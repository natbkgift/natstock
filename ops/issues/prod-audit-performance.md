# [Prod Audit] เปิดใช้งาน runtime cache และเปลี่ยน queue driver

- **บริบท:** `SettingManager` ใช้ cache, migrations มี index แต่ `.env.example` ยังตั้ง `QUEUE_CONNECTION=sync` และไม่มีขั้นตอนบังคับ cache หลังดีพลอย
- **ความเสี่ยง:** งานแจ้งเตือน/อีเมลทำงานใน request หลัก → เวลา response ช้า, config/route/view cache ไม่ถูกใช้ทำให้โหลดช้าใน production
- **วิธีตรวจ:**
  1. ตรวจ `php artisan config:get queue.default`
  2. ตรวจ deployment playbook ว่ามีคำสั่ง `config:cache`, `route:cache`, `view:cache`
  3. ตรวจ monitoring latency ของหน้า dashboard/reports
- **วิธีแก้ (ไม่แตะโค้ด):**
  - ตั้ง queue driver เป็น `database` หรือ `redis`, ตั้ง queue worker เป็น service/systemd
  - เพิ่มขั้นตอน cache commands ใน checklist ดีพลอย
  - สร้าง KPI latency และติดตามหลังเปิด cache
- **วิธีแก้ (แตะโค้ดภายหลัง):**
  - สร้าง job แยกสำหรับงานหนัก (ส่งอีเมล, LINE)
  - เพิ่ม health check endpoint สำหรับ queue worker
- **DoD:**
  - Queue driver ใหม่ทำงานและ worker ออนไลน์ต่อเนื่อง
  - ผลการรัน `php artisan config:cache route:cache view:cache` ถูกบันทึกทุกครั้งที่ดีพลอย
  - Latency หน้า dashboard/reports ลดลงและอยู่ใน SLA
- **Labels:** `audit`, `production`, `security`, `ops`
