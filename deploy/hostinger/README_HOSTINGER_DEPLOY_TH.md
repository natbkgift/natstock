# คู่มือดีพลอยผ่าน Hostinger hPanel Git สำหรับ natstock.net

## สถาปัตยกรรมการดีพลอยบน Hostinger
- แนะนำให้ตั้งค่า **Deployment path** ไปที่ `~/natstock_app` (ไม่ใช่ `public_html`)
- โครงสร้างโฮสหลังดีพลอยควรเป็นดังนี้:
  ```
  ~/natstock_app                       # โคลน Git ทั้งโปรเจกต์ (Laravel)
  ~/domains/natstock.net/public_html   # Document root ของโดเมน (เฉพาะไฟล์ public)
  ```
- เนื่องจากเป็น shared hosting ต้องแยกไฟล์บูต Laravel (`index.php`, `.htaccess`) ไว้ใน `public_html` แล้วชี้ย้อนกลับไปหาโค้ดหลักที่ `~/natstock_app/inventory-app/public`
- คงไฟล์ `deploy/hostinger/public_html/.htaccess` และ `deploy/hostinger/public_html/index.php` ใน repo เพื่อคัดลอกไปวางบนโฮสเท่านั้น (อย่าแก้ไขโดยไม่จำเป็น)

## ตั้งค่า Git Deployment ใน hPanel
1. เข้าสู่ระบบ hPanel → **Advanced ▸ Git**
2. กรอกค่าดังนี้
   - **Repository URL:** `https://github.com/natbkgift/natstock.git`
   - **Branch:** `main` หรือ `prod` (เลือกสาขาที่ต้องการปล่อยจริง)
   - **Deployment path:** `/home/<USER>/natstock_app`
3. กด **Deploy** (hPanel จะ clone/อัปเดตโค้ดมายังโฟลเดอร์ปลายทางโดยอัตโนมัติ)

## ขั้นตอน Manual ครั้งแรกหลังโค้ดถูก clone
1. เปิด **File Manager/SSH** แล้วคัดลอกไฟล์จาก `deploy/hostinger/public_html/*` ไปไว้ที่ `~/domains/natstock.net/public_html/`
2. สร้างไฟล์ `.env` ที่ `~/natstock_app/inventory-app/.env` โดยอ้างอิงจาก `~/natstock_app/inventory-app/.env.production.example` แล้วกรอกค่าจริง (ห้ามคอมมิตกลับ repo) และรัน `php artisan key:generate --force` หนึ่งครั้งเพื่อสร้าง `APP_KEY`
3. ผ่าน SSH รันสคริปต์ post-deploy:
   ```bash
   bash ~/natstock_app/deploy/hostinger/post-deploy.sh
   ```
4. ตรวจสอบระบบด้วย health check (`https://www.natstock.net/healthz`) หรือเรียก `deploy/hostinger/healthcheck.php` ชั่วคราวถ้าจำเป็น

## สคริปต์ Post-deploy
- สคริปต์ `deploy/hostinger/post-deploy.sh` จะรันคำสั่งสำคัญ: composer install, migrate, cache/optimize, storage:link และตั้ง permission
- ก่อนรันสคริปต์ให้ตรวจสอบว่า `.env` มี `APP_KEY` แล้ว หากยังไม่ตั้งค่าสคริปต์จะหยุดพร้อมข้อความแนะนำให้สร้างด้วย `php artisan key:generate --force`
- เมื่อสคริปต์เสร็จจะสร้าง log ที่ `storage/logs/deploy_YYYY-MM-DD_HHMM.log`
- หากต้องการรันด้วย Composer script ให้ใช้ `composer post-deploy-hostinger` (ระวัง: ใช้งานบนโฮสจริงเท่านั้น) ซึ่งเป็นตัวเรียก `deploy/hostinger/post-deploy.sh` โดยมี `php artisan down`/`up` ครอบ

## ตั้งค่า Cron/Schedule และ Queue
- Scheduler (ทุกนาที):
  ```cron
  * * * * * php /home/<USER>/natstock_app/inventory-app/artisan schedule:run >> /home/<USER>/logs/schedule.log 2>&1
  ```
- Queue (เชื่อมกับ `QUEUE_CONNECTION=database`):
  ```cron
  * * * * * php /home/<USER>/natstock_app/inventory-app/artisan queue:work --stop-when-empty >> /home/<USER>/logs/queue.log 2>&1
  ```

## SSL/HTTPS
- บังคับ HTTPS ด้วย `.htaccess` ที่อยู่ใน `public_html`
- อย่าลืมเปิดใช้งาน SSL Certificate ใน hPanel ก่อนคัดลอกไฟล์ .htaccess

## Health Check
- แนะนำให้ใช้ Route `/healthz` (ประกาศใน `routes/web.php`) เพื่อตรวจสอบสถานะแอป, DB และ cache
- ไฟล์ `deploy/hostinger/healthcheck.php` ให้ใช้เฉพาะกรณีจำเป็นต้องวางไฟล์ชั่วคราวใน `public_html`

## Rollback
1. ใน hPanel → Git เลือก commit/branch ที่ต้องการย้อนกลับแล้วกด **Deploy** อีกครั้ง
2. ถ้าการ deploy ก่อนหน้ามีการ migrate database ให้รัน:
   ```bash
   php artisan migrate:rollback --force
   ```
   > ระวังข้อมูลสูญหาย ให้สำรองข้อมูลก่อนทุกครั้ง
3. ตรวจสอบระบบด้วย `/healthz` หลัง rollback

## ข้อควรระวัง
- ห้ามคอมมิตไฟล์ `.env` หรือข้อมูลลับใด ๆ ลง repo
- อย่าตั้ง Document Root ให้ชี้ไปที่ `~/natstock_app/inventory-app/public` โดยตรง ให้ใช้ไฟล์บูตใน `public_html` เท่านั้น
- ตรวจสอบ version PHP/extension ให้ตรงกับ Laravel 11 (PHP ≥ 8.2) ผ่าน `php -v` และ `php -m`
