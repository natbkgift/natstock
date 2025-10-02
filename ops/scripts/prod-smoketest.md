# สคริปต์ทดสอบโปรดักชัน (อ่านอย่างเดียว)

> ใช้เป็นแนวทาง smoke test หลังดีพลอยหรือหลังเหตุการณ์สำคัญ (ไม่แก้ไขข้อมูลจริง เว้นแต่ว่าระบุว่า "เฉพาะ staging")

## 1. Health & HTTPS
1. รัน `curl -s https://natstock.kesug.com/ping` คาดหวัง `{"ok":true}` และ timestamp ปัจจุบัน
2. รัน `curl -I http://natstock.kesug.com/ping` ต้องได้ 301 ไป `https://natstock.kesug.com/ping`
3. เปิดเบราว์เซอร์ตรวจว่ามี HSTS (`Strict-Transport-Security`) และไม่มี mixed content

## 2. การเข้าสู่ระบบ & Dashboard
1. เข้าระบบด้วยบัญชี Admin ที่เตรียมไว้สำหรับทดสอบ
2. ตรวจว่า Dashboard โหลดได้ภายใน < 2 วินาที และ widget ทั้งหมดแสดงข้อมูลล่าสุด

## 3. ทดสอบการแจ้งเตือนประจำวัน
1. ที่ Admin ▸ ตั้งค่าระบบ ตั้ง `daily_scan_time=08:00` แล้วบันทึก
2. กด "ทดสอบการแจ้งเตือน" เพื่อตรวจ In-App, Email, LINE ว่ารับข้อความ `[ทดสอบ]`
3. กด "เรียกสแกนแจ้งเตือนตอนนี้" (`settings/run-scan`) แล้วตรวจ log ว่ามีรายการใหม่ และ notification จริงถูกส่ง (ไม่ใช่โหมดทดสอบ)
4. ตรวจบันทึกในตาราง `activities` ว่ามีเหตุการณ์ `alerts.scan_manual`

## 4. ทดสอบนำเข้าไฟล์
1. เตรียมไฟล์ `import-good.xlsx` และ `import-bad.csv` (ตัวอย่างควรเก็บใน secure share)
2. ที่ Admin ▸ นำเข้าไฟล์ อัปโหลด `import-good.xlsx` โหมด UPSERT + auto create category → คาดหวัง preview ผ่านและ commit สำเร็จ, มี movement สร้างอัตโนมัติ
3. อัปโหลด `import-bad.csv` เพื่อให้เกิด validation error → คาดหวังมีสรุป error และปุ่มดาวน์โหลด `error.csv`
4. ดาวน์โหลด `error.csv` ตรวจว่ามีหัวคอลัมน์ครบและไม่มีสูตร Excel (ค่าเริ่มด้วย `'`)
5. ย้อนกลับไปลบข้อมูลทดสอบ (หากจำเป็น) หรือรีเซ็ตด้วยสคริปต์ staging

## 5. รายงาน & Export
1. ที่ Admin ▸ รายงาน ▸ ใกล้หมดอายุ เลือกหมวด/ค้นหา → คาดหวังจำนวนรายการและ summary ถูกต้อง
2. กด Export CSV แล้วเปิดไฟล์ ตรวจว่าแถวแรกเป็น UTF-8 BOM และไม่มีสูตรคำนวณ (ค่า prefix `''` หากเริ่มด้วยสัญลักษณ์)
3. ทำซ้ำกับรายงานสต็อกต่ำและมูลค่าคลัง รวมถึงตรวจยอดรวมท้ายตาราง valuation

## 6. Backup & Restore (เฉพาะรอบทดสอบรายสัปดาห์)
1. รัน `php artisan inventory:backup` ผ่าน SSH หรือปุ่มในหน้า Backup
2. ดาวน์โหลดไฟล์ zip ล่าสุด, ตรวจขนาดไฟล์ > 0 และมี `database.json`, `meta.json`, `files/`
3. บนเครื่อง staging แตกไฟล์และนำ `database.json` ไป insert ชั่วคราวเพื่อตรวจความครบถ้วน (ใช้สคริปต์เฉพาะ staging)

## 7. Log & Queue
1. ตรวจ `storage/logs/laravel-*.log` ว่า timestamp ล่าสุดเป็น Asia/Bangkok
2. รัน `php artisan queue:work --once` เพื่อเคลียร์งานค้าง (ต้องใช้ driver ที่ไม่ใช่ sync)
3. ตรวจระบบ Monitoring (APM/uptime) ว่าไม่มี alert ระหว่าง smoke test

## 8. ปิดงาน
1. คืนค่าการตั้งเวลาสแกนเป็นค่าที่ใช้งานจริง (ถ้าแก้ชั่วคราว)
2. บันทึกผลการทดสอบใน ops/deploy log พร้อมแนบไฟล์หลักฐาน (สกรีนช็อต, log snippet)
3. แจ้งทีมธุรกิจว่า smoke test ผ่าน พร้อมลิสต์ปัญหาที่เจอ (ถ้ามี)
