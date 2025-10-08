# Runbook เหตุขัดข้อง (Incident)

## อาการและการตรวจเช็คเบื้องต้น
| อาการ | ขั้นตอนตรวจสอบ |
| --- | --- |
| เว็บไซต์ไม่ตอบสนอง | 1) `ping <hostname>` ดู latency  2) `curl -I https://<hostname>/ping` ต้องได้ `200` พร้อม JSON |
| redirect ผิดเป็น http | ตรวจค่า `APP_URL` ใน `.env` และดู `config('app.url')` ผ่าน `php artisan tinker` |
| ป๊อปอัปแจ้งเตือนไม่ขึ้น | ตรวจตาราง `user_alert_states` ว่ามี record snooze/read หรือไม่, รัน `php artisan inventory:scan-alerts` |
| scheduler ไม่รัน | `crontab -l` ต้องมี `schedule:run`, ดู log `/var/log/cron` หรือ systemd timer |
| นำเข้าไฟล์ล้มเหลว | ดูไฟล์ `storage/app/tmp/error.csv` และ log `storage/logs/laravel*.log` |

## ขั้นตอนแก้ไขเร่งด่วน
1. **ระบบล่ม/ไม่ตอบสนอง**
   - รีสตาร์ทบริการเว็บ (Nginx/Apache) และ PHP-FPM ตามคู่มือเซิร์ฟเวอร์
   - ตรวจ health endpoint `/ping` อีกครั้งก่อนแจ้งผู้ใช้
2. **แจ้งเตือนรบกวนผู้ใช้**
   - เข้า `ops/recovery/alerts_muting.md` เพื่อปิดการเตือนชั่วคราว (ตั้งค่า `low_stock_enabled=0` / `expiring_enabled=0`)
   - ล้าง `user_alert_states` เฉพาะ payload ที่แจ้งซ้ำ
3. **นำเข้าข้อมูลผิดพลาดจำนวนมาก**
   - เปลี่ยนโหมดเป็น LENIENT (หน้า Import เลือก `lenient`)
   - ดาวน์โหลด `error.csv` ให้ทีมข้อมูลแก้ไขตาม `ops/recovery/restore_import_failures.md`

## การกู้คืนชั่วคราว
- หาก strict mode ล้มเหลว ให้ rollback ทั้งไฟล์ตาม `ops/recovery/restore_import_failures.md`
- จำเป็นต้องปิดการนำเข้า ราคาจะแสดง? ตรวจ `config('inventory.enable_price')` ให้สอดคล้อง
- หากล็อตคงเหลือไม่ตรง ให้รัน `php artisan backfill:product-batches` อีกครั้งหลังตรวจสอบข้อมูล

## ช่องทางสื่อสารทีม
- Slack #natstock-ops (ประกาศสถานะและ ETTR)
- โทรศัพท์หัวหน้ากะปฏิบัติการ: 08x-xxx-xxxx
- อัปเดต Ticket ในระบบ ITSM ทุกครั้งที่เกิดเหตุ พร้อมแนบ log/ผลแก้ไข
