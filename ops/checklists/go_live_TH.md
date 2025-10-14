# เช็กลิสต์ Go-Live ระบบหลายล็อต (PR6)

## การตั้งค่าแอปพลิเคชัน
- [ ] `.env` กำหนด `APP_URL` เป็น `https://...` ตรงโดเมนจริง และใบรับรอง SSL ถูกต้อง
- [ ] `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`
- [ ] `INVENTORY_ENABLE_PRICE=false` (ตรวจได้ด้วย `php artisan tinker --execute="config('inventory.enable_price')"`)
- [ ] รัน `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## Scheduler / Cron
- [ ] เครื่องโปรดักชันมี cron `* * * * * php /path/to/artisan schedule:run` และ log ล่าสุดไม่มี error
- [ ] `php artisan schedule:list` แสดง job เคลียร์แจ้งเตือน/สำรองข้อมูลครบ

## การนำเข้า/ส่งออกและเทสต์ที่เกี่ยวข้อง
- [ ] หน้าพรีวิว import แสดงสรุป 20 แถว พร้อมแจ้งคอลัมน์ที่ถูก ignore (เช่น ราคา)
- [ ] ทดสอบ import โหมด **STRICT** ด้วยไฟล์ตัวอย่าง: เมื่อมี error ต้อง rollback ทั้งไฟล์
- [ ] ทดสอบ import โหมด **LENIENT** ด้วยไฟล์ที่มี error 1 แถว: ต้องสร้าง `error.csv` และ commit แถวอื่นสำเร็จ
- [ ] ปุ่ม export expiring-batches / low-stock ดาวน์โหลดได้และไม่มีคอลัมน์ราคา

## รายงานและแจ้งเตือน
- [ ] ตั้งค่า `expiring_days` ตามนโยบาย และตรวจรายงาน expiring ว่าแสดงเฉพาะล็อตในช่วงที่กำหนด
- [ ] รายงานสต็อกต่ำ (`qty_total` ≤ `reorder_point`) แสดงสินค้าตรงกับฐานทดสอบ
- [ ] เปิด Dashboard ด้วยผู้ใช้อย่างน้อย 2 บัญชีเพื่อยืนยัน modal แจ้งเตือนแยกตาม user (mark-read/snooze ทำงาน)

## ความพร้อมด้านข้อมูล
- [ ] รัน `php artisan inventory:backup` แล้วทดสอบกู้คืนบน staging หรือเครื่องทดสอบ
- [ ] ตรวจสอบว่าสินค้าที่ backfill แล้วมี `products.qty = 0` และยอดรวมอยู่ใน `product_batches`
- [ ] สุ่มสินค้าหลายรายการเพื่อตรวจ Movement ล่าสุด (receive/issue/adjust) ว่าระบุ lot_no ถูกต้อง

## เอกสารและการสื่อสาร
- [ ] อัปโหลดคู่มือ runbook/incident/rollback ตามรายการ PR6 ลง repo กลาง
- [ ] แจ้งทีมปฏิบัติการถึงขั้นตอนเปิด/ปิด feature flag ราคาและการใช้งาน import/export รูปแบบใหม่
- [ ] บันทึกผลการทดสอบ `php artisan test` รอบสุดท้ายแนบในบันทึก deploy
