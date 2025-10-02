# [Prod Audit] ตั้งค่า cron สำหรับ inventory scheduler

- **บริบท:** `App\Console\Kernel` ตั้งคำสั่ง `inventory:scan-alerts` (daily 08:00 Asia/Bangkok) และ `inventory:backup` (weekly Monday 02:00)
- **ความเสี่ยง:** หากไม่มี cron job จริง งานแจ้งเตือนและสำรองจะไม่ทำงาน → พลาด alert สินค้าใกล้หมดอายุและไม่มีไฟล์สำรองล่าสุด
- **วิธีตรวจ:**
  1. ตรวจ `crontab -l` หรือ systemd timer บนเครื่องโปรดักชัน
  2. ดู log `storage/logs/laravel.log` ว่ามีรายการสแกนรายวัน/สำรองตามเวลา
  3. ตรวจตาราง `activities` หาเหตุการณ์ `alerts.scan_manual` หรือ `backup.created`
- **วิธีแก้ (ไม่แตะโค้ด):**
  - เพิ่ม cron `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`
  - ตั้ง monitoring แจ้งเตือนเมื่อคำสั่งไม่รันภายในช่วงเวลา (เช่น Healthchecks.io)
  - บันทึกขั้นตอนในคู่มือปฏิบัติการ (SOP)
- **วิธีแก้ (แตะโค้ดภายหลัง):**
  - เพิ่ม log/metric ส่งออก (เช่น Prometheus) เพื่อเฝ้าดูผลลัพธ์แต่ละรัน
  - เพิ่ม notification เมื่อ backup ล้มเหลว
- **DoD:**
  - มีหลักฐาน cron ทำงาน (timestamp ล่าสุด ≤ 1 ชม.)
  - พบ log `inventory:scan-alerts` และ `inventory:backup` ในรอบ 24/168 ชม.
  - Monitoring แจ้งเตือนเมื่อ cron หยุดทำงาน
- **Labels:** `audit`, `production`, `security`, `ops`
