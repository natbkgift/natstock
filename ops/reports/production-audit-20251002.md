# รายงานตรวจสอบโปรดักชันระบบคลังสินค้า (natstock)

## บริบทระบบ
- โฮสต์หลัก: https://natstock.kesug.com
- โซนเวลาเป้าหมาย: Asia/Bangkok
- แพลตฟอร์ม: Laravel 11 + PHP 8.3 (ตาม composer.json) + MySQL/Postgres (รองรับทั้งคู่)

## สถานะรวม
- ภาพรวม: **ต้องแก้ไขเชิงโครงสร้างบางจุดก่อนใช้งานจริงเต็มรูปแบบ**
- ประเด็นเร่งด่วน: โมดูลกำหนดสิทธิ์ผู้ใช้ (UserPolicy) ถูกวางผิดที่ทำให้ autoload ไม่พบ, ยังไม่ยืนยันการบังคับใช้ HTTPS/APP_URL, ต้องจัดตั้ง cron/scheduler และวงจรสำรองข้อมูลให้ครบ

## รายการตรวจ 10 หัวข้อ

### 1) HTTPS / APP_URL
- **วิธีตรวจ:** ตรวจค่า `APP_URL` ใน `.env` และตั้งค่า redirect HTTP→HTTPS พร้อมทดสอบ `https://natstock.kesug.com/ping` และ `http://` ว่าถูกบังคับไป HTTPS; ตรวจ HSTS จาก middleware
- **คาดหวัง:** `APP_URL` ตั้งเป็น `https://natstock.kesug.com`, เว็บบังคับ HTTPS, HSTS เปิดใช้งาน
- **ผลที่พบ:** ไฟล์ `.env.example` ยังตั้ง `APP_URL=http://localhost`; middleware `SecurityHeaders` เพิ่ม HSTS เฉพาะเมื่อคำขอเป็น HTTPS แต่ยังไม่ยืนยันว่าระดับเว็บเซิร์ฟเวอร์บังคับ redirect แล้ว
- **ข้อเสนอแนะ:** ตั้ง `APP_URL=https://natstock.kesug.com` ในโปรดักชัน, กำหนด redirect 301 จาก HTTP→HTTPS ที่ layer หน้า (เช่น web server / Cloudflare) และทดสอบว่าคำสั่ง `curl -I http://natstock.kesug.com/ping` ถูกส่งต่อ 301 ไปยัง HTTPS; ตรวจว่าใบรับรอง TLS ไม่หมดอายุ

### 2) การตั้งค่า `.env` โปรดักชัน
- **วิธีตรวจ:** รวบรวมเช็กลิสต์ค่าบังคับ (APP_KEY, APP_ENV=production, APP_DEBUG=false, LOG_CHANNEL=daily, QUEUE_CONNECTION=database/redis, MAIL_*, LINE_NOTIFY_TOKEN ฯลฯ) และตรวจผ่านหน้าตั้งค่าระบบว่าการตั้งค่าแจ้งเตือนอ่านค่า `.env` ถูกต้อง
- **คาดหวัง:** ค่าที่อ่อนไหวตั้งครบ, ปิด debug, log หมุนรายวัน, ช่องทางแจ้งเตือน (email/LINE) กรอกจริง
- **ผลที่พบ:** `.env.example` ใช้ค่า dev (`APP_DEBUG=true`, `LOG_CHANNEL=stack`, `QUEUE_CONNECTION=sync`, `MAIL_*` เป็น log); หากไม่แก้ในโปรดักชันจะทำให้ debug เปิดและ log ไม่หมุน; ค่า default ของ `notify_emails` ถูก cache ผ่าน `SettingManager`
- **ข้อเสนอแนะ:** เติม `.env` โปรดักชันจริงตามเช็กลิสต์ (แนบในไฟล์ readiness), ยืนยันว่า `php artisan config:cache` อ่านค่าใหม่, เก็บไฟล์ `.env` นอก Git และสำรองเข้าที่ปลอดภัย

### 3) Scheduler / Cron & การแจ้งเตือน
- **วิธีตรวจ:** ตรวจตาราง cron ว่ารัน `php artisan schedule:run` ทุกนาที, จากโค้ด `App\Console\Kernel` และ `SettingController` ให้ทดสอบสั่ง `settings/run-scan` และ `settings/test-notification`
- **คาดหวัง:** คำสั่ง `inventory:scan-alerts` รันตาม `daily_scan_time` (ดีฟอลต์ 08:00 น.) และ `inventory:backup` รันรายสัปดาห์, แจ้งเตือนส่งถึง In-App/Email/LINE ตามที่เปิดไว้
- **ผลที่พบ:** โค้ดตั้งค่าสองคำสั่งพร้อม timezone Asia/Bangkok; มีบริการ `NotificationTestService` ให้ทดสอบ manual; ยังไม่พบหลักฐานว่ามีการตั้ง cron บนเซิร์ฟเวอร์จริง
- **ข้อเสนอแนะ:** เพิ่ม cron job `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`, บันทึกผลรันลง log และทดสอบ workflow แจ้งเตือนทุกช่องทาง, เก็บสถิติว่าแจ้งเตือนออกเวลา 08:00–08:05 ตาม requirement

### 4) นำเข้าไฟล์ CSV/XLSX (UPSERT/SKIP + error.csv)
- **วิธีตรวจ:** ทดสอบอัปโหลดไฟล์ตัวอย่าง (ดี/เสีย) ผ่านเมนูนำเข้า, ตรวจผล preview, ลอง commit ทั้งโหมด UPSERT/ SKIP, ดาวน์โหลด `error.csv`
- **คาดหวัง:** ระบบพรีวิว 20 แถวแรก, ตรวจสอบ validation, เมื่อ commit แล้วบันทึกหรือข้ามตามโหมด, error rows ถูกส่งออกไฟล์พร้อม raw data, rollback transaction หากเจอข้อผิดพลาด
- **ผลที่พบ:** `ImportService` รองรับ validation, chunk ละ 500 แถว, rollback ผ่าน transaction, มีการสร้าง `error.csv` พร้อมลิงก์ signed URL 24 ชม.; เส้นทางจัดเก็บใน `storage/app/tmp`
- **ข้อเสนอแนะ:** เตรียมไฟล์ทดสอบดี/เสียในทีม QA, ตั้ง scheduled job เพื่อล้างไฟล์ tmp เก่า, ตรวจสิทธิ์โฟลเดอร์ `storage/app/tmp`

### 5) รายงาน / แดชบอร์ด
- **วิธีตรวจ:** ตรวจหน้า expiring/low stock/valuation, ลอง export CSV, ตรวจ summary และสิทธิ์ `Gate::authorize('access-viewer')`
- **คาดหวัง:** เฉพาะ role viewer ขึ้นไปเข้าหน้ารายงานได้, CSV ปลอด CSV injection, summary ถูกต้อง
- **ผลที่พบ:** `ReportController` ใช้ service layer (`ProductReportService`) พร้อมฟิลเตอร์หมวด/สถานะ/ค้นหา, export ผ่าน `CsvExporter` ที่ sanitize ค่า (ใส่ prefix ป้องกันสูตร); ยังไม่มีการตั้งค่า cache สำหรับรายงานหนัก
- **ข้อเสนอแนะ:** เปิดใช้ caching (config/route/view) หลังดีพลอย, พิจารณาเพิ่มดัชนีพิเศษสำหรับคอลัมน์ `qty`/`reorder_point` หากฐานข้อมูลโต, ทำ load test รายงาน valuation

### 6) Roles / Policy
- **วิธีตรวจ:** ตรวจตำแหน่งไฟล์ policy, ทดสอบสร้าง/แก้ไข/ลบผู้ใช้ด้วยบัญชี non-admin, ตรวจ `Gate`
- **คาดหวัง:** Policy สำหรับ `User` อยู่ใต้ `app/Policies` และบังคับเฉพาะ Admin, ไม่มีผู้ใช้ลบบัญชีตัวเองได้
- **ผลที่พบ:** ไฟล์ `UserPolicy` ถูกวางไว้ใน `resources/views/auth/UserPolicy.php` ทำให้ autoloader ไม่พบ `App\Policies\UserPolicy` ที่ลงทะเบียนใน `AuthServiceProvider` → เสี่ยงเกิด fatal error หรือ policy ไม่ถูกบังคับ; `Route` สำหรับผู้ใช้พึ่ง `can:access-admin` แต่ยังควรแก้ policy ให้ถูกที่
- **ข้อเสนอแนะ:** ย้ายไฟล์ `UserPolicy` ไป `app/Policies/UserPolicy.php` และเพิ่มการทดสอบสิทธิ์, ตรวจทวนว่าผู้ใช้ non-admin ไม่เห็นเมนูจัดการผู้ใช้; หลังย้ายรัน `composer dump-autoload`

### 7) Audit / Logs
- **วิธีตรวจ:** ตรวจบริการ `AuditLogger`, ตาราง `activities`, การตั้งค่า logging, ทดสอบกิจกรรมสำคัญ (import, settings) แล้วตรวจ log/ตาราง
- **คาดหวัง:** ทุกเหตุการณ์สำคัญถูกบันทึกทั้งใน DB และ log หมุนรายวัน, ค่าอ่อนไหวถูก mask, มี retention
- **ผลที่พบ:** `AuditLogger` บันทึก IP/user agent ลงตาราง `activities` ที่มี index, log channel `daily` มี mask sensitive, แต่ `.env.example` ยังใช้ `LOG_CHANNEL=stack` (single file) → อาจไม่ได้หมุน log; ไม่มีการผลัก log ออกนอกเครื่อง
- **ข้อเสนอแนะ:** ตั้ง `LOG_CHANNEL=daily` หรือเพิ่ม channel ใน stack, ส่งออก log ไป external (เช่น CloudWatch/ELK), สร้าง dashboard audit สำหรับทีม compliance

### 8) Backup / Restore
- **วิธีตรวจ:** ตรวจคำสั่ง `inventory:backup` และเมนู admin/backup, ทดสอบสร้างไฟล์, ดาวน์โหลดและลองกู้คืนใน staging
- **คาดหวัง:** สำรองได้ไฟล์ zip มีฐานข้อมูล (JSON) + storage/public/tmp, retention 7 ไฟล์, แจ้งเตือน audit log
- **ผลที่พบ:** `BackupService` สร้าง zip ใน `storage/app/backups`, แนบ meta+database, retention 7, บันทึก audit + log; ยังไม่มีหลักฐานว่ามีการคัดลอกไฟล์ไป offsite หรือทดสอบ restore
- **ข้อเสนอแนะ:** สร้าง cron เรียก `inventory:backup` หลังเวลางาน, sync ไฟล์ไป object storage, ทำ drill restore รายไตรมาส, ตรวจสิทธิ์การเข้าถึงไฟล์สำรอง

### 9) Security Headers
- **วิธีตรวจ:** ตรวจ middleware `SecurityHeaders`, ทดสอบ response header ทุกหน้า, ตรวจ CSP ผ่าน browser devtools
- **คาดหวัง:** มี X-Content-Type-Options, X-Frame-Options, Referrer-Policy, HSTS, CSP ไม่เปิด `unsafe-inline`
- **ผลที่พบ:** Middleware เพิ่ม header ครบ, HSTS เฉพาะ HTTPS, CSP ยังคง `'unsafe-inline'` สำหรับ script/style และอนุญาต CDN เดียว; ยังไม่มี `Permissions-Policy`
- **ข้อเสนอแนะ:** ค่อย ๆ ย้าย inline script/style ออกเพื่อถอด `unsafe-inline`, เพิ่ม `Permissions-Policy`/`Cross-Origin-Opener-Policy`, ตรวจว่า static asset โหลดผ่าน CDN ที่เชื่อถือได้

### 10) Performance (config/route/view cache + ดัชนี DB)
- **วิธีตรวจ:** ตรวจการใช้ cache (`SettingManager`), ตรวจ migration ว่ามี index, รัน `php artisan config:cache`, `route:cache`, `view:cache` บน staging แล้วเทียบ latency
- **คาดหวัง:** ตั้งค่า cache หลังดีพลอย, DB มี index เพียงพอ, ไม่มีคิวที่ใช้ `sync` ในโปรดักชัน
- **ผลที่พบ:** `SettingManager` cache ค่า settings ผ่าน `Cache::rememberForever`, ตารางสำคัญมี index (`products` มี unique + index, `stock_movements` มี composite, `activities` มี index), แต่ `.env` ยังตั้ง `QUEUE_CONNECTION=sync` และไม่มีเอกสารการรันคำสั่ง cache หลังดีพลอย
- **ข้อเสนอแนะ:** ตั้ง queue driver เป็น `database`/`redis` สำหรับงานแจ้งเตือน, รวมขั้นตอน `php artisan config:cache route:cache view:cache` ใน playbook ดีพลอย, เฝ้าระวัง slow query ผ่าน APM/monitoring

## Roadmap 30/60/90 วัน
- **30 วัน:** ปรับ `.env` โปรดักชัน, บังคับ HTTPS, ตั้ง cron + ตรวจแจ้งเตือน, ย้าย `UserPolicy` และทดสอบสิทธิ์, เริ่มหมุน log รายวัน
- **60 วัน:** ทำ drill สำรอง/กู้คืนเต็มรูปแบบ, เพิ่ม monitoring สำหรับแจ้งเตือน/cron, ปรับ CSP เพื่อลด `unsafe-inline`, ตั้ง queue driver นอก sync
- **90 วัน:** ออกแบบ offsite backup + automation, ทำ performance tuning (cache + index review), เพิ่ม Security Headers เพิ่มเติม (Permissions-Policy) และเสริม SOC report สำหรับ audit รอบต่อไป
