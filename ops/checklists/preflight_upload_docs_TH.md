# Preflight ก่อนอัปโหลดเอกสาร/สคริปต์ Ops

- [ ] ตรวจว่ามีการอัปโหลดเฉพาะไฟล์ใน `docs/` และ `ops/` เท่านั้น
- [ ] ยืนยันว่าไม่ได้แก้ไขโค้ดแอป, ไม่แตะ `deploy/*` และ `autoload.php`
- [ ] แนบ `docs/ops/production-audit-20251008.md` และเอกสาร runbook ทั้งหมด
- [ ] แนบสคริปต์ recovery (`ops/recovery/*`) ให้ครบทุกไฟล์
- [ ] ตรวจความถูกต้องของเช็กลิสต์ Go-Live (`ops/checklists/go_live_TH.md`)
- [ ] แนบบันทึกการทดสอบ `composer test` รอบสุดท้ายในรายงาน
- [ ] แจ้งทีม QA/PM ก่อนปล่อยเอกสารขึ้น repository กลาง
