# Runbook เหตุขัดข้อง (Incident)

ครอบคลุมเหตุฉุกเฉินหลักที่เกี่ยวข้องกับฟีเจอร์ A1–A5

## อาการและการตรวจเช็คเบื้องต้น
| อาการ | ขั้นตอนตรวจสอบ |
| --- | --- |
| HTTP 5xx / 4xx (เข้าเว็บไม่ได้) | 1) `curl -I https://<hostname>/ping` ต้องได้ `200`<br>2) ตรวจ error log ของเว็บเซิร์ฟเวอร์และ `storage/logs/laravel.log`<br>3) ตรวจ `APP_URL` และ proxy ว่าตั้ง HTTPS ถูกต้อง |
| storage เต็ม | `df -h` ที่โฮสต์, `php artisan storage:usage` (ถ้ามี), ตรวจ `storage/app/tmp` ว่ามีไฟล์ import ค้าง |
| cron / scheduler ไม่ทำงาน | `crontab -l` ต้องมี `* * * * * php /path/to/artisan schedule:run`<br>รัน `php artisan schedule:run --verbose --once` และดูผลลัพธ์/ warning |
| นำเข้า STRICT fail | ดู response summary, ตรวจ `storage/logs/laravel.log` บรรทัด error, เปิด `error.csv` ที่สร้างไว้ |
| ดัชนีฐานข้อมูลเสีย (lot/product) | รัน `php artisan tinker` ตรวจ `ProductBatch::count()` และ query ช้า, ตรวจ `show engine innodb status` |
| ป๊อปอัปแจ้งเตือนไม่ขึ้น | ตรวจตาราง `user_alert_states` ว่า payload มี snooze ค้าง, รัน `php artisan inventory:scan-alerts` |

## ขั้นตอนแก้ไขเร่งด่วนตามเหตุการณ์
1. **HTTP 5xx / 4xx**
   - รีสตาร์ทบริการเว็บ (Nginx/Apache) และ PHP-FPM ตามคู่มือเซิร์ฟเวอร์
   - เช็ก `.env` ให้ `APP_URL` ตรงโดเมนจริงและเป็น https
   - หากแอปยัง error ให้ deploy hotfix หรือสลับเป็นหน้า maintenance ชั่วคราวและแจ้งทีมสื่อสาร
2. **พื้นที่จัดเก็บเต็ม**
   - ลบไฟล์ใน `storage/app/tmp` ที่เก่ากว่า 7 วัน (ตรวจซ้ำว่าไม่มีการใช้งาน)
   - ย้าย backup เก่าออกจากเครื่อง หรือเพิ่ม space ชั่วคราว
   - บันทึกพื้นที่คงเหลือหลังแก้ไขและแจ้งทีมโครงสร้างพื้นฐาน
3. **cron / scheduler หยุดทำงาน**
   - รัน `php artisan schedule:run --verbose --once` เพื่อให้ job สำคัญทำงานทันที
   - ตรวจ `systemctl status cron` (หรือ `systemctl status laravel-scheduler.timer` ตามระบบ)
   - หลังแก้ไขให้บันทึกเวลาเริ่มเดินใหม่ใน incident log
4. **นำเข้า STRICT ล้มเหลว**
   - ยืนยันว่าไม่ commit ข้อมูลใหม่ (ระบบควร rollback อัตโนมัติ)
   - เปลี่ยนโหมดเป็น LENIENT ชั่วคราว และใช้งานตาม [restore_import_failures.md](../../ops/recovery/restore_import_failures.md)
   - เมื่อแก้ไขข้อมูลครบแล้วให้กลับไปใช้ STRICT และแจ้ง QA ตรวจสอบแถวที่ถูกแก้
5. **ดัชนีฐานข้อมูลเสียหรือข้อมูลล็อตไม่สมดุล**
   - ปิดรับ traffic ชั่วคราว
   - รวมยอดล็อตกลับไปสินค้าอ้างอิงตาม [rollback_A1_schema.md](../../ops/recovery/rollback_A1_schema.md)
   - ตรวจสอบความถูกต้องด้วยการรัน `php artisan backfill:product-batches` และเทียบยอดอีกครั้ง
6. **แจ้งเตือนรบกวนผู้ใช้ (modal เด้งซ้ำ)**
   - ปิดการแจ้งเตือนชั่วคราวตาม [alerts_muting.md](../../ops/recovery/alerts_muting.md)
   - ล้าง `user_alert_states` เฉพาะ payload_hash ที่ซ้ำ แล้วเปิดกลับเมื่อแก้ไขต้นเหตุ

## การกู้คืนและติดตามผล
- ทุกเหตุการณ์ต้องจดใน ITSM/Ticket พร้อมเวลาเริ่ม-สิ้นสุดและคนรับผิดชอบ
- หลังระบบกลับมาปกติ ให้รัน `php artisan test --filter=ImportExport` เฉพาะกรณี import มีปัญหา เพื่อตรวจสอบความพร้อม
- ตรวจสอบแดชบอร์ดและรายงาน expiring/low-stock ว่าแสดงผลได้ตามปกติ
- แจ้งสรุปเหตุการณ์ (root cause, แผนป้องกัน) ภายใน 24 ชั่วโมง

## ช่องทางสื่อสารทีม
- Slack `#natstock-ops` — แจ้งสถานะ, ETTR และการปิด incident
- โทรศัพท์หัวหน้ากะปฏิบัติการ: 08x-xxx-xxxx
- ส่งอีเมลสรุปให้ผู้บริหารเมื่อเกิดเหตุสำคัญ (Downtime > 15 นาที หรือมีผลกับการสั่งซื้อ)
