# บันทึกสคีมารองรับหลายล็อต (A1)

## ภาพรวม
- เพิ่มตาราง `product_batches` สำหรับเก็บ lot/sub SKU ต่อสินค้า พร้อมสถานะใช้งานและวันหมดอายุ
- เพิ่มคอลัมน์ `batch_id` ใน `stock_movements` เพื่อผูกความเคลื่อนไหวกับ lot เฉพาะ (อนาคต)
- เพิ่มเมธอด `Product::qtyCurrent()` สำหรับดึงยอดคงเหลือจาก batch ที่เปิดใช้งาน พร้อม fallback ไป `products.qty`

## เหตุผล
- เตรียมรองรับสินค้าที่มีหลายล็อตหรือวันหมดอายุ โดยไม่กระทบข้อมูลเดิม
- แยกยอดคงเหลือราย lot เพื่อให้สามารถทำงานต่อยอดกับ UI/รายงานในเฟสถัดไป

## ความเสี่ยงที่ต้องระวัง
- หากมีการสร้าง lot ใหม่ซ้ำ `sub_sku` จะชนกับ unique constraint (`product_id`, `sub_sku`)
- คำสั่ง backfill จะตั้ง `products.qty` เป็น 0 ทั้งหมด ควรสำรองข้อมูลก่อนรันจริง
- หากระบบภายนอกยังพึ่งพา `products.qty` ต้องปรับให้เรียก `qtyCurrent()` แทน

## ขั้นตอน backfill
1. สำรองฐานข้อมูลก่อน (`php artisan inventory:backup` แนะนำ)
2. รัน `php artisan migrate --force`
3. รัน `php artisan backfill:product-batches`
4. ตรวจสอบจำนวน lot ที่สร้าง (ค่าดีฟอลต์ `UNSPECIFIED`) และเทียบยอดรวมกับยอดเดิม

## ขั้นตอน rollback (ฉุกเฉิน)
1. รวมยอด `qty` ของทุก batch กลับไปที่คอลัมน์ `products.qty` ต่อสินค้า
2. ลบ foreign key และคอลัมน์ `batch_id` จาก `stock_movements`
3. ลบตาราง `product_batches`
4. ปรับโค้ดให้กลับไปใช้งาน `products.qty` โดยตรง

> หมายเหตุ: ขั้นตอนที่ 1 สามารถเขียนสคริปต์ SQL หรือใช้คำสั่ง artisan ชั่วคราวเพื่อป้องกันยอดคงเหลือหาย
