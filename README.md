
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

---

## การรันชุดทดสอบ (ท้องถิ่น/CI)
1. ติดตั้ง dependency ด้วย `composer install` เพื่อให้มีไฟล์ `vendor/autoload.php`
2. เตรียม `.env` (เช่น `cp -n .env.example .env`) แล้วตั้งค่า database สำหรับรันเทสต์ (นิยมใช้ sqlite memory)
3. รัน `php artisan key:generate` และ `php artisan migrate --force`
4. สั่ง `php artisan test`

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
