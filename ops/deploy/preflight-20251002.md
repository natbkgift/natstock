# Preflight Upload Checklist (2025-10-02)

## วิธีอัปโหลดที่จะใช้
- ใช้วิธีเดียวกับ `deploy/README-DEPLOY-CPANEL.md` คืออัปโหลดไฟล์ผ่าน cPanel File Manager/FTP ไปยังโฟลเดอร์ `natstock/` (Laravel app) ใต้ public_html
- ไม่แตะ `deploy/*` หรือไฟล์แอปหลัก นำเข้าเฉพาะไฟล์เอกสารใน `ops/`

## รายการไฟล์ที่จะอัปโหลด
| ลำดับ | ไฟล์ | ขนาด (ไบต์) | เส้นทางปลายทาง |
|-------|------|-------------|------------------|
| 1 | ops/reports/production-audit-20251002.md | 14,566 | `/natstock/ops/reports/production-audit-20251002.md` |
| 2 | ops/checklists/production-readiness.md | 5,133 | `/natstock/ops/checklists/production-readiness.md` |
| 3 | ops/scripts/prod-smoketest.md | 5,324 | `/natstock/ops/scripts/prod-smoketest.md` |
| 4 | ops/issues/prod-audit-https-app-url.md | 2,099 | `/natstock/ops/issues/prod-audit-https-app-url.md` |
| 5 | ops/issues/prod-audit-env-hardening.md | 2,132 | `/natstock/ops/issues/prod-audit-env-hardening.md` |
| 6 | ops/issues/prod-audit-cron.md | 2,092 | `/natstock/ops/issues/prod-audit-cron.md` |
| 7 | ops/issues/prod-audit-import.md | 2,335 | `/natstock/ops/issues/prod-audit-import.md` |
| 8 | ops/issues/prod-audit-reports.md | 2,091 | `/natstock/ops/issues/prod-audit-reports.md` |
| 9 | ops/issues/prod-audit-user-policy.md | 2,104 | `/natstock/ops/issues/prod-audit-user-policy.md` |
|10 | ops/issues/prod-audit-logging.md | 2,019 | `/natstock/ops/issues/prod-audit-logging.md` |
|11 | ops/issues/prod-audit-backup.md | 1,950 | `/natstock/ops/issues/prod-audit-backup.md` |
|12 | ops/issues/prod-audit-security-headers.md | 2,030 | `/natstock/ops/issues/prod-audit-security-headers.md` |
|13 | ops/issues/prod-audit-performance.md | 1,965 | `/natstock/ops/issues/prod-audit-performance.md` |

- ขนาดรวมประมาณ 44.8 KB → ใช้เวลาอัปโหลด < 1 นาทีผ่าน cPanel

## Dry-run / ขั้นตอนจำลอง
1. ZIP โฟลเดอร์ `ops/` ภายในเครื่อง (`zip -r ops-20251002.zip ops/`) เพื่อง่ายต่อการอัปโหลดทีเดียว (ยังไม่อัปโหลดจริง)
2. ตรวจว่าไฟล์ zip มีเฉพาะโครงสร้าง `ops/...` ตามตารางด้านบน
3. เตรียมบัญชี cPanel/FTP และตรวจสิทธิ์การเขียนใน `natstock/ops/`
4. ตรวจพื้นที่ว่างบนโฮสต์ (ควรเหลือ > 10 MB)

## เช็กลิสต์ก่อนดีพลอยจริง
- [ ] ได้รับอนุมัติให้เผยแพร่เอกสารจากทีม Security/Compliance
- [ ] ยืนยันว่าระหว่างอัปโหลดไม่กระทบผู้ใช้งาน (ไฟล์เป็นเอกสารเท่านั้น)
- [ ] เตรียมแผน fallback (ลบไฟล์ออกหากพบข้อผิดพลาด)
- [ ] แจ้งทีมที่เกี่ยวข้องถึงหน้าที่หลังอัปโหลด (อ่านรายงาน, ดำเนินการ issue)
