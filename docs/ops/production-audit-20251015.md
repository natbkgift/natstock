# บันทึกตรวจสอบโปรดักชัน (Production Audit) — PR6 (2025-10-15)

## ภาพรวมการเปลี่ยนแปลง
- **A1**: โครงสร้างหลายล็อต + backfill SKU/LOT อัตโนมัติ
- **A2**: Service/ฟอร์มเคลื่อนไหวสต็อก รองรับการสร้าง/เลือกล็อต (FEFO)
- **A3**: ปิดการใช้งานราคา (Feature Flag `INVENTORY_ENABLE_PRICE=false`)
- **A4**: รายงานล็อตใกล้หมดอายุ + สต็อกต่ำ พร้อมป๊อปอัปคงอยู่ที่แดชบอร์ด
- **A5**: นำเข้า/ส่งออกล็อตแบบพรีวิว, UPSERT/DELTA/SKIP, รองรับ STRICT/LENIENT พร้อม error.csv

## อ้างอิงเอกสารปฏิบัติการ
| หัวข้อ | เอกสาร |
| --- | --- |
| งานประจำวัน/การตรวจสอบระบบ | [runbook_daily_TH.md](runbook_daily_TH.md) |
| เหตุขัดข้องและการกู้คืน | [incident_runbook_TH.md](incident_runbook_TH.md), [ops/recovery/*](../../ops/recovery) |
| Feature Flags | [feature_flags_TH.md](feature_flags_TH.md) |
| ฟอร์แมตนำเข้า/ส่งออก | [import_export_format_TH.md](import_export_format_TH.md) + ตัวอย่างใน [samples/](samples) |
| การแจ้งเตือน/รายงาน | [alerts_and_reports_TH.md](alerts_and_reports_TH.md) |
| เช็กลิสต์เตรียมเปิดใช้งาน | [ops/checklists/go_live_TH.md](../../ops/checklists/go_live_TH.md) |
| Preflight อัปโหลดเอกสาร | [ops/checklists/preflight_upload_docs_TH.md](../../ops/checklists/preflight_upload_docs_TH.md) |

## จุดที่ต้องจับตาพิเศษ
- ตรวจ **Backfill** ว่า `products.qty` เป็น 0 และยอดมาจาก `product_batches.qty` ทั้งหมด
- ยืนยันว่า **SKU/LOT** ยังคงรันต่อเนื่องหลัง import/reverse migration
- ตรวจแดชบอร์ดว่าป๊อปอัปแจ้งเตือนปรากฏเมื่อมีรายการใหม่ (payload_hash ต้องเปลี่ยนทุกครั้ง)
- ทดสอบ **Import LENIENT** ให้ gen `error.csv` และกู้คืนจาก [restore_import_failures.md](../../ops/recovery/restore_import_failures.md)
- สำรองคู่มือ rollback และแจ้งทีมเฝ้าระวังเรื่องการปิดเสียงแจ้งเตือนตาม [alerts_muting.md](../../ops/recovery/alerts_muting.md)

## Log การทดสอบก่อนอัปโหลด
- `php artisan test` — ผ่าน (sqlite, Pest)
- ตรวจสอบไฟล์ตัวอย่างนำเข้าที่ `docs/ops/samples/*`
- รัน `php artisan schedule:list` เพื่อยืนยัน cron job แสดงผลครบ

> บันทึกนี้จัดทำเพื่อใช้ในการตรวจสอบโปรดักชันรอบแรกของการเปิดใช้ฟีเจอร์หลายล็อต พร้อมเทสต์อัตโนมัติและเอกสารประกอบครบชุด
