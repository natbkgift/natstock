# บันทึกตรวจสอบโปรดักชัน 2025-10-08

## สรุปย่อ
- โครงสร้างหลายล็อต (#A1) เปิดใช้งานครบ พร้อมล็อต `UNSPECIFIED` สำหรับสินค้าที่ไม่มีรหัสล็อตเดิม
- บริการเคลื่อนไหวสต็อกและ UI (#A2) รองรับรับเข้า/เบิก/ปรับยอดต่อล็อต พร้อมป้องกันยอดติดลบ
- ระบบปิดราคา (#A3) ตัดช่องราคาทั้ง UI/Export เมื่อ flag `INVENTORY_ENABLE_PRICE` = false
- รายงานและป๊อปอัปแจ้งเตือน (#A4) ทำงานแบบคงอยู่ต่อผู้ใช้ และ export CSV ไม่มีราคา
- นำเข้า/ส่งออกล็อต (#A5) รองรับพรีวิว 20 แถว, UPSERT/DELTA/SKIP, strict rollback + lenient error.csv

## เอกสารอ้างอิงภายใน
- [docs/ops/runbook_daily_TH.md](runbook_daily_TH.md)
- [docs/ops/incident_runbook_TH.md](incident_runbook_TH.md)
- [docs/ops/import_export_format_TH.md](import_export_format_TH.md)
- [docs/ops/alerts_and_reports_TH.md](alerts_and_reports_TH.md)
- [docs/ops/feature_flags_TH.md](feature_flags_TH.md)
- แผนฟื้นฟู: `ops/recovery/*`

## ประเด็นที่ต้องตรวจทุกดีพลอย
- ยืนยันว่า `php artisan backfill:product-batches` ทำงานครบและไม่มี product ติดลบ
- ตรวจ movement ล่าสุดว่ามีการบันทึก `batch_sub_sku` ถูกต้อง (รับเข้า/เบิก/ปรับ)
- ฟีเจอร์ราคายังปิดอยู่: หน้า Product/Create และ CSV รายงานต้องไม่ปรากฏฟิลด์ราคา
- Dashboard แสดงป๊อปอัปเมื่อมี alert ใหม่ (ทดสอบ mark-read, snooze)
- หน้า Import ต้องพรีวิว 20 แถวและแจ้งเตือนเมื่อเจอราคาในไฟล์

## เช็กลิสต์หลังทดสอบ
- [ ] รัน `composer test` ให้ผ่านทุกเคส
- [ ] ทดสอบ import โหมด STRICT และ LENIENT บน staging (แนบผลสรุป)
- [ ] ส่งออกรายงาน expiring/low-stock เพื่อตรวจ header ไม่มีราคา
- [ ] ตรวจ log `user_alert_states` หลัง snooze/mark-read ว่ามี 1 record ต่อ payload
- [ ] ยืนยัน backup ล่าสุดและทดสอบกู้คืนตัวอย่าง (ตาม ops/recovery/rollback_A1_schema.md)
