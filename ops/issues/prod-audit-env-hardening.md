# [Prod Audit] ปรับค่า `.env` โปรดักชันให้ปลอดภัย

- **บริบท:** `.env.example` ยังใช้ค่าดีฟอลต์สำหรับการพัฒนา (เปิด debug, log stack, queue sync)
- **ความเสี่ยง:** หากนำไปใช้จริงจะทำให้ข้อมูลเซิร์ฟเวอร์รั่ว (debug), log ไม่หมุน, งาน background ทำงานใน process หลัก ทำให้ response ช้า
- **วิธีตรวจ:**
  1. SSH เข้าเซิร์ฟเวอร์ รัน `php artisan env`, `php artisan config:get app.debug`, `php artisan config:get logging.default`, `php artisan config:get queue.default`
  2. ตรวจบันทึกว่ามีการตั้งค่า MAIL_* และ LINE_NOTIFY_TOKEN จริง
  3. ตรวจสิทธิ์ไฟล์ `.env` (ควรเป็น 600/640)
- **วิธีแก้ (ไม่แตะโค้ด):**
  - ตั้งค่า `APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=daily`, `QUEUE_CONNECTION=database` (หรือ redis)
  - เติม MAIL และ LINE token จากความต้องการธุรกิจ
  - จำกัดสิทธิ์ไฟล์ `.env` และสำรองใน vault ที่ปลอดภัย
- **วิธีแก้ (แตะโค้ดภายหลัง):**
  - ปรับ `.env.example` ให้เหมาะกับ production (เช่น ปิด debug, log daily)
  - เพิ่มเอกสารอธิบายค่าที่ต้องตั้งใน README/deploy guide
- **DoD:**
  - คำสั่งข้างต้นรายงานค่าตรงตาม policy
  - กดทดสอบแจ้งเตือนแล้วสำเร็จทุกช่องทาง
  - ไม่มี warning เรื่อง permission ของไฟล์ `.env`
- **Labels:** `audit`, `production`, `security`, `ops`
