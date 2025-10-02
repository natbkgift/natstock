# เช็กลิสต์ความพร้อมโปรดักชัน (natstock)

> ใช้ติ๊ก `[x]` เมื่อดำเนินการเสร็จและแนบหลักฐาน (สกรีนช็อต/ลิงก์ผลตรวจ) ในแต่ละข้อ

## HTTPS / APP_URL
- [ ] ตรวจว่าเปิด https://natstock.kesug.com แล้วใบรับรองถูกต้อง (ไม่มี warning)
- [ ] รัน `curl -I http://natstock.kesug.com/ping` ต้องได้สถานะ 301/302 ไป HTTPS
- [ ] ในหน้า Admin ▸ ตั้งค่าระบบ แสดง URL ฐานเป็น `https://natstock.kesug.com`

## .env / ค่าพื้นฐาน
- [ ] เปิด SSH แล้วรัน `php artisan env` เพื่อยืนยัน `APP_ENV=production`
- [ ] ตรวจ `php artisan tinker --execute="config('app.debug')"` ต้องได้ `false`
- [ ] รัน `php artisan key:show` ต้องคืนค่า APP_KEY (ไม่ว่าง)
- [ ] ตรวจ `php artisan config:get logging.default` ต้องเป็น `daily` หรือ stack ที่หมุนรายวัน
- [ ] ตรวจ `php artisan config:get queue.default` ต้องไม่ใช่ `sync`

## Scheduler / Cron
- [ ] รัน `crontab -l` บนเซิร์ฟเวอร์ ต้องมี `* * * * * php /path/to/artisan schedule:run`
- [ ] ทดสอบ `php artisan inventory:scan-alerts` manual แล้วดู log ว่า success
- [ ] ตรวจ log cron (`/var/log/cron` หรือ systemd timer) ว่ามีรันในรอบ 24 ชม.

## Notifications
- [ ] ที่หน้า Admin ▸ ตั้งค่าระบบ กดปุ่ม "ทดสอบการแจ้งเตือน" และตรวจ In-App notification
- [ ] ยืนยันว่ากล่องจดหมายปลายทางได้รับอีเมลทดสอบ (มีหัวข้อ `[ทดสอบ]`)
- [ ] ตรวจใน LINE group/ห้องว่ามีข้อความ `[ทดสอบ] ระบบคลังสินค้า...`

## Import / Reports
- [ ] โหลดไฟล์ตัวอย่างสินค้าถูกต้อง (UPSERT) แล้วตรวจว่าสรุปเพิ่ม/อัปเดตถูกต้อง
- [ ] โหลดไฟล์ผิดพลาดเพื่อให้เกิด error.csv และดาวน์โหลดได้
- [ ] ตรวจหน้า รายงาน ▸ ใกล้หมดอายุ/สต็อกต่ำ/มูลค่าคลัง แสดงข้อมูลครบและ export CSV ได้

## Roles
- [ ] เข้าระบบด้วยบัญชี `staff` แล้วตรวจว่าไม่เห็นเมนู "จัดการผู้ใช้"
- [ ] ลองเปิดลิงก์ `/admin/users` ด้วยบัญชี non-admin ต้องถูกบล็อก (403)
- [ ] สร้างบัญชี viewer แล้วตรวจว่าเข้าได้เฉพาะหน้า Dashboard + รายงาน

## Audit / Logs
- [ ] ทำกิจกรรมสำคัญ (เช่น บันทึกการตั้งค่า) แล้วตรวจตาราง `activities` ว่ามีเรคคอร์ดใหม่
- [ ] ตรวจไฟล์ `storage/logs/laravel-*.log` ว่ามี log channel `daily` และ masking แล้ว
- [ ] ส่งออก log ไปปลายทางศูนย์กลาง (เช่น กำหนด syslog/agent) แล้วแนบหลักฐาน

## Backup / Restore
- [ ] รัน `php artisan inventory:backup` แล้วตรวจว่ามีไฟล์ใน `storage/app/backups`
- [ ] ดาวน์โหลดไฟล์สำรองล่าสุดจากเมนู Admin ▸ Backup และเก็บไว้ใน secure storage
- [ ] ลองกู้คืนบนเครื่อง staging (import database.json + ไฟล์) แล้วบันทึกขั้นตอน

## Security Headers
- [ ] ใช้ `curl -I https://natstock.kesug.com` แล้วตรวจ header: HSTS, X-Frame-Options, CSP
- [ ] ตรวจ CSP ผ่าน browser devtools ว่าไม่มี error/blocking ผิดปกติ
- [ ] บันทึกแผนการลบ `'unsafe-inline'` (timeline + ticket)

## Performance
- [ ] หลังดีพลอย รัน `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] เปิด `php artisan queue:work --queue=default --once` บนเซิร์ฟเวอร์ให้แน่ใจว่าคิวทำงานได้
- [ ] รวบรวม slow query จาก APM หรือ `mysqlslow.log` และทบทวนทุกสปรินต์
