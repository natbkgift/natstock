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
   ```sql
   SELECT product_id, SUM(qty) AS total_qty FROM product_batches GROUP BY product_id;
   ```
2. รวมยอดกลับไปที่ตารางสินค้า
   ```sql
   UPDATE products p
   JOIN (
       SELECT product_id, SUM(CASE WHEN is_active = 1 THEN qty ELSE 0 END) AS active_qty
       FROM product_batches
       GROUP BY product_id
   ) b ON b.product_id = p.id
   SET p.qty = b.active_qty;
   ```
3. ตรวจยอดติดลบ (ต้องไม่มี)
   ```sql
   SELECT sku, qty FROM products WHERE qty < 0;
   ```
4. ปิดการใช้งานล็อต
   ```sql
   UPDATE product_batches SET is_active = 0;
   ```
5. ลบ constraint/ตารางที่เพิ่มมาใน #A1 อย่างปลอดภัย
   - ลบ foreign key จาก `stock_movements.batch_id`
   - ลบตาราง `product_batches`
6. รัน `php artisan config:cache` และตรวจหน้า Product ว่ายอดกลับไปใช้ `products.qty`

> หลัง rollback เสร็จให้บันทึกเหตุผลใน ticket และเก็บ snapshot ตาราง `stock_movements` ไว้ตรวจสอบย้อนหลัง
