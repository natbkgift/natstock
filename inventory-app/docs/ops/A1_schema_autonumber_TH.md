# A1: สคีมาหลายล็อต + ระบบเลขรันอัตโนมัติ (SKU/LOT)

## ภาพรวมสคีมาใหม่

| ตาราง | คีย์ | รายละเอียด |
|-------|------|-------------|
| `product_batches` | `id` (PK), `unique(product_id, lot_no)` | เก็บล็อตของสินค้า แต่ละแถวมี `product_id`, `lot_no` (เช่น `LOT-01`), `qty`, `expire_date`, `received_at`, `is_active`, `note`. มีดัชนีร่วมบน `product_id, expire_date` เพื่อช่วยค้นหาล็อตใกล้หมดอายุ |
| `stock_movements` | เพิ่ม FK `batch_id` | ผูกการเคลื่อนไหวสต็อกเข้ากับล็อต (nullable, ลบล็อตแล้วตั้งค่า `null`) |
| `products` | `sku` (unique) | ยังคงตารางเดิม แต่ระบบเลิกใช้คอลัมน์ `qty` เป็นแหล่งความจริงหลัง backfill |
| `sequences` | `key` (PK), `next_val` | เก็บเลขรันกลาง เช่น `('SKU', 1)` |
| `product_lot_counters` | `product_id` (PK/FK), `next_no` | ติดตามเลขล็อตถัดไปของสินค้าแต่ละตัว (ค่าเริ่มต้น 1) |

## หลักการเลขรัน

- **SKU**: ใช้ `SkuService::next()` เพื่อจองเลขแบบ global sequence ในรูป `SKU-0001`, `SKU-0002`, ... ฟังก์ชันรันในทรานแซกชันพร้อม `SELECT ... FOR UPDATE` เพื่อป้องกัน race condition
- **LOT**: ใช้ `LotService::nextFor($product)` ผูกกับสินค้า สร้างเลขแบบ `LOT-01`, `LOT-02` ต่อสินค้า (ล็อตแรก `LOT-01` ถูกสร้างอัตโนมัติเมื่อมีสินค้าใหม่หรือผ่าน backfill) ตัวบริการล็อกแถวใน `product_lot_counters` เช่นกัน
- เมื่อสร้างสินค้าใหม่ (ผ่านโมเดล) ระบบจะกำหนด `sku` อัตโนมัติถ้าไม่ได้ส่งมา และสร้างล็อตตั้งต้น `LOT-01` (qty=0) พร้อมตั้ง `next_no = 2`

## ขั้นตอน Backfill

> **เตือน**: รันคำสั่งนอกเวลาใช้งานจริงเพื่อลดผลกระทบต่อการทำงานของผู้ใช้

1. ติดตั้ง/อัปเดต Composer: `composer install`
2. รันไมเกรชัน: `php artisan migrate --force`
3. รันคำสั่ง backfill: `php artisan backfill:product-batches`
   - กำหนด SKU ให้สินค้าที่ว่าง
   - ย้ายยอด `products.qty > 0` ไปสร้าง `product_batches` (`LOT-01`, `received_at` = เวลารันคำสั่ง)
   - ตั้ง `product_lot_counters.next_no = 2` และรีเซ็ต `products.qty = 0`
4. ตรวจสอบผลลัพธ์:
   - ดูจำนวนล็อตต่อสินค้าให้ครบ
   - ตรวจค่าคงเหลือผ่าน `Product::qtyCurrent()` (ควรเท่ากับยอดเดิมรวมทุกล็อต)

## แผน Rollback (อ่านทำความเข้าใจเท่านั้น)

หากจำเป็นต้องย้อนกลับ:

1. รวมยอดจาก `product_batches` (เฉพาะที่ `is_active = 1`) กลับไปยัง `products.qty`
2. ตรวจสอบว่าไม่มีโมดูลอื่นพึ่งพาตารางใหม่แล้วค่อยรัน `php artisan migrate:rollback`
3. ลบข้อมูลใน `product_batches`, `product_lot_counters`, `sequences` ตามลำดับหลัง rollback (อย่าลืมสำรองข้อมูลก่อนทุกครั้ง)

> หมายเหตุ: ระหว่าง rollback ห้ามลืมปิดการใช้งานระบบเพื่อป้องกันข้อมูลคาดเคลื่อน
