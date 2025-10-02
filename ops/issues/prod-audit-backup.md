# [Prod Audit] ทำ automation สำรอง/กู้คืนให้ครบวงจร

- **บริบท:** คำสั่ง `inventory:backup` สร้าง zip (database.json + meta + storage) และลบไฟล์เกิน 7 ชุด
- **ความเสี่ยง:** หากไม่รันทดสอบ restore หรือไม่มี offsite storage เมื่อเกิดเหตุฉุกเฉินจะกู้คืนไม่ได้
- **วิธีตรวจ:**
  1. ตรวจ timestamp ของไฟล์ใน `storage/app/backups`
  2. ตรวจว่าไฟล์ถูกคัดลอกออกนอกเครื่อง (object storage/FTP)
  3. ทำ drill restore บน staging แล้วจดบันทึก
- **วิธีแก้ (ไม่แตะโค้ด):**
  - ตั้ง cron รัน `php artisan inventory:backup` หลังเวลางาน
  - Sync ไฟล์ไป offsite (เช่น Rclone -> S3) พร้อมเข้ารหัส
  - จัดทำ playbook กู้คืนและซ้อมอย่างน้อยรายไตรมาส
- **วิธีแก้ (แตะโค้ดภายหลัง):**
  - เพิ่ม notification แจ้งเมื่อ backup สำเร็จ/ล้มเหลว
  - เพิ่มตัวเลือกสำรองเฉพาะ database (SQL dump) เพื่อความรวดเร็ว
- **DoD:**
  - มีหลักฐานไฟล์สำรองล่าสุด ≤ 24 ชม.
  - มีรายงานการซ้อมกู้คืน (restore report) ลงนามโดยทีมที่เกี่ยวข้อง
  - Offsite storage ยืนยันว่าไฟล์ถูก replicate เรียบร้อย
- **Labels:** `audit`, `production`, `security`, `ops`
