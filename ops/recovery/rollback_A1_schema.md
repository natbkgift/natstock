# แผน Rollback โครงสร้างหลายล็อต (A1)

- **เวลาประมาณ**: 30–45 นาที (ขึ้นกับจำนวนสินค้า)
- **เป้าหมาย**: ย้อนโครงสร้างกลับไปใช้ `products.qty` แบบเดิม โดยไม่สูญเสีย movement history

## Do
- สำรองฐานข้อมูล (`php artisan inventory:backup`) ก่อนเริ่มทุกครั้ง
- ปิด traffic หรือประกาศ freeze การบันทึกข้อมูลกับผู้ใช้
- จดเวลาที่เริ่มและจบ rollback ใส่ incident log

## Don’t
- อย่าลบตาราง/คอลัมน์ก่อนยืนยันว่า `products.qty` ถูกอัปเดตกลับแล้ว
- อย่าลบข้อมูล `product_batches` ที่ยังผูกกับ `stock_movements` (ต้อง disable ก่อน)
- อย่าลืมเปิด flag ราคา (`INVENTORY_ENABLE_PRICE`) หากเคยปรับชั่วคราวเพื่อการทดสอบ

## ขั้นตอนดำเนินการ
1. **ตรวจสอบยอดในแต่ละล็อต**
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
   - บันทึกผลลัพธ์เพื่อตรวจสอบหลัง rollback

2. **รวมยอดกลับไปที่ตารางสินค้า**
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

3. **ตรวจว่ายอดสินค้าไม่ติดลบ**
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
   - ถ้ามีค่าติดลบต้องตรวจย้อน movement ก่อนทำขั้นตอนถัดไป

4. **ปิดการใช้งานล็อตและเตรียมลบโครงสร้าง**
   ```bash
   php artisan tinker <<'PHP'
   use Illuminate\Support\Facades\DB;

   DB::table('product_batches')->update([
       'is_active' => 0,
   ]);

   echo "ปิดการใช้งาน product_batches เรียบร้อย\n";
   PHP
   ```

5. **ลบ constraint/ตารางที่เพิ่มมาใน A1 อย่างปลอดภัย**
   - ลบ foreign key `stock_movements_batch_id_foreign`
   - ดรอปตาราง `product_lot_counters` และ `product_batches`
   - ลบคอลัมน์ `batch_id` จาก `stock_movements`

6. **รีเฟรช cache และตรวจ UI**
   ```bash
   php artisan config:clear
   php artisan config:cache
   php artisan view:clear
   ```
   - เปิดหน้า Product ตรวจว่ายอด `qty` ตรงกับข้อมูลที่รวมไว้ในข้อ 1

## หลังดำเนินการ
- เก็บ log คำสั่งทั้งหมดและผลลัพธ์ไว้ใน incident ticket
- แจ้งทีม QA ให้รัน `php artisan test --group=legacy-qty` (หรือเทสต์ที่ตกลงไว้) เพื่อยืนยันความถูกต้อง
- วางแผน deploy ใหม่เพื่อนำฟีเจอร์หลายล็อตกลับเมื่อปัญหาถูกแก้ไข
