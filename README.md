# inventory-app (Phase 1 — Bootstrap & Auth)

โปรเจกต์ระบบบริหารสต็อกสินค้าพัฒนาด้วย Laravel 11 (PHP 8.3) โดยเน้นภาษาไทยทั้งหมดสำหรับทีมงานในคลังสินค้า ชุดนี้คือ Phase 1 ที่เตรียมโครงสร้างพื้นฐาน ระบบยืนยันตัวตน และเมนูหลังบ้านเบื้องต้น

## ความต้องการระบบ
- PHP 8.3
- Composer 2
- SQLite 3 (ใช้สำหรับการทดสอบ/CI)
- Node.js 20+ (ใช้สำหรับจัดการ dependency ของ Vite หากจำเป็น)

## ขั้นตอนติดตั้งเบื้องต้น
1. ติดตั้ง dependency ของ PHP
   ```bash
   composer install
   ```
2. สร้างไฟล์สิ่งแวดล้อม
   ```bash
   cp .env.example .env
   ```
3. สร้างคีย์แอปและ migrate ฐานข้อมูล SQLite
   ```bash
   php artisan key:generate
   php artisan migrate
   php artisan db:seed
   ```
4. เริ่มเซิร์ฟเวอร์ทดสอบ
   ```bash
   php artisan serve
   ```
5. เข้าถึงระบบได้ที่ `http://127.0.0.1:8000` ด้วยบัญชีเริ่มต้น
   - อีเมล: `admin@example.com`
   - รหัสผ่าน: `password`

> Phase 1 ใช้ AdminLTE จาก CDN เพื่อให้โค้ดเบาและตั้งค่าเร็ว โดยจะปรับปรุง build tool ใน Phase ถัดไป

## โครงสร้างเมนูหลังบ้าน (ภาษาไทย)
- แดชบอร์ด — สรุปภาพรวม (placeholder)
- สินค้า — จัดการสินค้า (placeholder)
- หมวดหมู่ — จัดการหมวดหมู่ (placeholder)
- เคลื่อนไหวสต็อก — ประวัติ in/out (placeholder)
- นำเข้าไฟล์ — เตรียมรองรับการอัปโหลด (placeholder)
- รายงาน — รายงานต่าง ๆ (placeholder)

ทุกเมนูอยู่ภายใต้ prefix `/admin/*` และจำเป็นต้องเข้าสู่ระบบก่อนใช้งาน

## สิ่งที่ครอบคลุมใน Phase 1
- ตั้งค่า locale = `th`, timezone = `UTC`
- ระบบยืนยันตัวตนภาษาไทย (สมัคร, เข้าสู่ระบบ, ลืมรหัสผ่าน)
- กำหนดบทบาทผู้ใช้ `admin`, `staff`, `viewer` พร้อม Gate เบื้องต้น
- Layout หลักใช้ AdminLTE (CDN) พร้อม Breadcrumb และ Flash Message ภาษาไทย
- Seeder สร้างผู้ดูแลระบบเริ่มต้นและ log ภาษาไทย
- ตั้งค่า Laravel Pint (โหมดทดสอบ) และ Pest (Smoke test)
- GitHub Actions workflow รัน composer install → migrate → pint --test → pest

## แผน Phase ถัดไป (สรุป)
- Phase 2: โมเดลสินค้า/หมวดหมู่ + CRUD
- Phase 3: แดชบอร์ดและสิทธิ์การใช้งานเชิงลึก
- Phase 4: ระบบนำเข้าไฟล์และรายงาน
- Phase 5: ปรับปรุง UX/การพิมพ์รายงาน + ตรวจสอบคุณภาพขั้นสูง

## หมายเหตุ
- ยังไม่มีเนื้อหาเชิงลอจิกของเมนู (placeholder เท่านั้น)
- ไม่มีไฟล์ไบนารีใน repo; หากต้องใช้จะเพิ่มผ่าน Git LFS ใน Phase ถัดไป
- UI และข้อความทั้งหมดเป็นภาษาไทยเพื่อความเข้าใจง่ายของผู้ใช้งานภายใน
