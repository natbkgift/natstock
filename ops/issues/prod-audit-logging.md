# [Prod Audit] จัดการ log rotation และ audit trail ส่งออก

- **บริบท:** ระบบใช้ `Log::channel('daily')` พร้อม masking แต่ `.env.example` ยังตั้ง `LOG_CHANNEL=stack`
- **ความเสี่ยง:** หาก log ไม่หมุนจะทำให้ไฟล์เดียวโตจนเต็มดิสก์, audit trail ไม่ถูกส่งออกสู่ศูนย์กลาง → ยากต่อการตรวจสอบย้อนหลัง
- **วิธีตรวจ:**
  1. รัน `php artisan config:get logging.default` และตรวจไฟล์ `storage/logs`
  2. ตรวจว่ามีการส่งออก log ไปยังระบบกลางหรือไม่ (เช่น syslog/ELK)
  3. ตรวจว่าตาราง `activities` ถูกสำรองหรือส่งออกเป็นระยะ
- **วิธีแก้ (ไม่แตะโค้ด):**
  - ตั้ง `LOG_CHANNEL=daily` และ/หรือเพิ่ม Slack/Syslog channel
  - จัดตั้ง policy rotation & retention (เช่น เก็บ 30 วันในเครื่อง + ส่งออก)
  - สร้างงานส่งออกตาราง `activities` รายสัปดาห์ไปยังที่ปลอดภัย
- **วิธีแก้ (แตะโค้ดภายหลัง):**
  - ปรับ `config/logging.php` ให้ `stack` รวม `daily`
  - เพิ่มฟีเจอร์ export audit log (CSV/JSON)
- **DoD:**
  - มีหลักฐาน log หมุนรายวันและถูกส่งออก
  - ตาราง `activities` ถูกสำรอง/ส่งออกตามรอบที่กำหนด
  - Dashboard monitoring แสดงสถานะ log collector
- **Labels:** `audit`, `production`, `security`, `ops`
