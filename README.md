
# ระบบคลังสินค้า/สต็อกยา (ภาษาไทย) — Laravel 11
**อัปเดต:** 2025-09-28

[![Open in GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://codespaces.new/{REPO_PATH}?quickstart=1)

ระบบนี้ออกแบบให้ใช้งานง่าย: แดชบอร์ดสรุป, จัดการสินค้า/หมวดหมู่, เคลื่อนไหวสต็อก (รับเข้า/เบิกออก/ปรับยอด), นำเข้าไฟล์ CSV/XLSX, รายงาน (ใกล้หมดอายุ/สต็อกต่ำ/มูลค่าสต็อก), การแจ้งเตือน และตั้งค่าระบบ (ภาษาไทยล้วน)

> แทนที่ `{REPO_PATH}` ด้วย `owner/repo` ของคุณ (เช่น `nat/inventory-app`) เพื่อให้ปุ่ม Codespaces ทำงาน

---

## การรันแบบเร็วใน GitHub Codespaces
1) คลิกปุ่ม **Open in GitHub Codespaces** ด้านบน (หรือ Code ▸ Codespaces ▸ Create)  
2) เปิด Terminal แล้วรัน:

```bash
composer install
cp -n .env.example .env
php artisan key:generate

# (แนะนำ sqlite สำหรับ Codespaces/CI)
mkdir -p database
touch database/database.sqlite

php artisan migrate --force
php artisan db:seed --force

# รันเว็บจริง
php artisan serve --host 0.0.0.0 --port 8000
```

Codespaces จะเปิดพอร์ต **8000** อัตโนมัติ → คลิก **Open in Browser** เพื่อดูเว็บจริง

> ต้องการสแกนแจ้งเตือนระหว่างเดโม ให้เปิด Terminal ใหม่แล้วรัน:
> ```bash
> php artisan schedule:work
> ```

---

## เมนูหลังบ้าน
- แดชบอร์ด • สินค้า • หมวดหมู่ • เคลื่อนไหวสต็อก • นำเข้าไฟล์ • รายงาน • ตั้งค่าระบบ (เฉพาะ Admin)

## Feature Flag: INVENTORY_ENABLE_PRICE
- ตั้งค่าได้ผ่านไฟล์ `.env` (ค่าเริ่มต้น = `false` สำหรับระบบจริง)
- เมื่อปิด (`false`): ระบบจะซ่อนทุกฟิลด์/รายงานเกี่ยวกับราคาทุน-ราคาขาย และเพิกเฉยข้อมูลราคาที่ส่งเข้ามา
- เมื่อเปิด (`true`): ฟังก์ชันราคาจะกลับมาพร้อมคอลัมน์ cost/sale price ในฟอร์ม/รายงาน/ไฟล์นำเข้า

## นำเข้าไฟล์ (CSV/XLSX)
- หัวคอลัมน์ที่รองรับ:
  - เมื่อเปิดราคา: `sku,name,category,qty,cost_price,sale_price,expire_date,reorder_point,note,is_active`
  - เมื่อปิดราคา: `sku,name,category,qty,expire_date,reorder_point,note,is_active`
- โหมดซ้ำ: **UPSERT** (อัปเดต + ปรับยอด) / **SKIP** (ข้ามแถวซ้ำ)
- พรีวิว 20 แรก + สรุปผลนำเข้า + error.csv ดาวน์โหลดได้

## รายงานหลัก
- **ใกล้หมดอายุ** (30/60/90 วัน) — เฉพาะสินค้าที่มีวันหมดอายุ  
- **สต็อกต่ำ** — สินค้าที่ `qty ≤ จุดสั่งซื้อซ้ำ`  
- **มูลค่าสต็อก** — รวมยอด `qty * ราคาทุน` + แสดงยอดรวมท้ายตาราง (ปรากฏเมื่อเปิดฟีเจอร์ราคา)
- ทุกหน้ามีปุ่ม **ส่งออก CSV**

## เอกสาร/เช็กลิสต์
- ถ้ามีโฟลเดอร์ **`docs/`**: เปิด GitHub Pages ที่ **Settings ▸ Pages** (Branch = `main`, Folder = `/docs`) เพื่อแสดงเอกสารเป็นเว็บ
- แนะนำไฟล์: `docs/index.md` (คู่มือรวม), `docs/checklists/*.csv` (เช็กลิสต์)

---

## การดีพลอยจริง (สรุปย่อ)
- **สภาพแวดล้อม:** PHP 8.3 + MySQL 8 / Postgres 15+
- **สิ่งที่ต้องตั้งค่า:** `APP_KEY`, `APP_URL`, MAIL_*, (ถ้าใช้) `LINE_NOTIFY_TOKEN`
- **Cron Scheduler:**
  ```
  * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
  ```
- **สิทธิ์โฟลเดอร์:** `storage/`, `bootstrap/cache/`
- **สำรองข้อมูล:** ตั้งอัตโนมัติรายวัน + เก็บ 7 ชุดล่าสุด + ทดสอบกู้คืนรายเดือน

## Deploy บน Hostinger (hPanel Git)
รายละเอียดฉบับเต็มอยู่ใน `deploy/hostinger/README_HOSTINGER_DEPLOY_TH.md` ส่วนนี้สรุปขั้นตอนหลัก:

1. **โครงสร้างโฮส**
   - ตั้งค่า Git Deployment ให้โคลนโค้ดไปที่ `/home/<USER>/natstock_app`
   - Document root (`~/domains/natstock.net/public_html`) มีเฉพาะไฟล์จาก `deploy/hostinger/public_html/` ซึ่งจะชี้เข้าสู่ `~/natstock_app/inventory-app/public`
2. **ตั้งค่า hPanel ▸ Advanced ▸ Git**
   - Repository: `https://github.com/natbkgift/natstock.git`
   - Branch: `main` หรือ `prod`
   - Deployment path: `/home/<USER>/natstock_app`
   - กด **Deploy** เพื่อ clone หรืออัปเดตรุ่นล่าสุด
3. **ขั้นตอน Manual หลัง deploy ครั้งแรก**
   - คัดลอกไฟล์ `deploy/hostinger/public_html/.htaccess` และ `index.php` ไปไว้ใน `~/domains/natstock.net/public_html/`
   - สร้างไฟล์ `.env` จาก `~/natstock_app/inventory-app/.env.production.example` แล้วกรอกค่าจริง (ห้ามคอมมิต) จากนั้นรัน `php artisan key:generate --force` หนึ่งครั้งเพื่อสร้าง `APP_KEY`
   - SSH แล้วรัน `bash ~/natstock_app/deploy/hostinger/post-deploy.sh`
   - ตรวจสุขภาพระบบผ่าน `https://www.natstock.net/healthz`
4. **สคริปต์ Post-deploy**
   - สคริปต์ `deploy/hostinger/post-deploy.sh` รัน composer install, migrate, cache, storage:link และตั้ง permission โดยมี log ที่ `storage/logs/deploy_*.log`
   - สคริปต์จะตรวจสอบว่า `.env` มี `APP_KEY` แล้วเท่านั้น หากยังไม่ได้สร้างให้ทำขั้นตอน `php artisan key:generate --force` ด้วยตนเองก่อน
   - สามารถใช้ `composer post-deploy-hostinger` แทนได้ (เฉพาะบนโฮสจริงเท่านั้น) ซึ่งเป็นตัวเรียก `deploy/hostinger/post-deploy.sh` โดยครอบด้วย `php artisan down`/`up`
5. **ตั้ง Cron/Queue**
   ```cron
   * * * * * php /home/<USER>/natstock_app/inventory-app/artisan schedule:run >> /home/<USER>/logs/schedule.log 2>&1
   * * * * * php /home/<USER>/natstock_app/inventory-app/artisan queue:work --stop-when-empty >> /home/<USER>/logs/queue.log 2>&1
   ```
6. **Health Check & SSL**
   - ใช้ Route `/healthz` ที่ bundle มากับแอป เพื่อตรวจ env/db/cache
   - `.htaccess` บังคับ HTTPS (ต้องเปิด SSL ใน hPanel ให้เรียบร้อย)
7. **Rollback**
   - ใน hPanel เลือก commit/branch ที่ต้องการแล้วกด Deploy อีกครั้ง
   - หากต้องย้อน database ให้ใช้ `php artisan migrate:rollback --force` โดยสำรองข้อมูลก่อน
8. **Pre-deploy Checklist ก่อนกด Deploy**
   - ✅ DNS/SSL ของ `natstock.net` พร้อมใช้งาน
   - ✅ ตั้งค่า `.env` ที่ `~/natstock_app/inventory-app` ครบทุกค่า (ไม่มีการคอมมิต)
   - ✅ ตรวจ `php -v` และ extension ให้ตรงกับ Laravel 11 (PHP ≥ 8.2 พร้อม ext พื้นฐาน)
   - ✅ โฟลเดอร์ `storage/` และ `bootstrap/cache/` เขียนได้
   - ✅ หลัง Deploy รัน `bash ~/natstock_app/deploy/hostinger/post-deploy.sh` หรือ `composer post-deploy-hostinger`

---

## การรันชุดทดสอบ (ท้องถิ่น/CI)
1. ติดตั้ง dependency ด้วย `composer install` เพื่อให้มีไฟล์ `vendor/autoload.php`
2. เตรียม `.env` (เช่น `cp -n .env.example .env`) แล้วตั้งค่า database สำหรับรันเทสต์ (นิยมใช้ sqlite memory)
3. รัน `php artisan key:generate` และ `php artisan migrate --force`
4. สั่ง `php artisan test`
   - หรือใช้ `composer test` (ต้องรัน `composer install` ให้เรียบร้อยก่อนเพื่อดึง Pest CLI)

> หากข้ามขั้นตอน `composer install` จะทำให้คำสั่งเทสต์ล้มเหลวเพราะหา `vendor/autoload.php` ไม่เจอ

---

## สคริปต์อัปเดตลิงก์ Codespaces อัตโนมัติ (ทางเลือก)
รันในเครื่อง/CI เพื่อแทนที่ `{REPO_PATH}` ด้วย owner/repo จริง:
```bash
repo_path="$(git remote get-url origin | sed -E 's#(git@github.com:|https://github.com/)##; s/\.git$//')"
sed -i.bak "s#{REPO_PATH}#${repo_path}#g" README.md
```

---

## ใบอนุญาต
กำหนด License ตามที่องค์กรต้องการ (เช่น MIT) แล้วใส่ไฟล์ `LICENSE` ที่รากโปรเจกต์
