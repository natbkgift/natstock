# [Prod Audit] เสริมประสิทธิภาพรายงานและการส่งออก CSV

- **บริบท:** รายงาน expiring/low stock/valuation ใช้การ join/filter และ export CSV ผ่าน `CsvExporter`
- **ความเสี่ยง:** เมื่อข้อมูลโตอาจทำให้หน้ารายงานช้า, export ใช้ memory สูง, ผู้ใช้รอคิวนานจนเกิด timeout
- **วิธีตรวจ:**
  1. วัดเวลาโหลดรายงานด้วยข้อมูลจริง (โดยใช้ Laravel Telescope/clockwork หรือ nginx log)
  2. ตรวจ usage ของฐานข้อมูล (index hit ratio, slow query log)
  3. ทดสอบ export CSV ขนาดใหญ่บน staging
- **วิธีแก้ (ไม่แตะโค้ด):**
  - เปิดใช้ `php artisan config:cache`, `route:cache`, `view:cache` หลังดีพลอย
  - สร้างตาราง monitoring latency ของรายงานและตั้ง SLA
  - แจ้งผู้ใช้ให้แบ่งช่วงเวลา export หรือใช้ฟิลเตอร์จำกัดผลลัพธ์
- **วิธีแก้ (แตะโค้ดภายหลัง):**
  - เพิ่ม pagination/async export (queue + notification)
  - เพิ่ม index เฉพาะ (เช่น composite บน `is_active`, `category_id`, `qty`)
  - ปรับ Service ให้ใช้ chunked streaming แทนการโหลดทั้งหมดใน memory
- **DoD:**
  - รายงานหลักโหลดภายใน SLA ที่กำหนด (<3 วินาที) บนข้อมูลจริง
  - Export CSV ผ่านได้โดยไม่เกิด memory exhaustion
  - บันทึก performance baseline ในเอกสาร ops
- **Labels:** `audit`, `production`, `security`, `ops`
