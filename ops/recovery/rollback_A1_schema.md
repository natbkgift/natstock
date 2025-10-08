# แผน Rollback โครงสร้างหลายล็อต (#A1)

- **เวลาประมาณ**: 30 นาที (รวมตรวจสอบ)

## Do
- สำรองฐานข้อมูล (`php artisan inventory:backup`) ก่อนทุกครั้ง
- ปิด traffic ชั่วคราวหรือสื่อสารให้ผู้ใช้หยุดบันทึกข้อมูล
- รวมยอด `product_batches` กลับไปยัง `products.qty` ตามสินค้าล่าสุด

## Don't
- อย่าลบตาราง/คอลัมน์ก่อนยืนยันว่า `products.qty` อัปเดตครบ
- อย่าลบล็อตที่ยังมี movement ค้าง (จะทำให้ audit ขาด)

## ขั้นตอน
1. ตรวจสอบจำนวนล็อต
   ```bash
   php artisan tinker <<'PHP'
   use Illuminate\Support\Facades\DB;

   DB::table('product_batches')
       ->selectRaw('product_id, SUM(qty) as total_qty')
       ->groupBy('product_id')
       ->orderBy('product_id')
       ->chunk(100, function ($rows) {
           foreach ($rows as $row) {
               echo "สินค้า ID {$row->product_id} => รวม {$row->total_qty}\n";
           }
       });
   PHP
   ```
2. รวมยอดกลับไปที่ตารางสินค้า
   ```bash
   php artisan tinker <<'PHP'
   use Illuminate\Support\Facades\DB;

   DB::table('products')
       ->orderBy('id')
       ->chunk(100, function ($products) {
           foreach ($products as $product) {
               $activeQty = DB::table('product_batches')
                   ->where('product_id', $product->id)
                   ->where('is_active', 1)
                   ->sum('qty');

               DB::table('products')
                   ->where('id', $product->id)
                   ->update(['qty' => $activeQty]);
           }
       });

   echo "อัปเดต qty จากล็อตเสร็จสิ้น\n";
   PHP
   ```
3. ตรวจยอดติดลบ (ต้องไม่มี)
   ```bash
   php artisan tinker <<'PHP'
   use Illuminate\Support\Facades\DB;

   $negatives = DB::table('products')
       ->where('qty', '<', 0)
       ->select('sku', 'qty')
       ->orderBy('sku')
       ->get();

   if ($negatives->isEmpty()) {
       echo "ไม่มียอดติดลบ\n";
   } else {
       foreach ($negatives as $row) {
           echo "SKU {$row->sku} => qty {$row->qty}\n";
       }
   }
   PHP
   ```
4. ปิดการใช้งานล็อต
   ```bash
   php artisan tinker <<'PHP'
   use Illuminate\Support\Facades\DB;

   DB::table('product_batches')->update(['is_active' => 0]);

   echo "ปิดการใช้งาน product_batches เรียบร้อย\n";
   PHP
   ```
5. ลบ constraint/ตารางที่เพิ่มมาใน #A1 อย่างปลอดภัย
   - ลบ foreign key จาก `stock_movements.batch_id`
   - ลบตาราง `product_batches`
6. รัน `php artisan config:cache` และตรวจหน้า Product ว่ายอดกลับไปใช้ `products.qty`

> หลัง rollback เสร็จให้บันทึกเหตุผลใน ticket และเก็บ snapshot ตาราง `stock_movements` ไว้ตรวจสอบย้อนหลัง
