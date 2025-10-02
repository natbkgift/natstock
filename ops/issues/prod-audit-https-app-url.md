# [Prod Audit] บังคับ HTTPS และปรับค่า APP_URL ให้ตรงโปรดักชัน

- **บริบท:** ระบบใช้งานที่ https://natstock.kesug.com แต่ไฟล์ `.env.example` ยังตั้ง `APP_URL=http://localhost` และ middleware `SecurityHeaders` จะเพิ่ม HSTS เฉพาะเมื่อคำขอเป็น HTTPS
- **ความเสี่ยง:** หาก `APP_URL` ยังเป็น HTTP หรือไม่มี redirect ระดับเว็บเซิร์ฟเวอร์ อาจเกิดปัญหา cookie/mixed content และลดความปลอดภัยของ session
- **วิธีตรวจ:**
  1. ตรวจค่า `APP_URL` บนเซิร์ฟเวอร์จริงด้วย `php artisan config:get app.url`
  2. รัน `curl -I http://natstock.kesug.com/ping` ต้องถูก redirect เป็น HTTPS
  3. ตรวจ header `Strict-Transport-Security` ในคำตอบ HTTPS
- **วิธีแก้ (ไม่แตะโค้ด):**
  - ตั้งค่า `APP_URL=https://natstock.kesug.com` ใน `.env`
  - เพิ่ม redirect 301 จาก HTTP→HTTPS ใน web server/cPanel หรือ Cloudflare
  - ยืนยันใบรับรอง TLS ไม่หมดอายุและเปิด auto renew
- **วิธีแก้ (แตะโค้ดภายหลัง):**
  - ปรับ default `.env.example` เป็น HTTPS เพื่อลดความผิดพลาดในการดีพลอยครั้งถัดไป
  - เพิ่ม automated test สำหรับ `/ping` ที่ตรวจ HTTPS
- **DoD:**
  - `curl -I http://natstock.kesug.com/ping` คืนสถานะ 301 ไป HTTPS
  - `php artisan config:get app.url` รายงาน URL ที่ถูกต้อง
  - smoke test บันทึกหลักฐาน header HSTS
- **Labels:** `audit`, `production`, `security`, `ops`
