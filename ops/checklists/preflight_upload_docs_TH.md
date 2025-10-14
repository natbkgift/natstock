# Preflight ก่อนอัปโหลดเอกสาร/สคริปต์ Ops (PR6)

- [ ] ตรวจว่าการเปลี่ยนแปลงอยู่ภายใต้โฟลเดอร์ `docs/` และ `ops/` เท่านั้น
- [ ] ยืนยันว่าไม่มีการแก้ไขโค้ดแอป, ไม่แตะ `deploy/*`, `autoload.php`, หรือไฟล์ config runtime
- [ ] แนบไฟล์ `docs/ops/production-audit-20251015.md` และเอกสารย่อยใน `docs/ops/*.md` ครบถ้วน
- [ ] มีตัวอย่างไฟล์นำเข้าใน `docs/ops/samples/` (อย่างน้อย `sample_import_batches.csv`, `sample_import_batches_delta.csv`)
- [ ] สคริปต์กู้คืนใน `ops/recovery/` ครบ 3 ไฟล์ (rollback, restore import, mute alerts)
- [ ] เช็กลิสต์ Go-Live (`ops/checklists/go_live_TH.md`) และ Preflight ฉบับนี้ได้รับการอัปเดตล่าสุด
- [ ] เก็บหลักฐานการรัน `php artisan test` และแนบผลในบันทึกส่งมอบ
- [ ] แจ้งทีม QA/PM ก่อน merge หรืออัปโหลดสู่ repository กลาง
