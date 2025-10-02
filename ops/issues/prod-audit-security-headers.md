# [Prod Audit] ปรับปรุง Security Headers และ CSP

- **บริบท:** Middleware `SecurityHeaders` เพิ่ม X-Content-Type-Options, X-Frame-Options, Referrer-Policy, HSTS (เฉพาะ HTTPS) และ CSP ที่ยังมี `'unsafe-inline'`
- **ความเสี่ยง:** การคง `'unsafe-inline'` เพิ่มโอกาส XSS, ไม่มี `Permissions-Policy` ทำให้ควบคุมฟีเจอร์เบราว์เซอร์ไม่ได้เต็มที่
- **วิธีตรวจ:**
  1. ใช้ `curl -I https://natstock.kesug.com` และสแกน header ผ่าน securityheaders.com
  2. ตรวจหน้าเว็บว่ามี inline script/style ที่ต้องแก้หรือไม่
  3. ทดสอบการเปิดเว็บผ่าน HTTP ว่าถูก redirect และ HSTS แคชแล้ว
- **วิธีแก้ (ไม่แตะโค้ด):**
  - ทำ inventory ของ inline script/style, วางแผนย้ายไปไฟล์แยก
  - กำหนดค่าบน web server ให้ redirect HTTP และเปิด HSTS preload หากพร้อม
- **วิธีแก้ (แตะโค้ดภายหลัง):**
  - ปรับ CSP เพื่อลบ `'unsafe-inline'`, เพิ่ม nonce หรือใช้ Laravel Mix/Vite inject
  - เพิ่ม header `Permissions-Policy`, `Cross-Origin-Embedder-Policy`, `Cross-Origin-Opener-Policy`
  - เพิ่ม automated test ตรวจ header หลังดีพลอย
- **DoD:**
  - Security header scan ได้เกรด A หรือสูงกว่า
  - ไม่มี inline script/style สำคัญที่ต้อง whitelist
  - เอกสารอธิบายวิธีอัปเดต CSP เมื่อเพิ่ม asset ใหม่
- **Labels:** `audit`, `production`, `security`, `ops`
