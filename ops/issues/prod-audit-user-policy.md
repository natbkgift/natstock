# [Prod Audit] ย้าย UserPolicy ให้อยู่ใน PSR-4 path

- **บริบท:** `AuthServiceProvider` ลงทะเบียน `App\Policies\UserPolicy` แต่ไฟล์จริงอยู่ใน `resources/views/auth/UserPolicy.php`
- **ความเสี่ยง:** Autoloader ไม่พบคลาส → อาจเกิด fatal error เมื่อบู๊ตระบบ หรือ policy ไม่ทำงาน ทำให้ผู้ใช้ที่ไม่ใช่ admin เข้าถึงเมนูจัดการผู้ใช้ได้
- **วิธีตรวจ:**
  1. รัน `php artisan tinker --execute="class_exists(\\App\\Policies\\UserPolicy)"`
  2. ตรวจสิทธิ์บัญชี staff/viewer ว่าเข้าถึง `/admin/users` ไม่ได้
  3. ดู log/error หากมี message `Class "App\Policies\UserPolicy" not found`
- **วิธีแก้ (ไม่แตะโค้ด):**
  - ระหว่างรอแก้ไข ให้จำกัดสิทธิ์ผ่าน web server หรือ manual guard (ตรวจ role ก่อนแสดงเมนู)
  - ห้ามแจกจ่ายลิงก์ `/admin/users` ให้ผู้ใช้ non-admin
- **วิธีแก้ (แตะโค้ดภายหลัง):**
  - ย้ายไฟล์ไป `app/Policies/UserPolicy.php` หรือสร้างไฟล์ใหม่แล้วลบไฟล์เดิมใน `resources`
  - รัน `composer dump-autoload`
  - เพิ่มการทดสอบอัตโนมัติครอบคลุม policy (`UserPolicyTest`)
- **DoD:**
  - คำสั่ง tinker ด้านบนตอบ `true`
  - บัญชี staff/viewer ถูกปฏิเสธการเข้าถึงเมนูผู้ใช้
  - ไม่มีไฟล์ PHP ที่เป็น business logic ค้างอยู่ใน `resources/views`
- **Labels:** `audit`, `production`, `security`, `ops`
