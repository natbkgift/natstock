# เช็กลิสต์ Go-Live ระบบหลายล็อต

- [ ] ตั้งค่า `.env` ให้ `APP_URL` เป็น https และตรวจใบรับรองบนเบราว์เซอร์
- [ ] ยืนยัน `APP_DEBUG=false` และ `SESSION_SECURE_COOKIE=true` ผ่าน `php artisan tinker`
- [ ] ตรวจว่า cron `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1` ทำงาน (ดู log ล่าสุด)
- [ ] ตรวจค่า `expiring_days` ที่หน้า ตั้งค่าระบบ ว่าตรงตามนโยบายองค์กร
- [ ] ทดสอบ import โหมด **STRICT** ผ่านไฟล์ตัวอย่าง (ต้อง rollback ทั้งไฟล์เมื่อมี error)
- [ ] ทดสอบ import โหมด **LENIENT** ให้สร้าง `error.csv` เมื่อมีแถวผิด
- [ ] ตรวจรายงาน expiring/low-stock ว่า `qty_total` และ `expire_date` ตรงกับข้อมูลทดสอบ
- [ ] เปิด Dashboard ด้วย 2 ผู้ใช้เพื่อตรวจป๊อปอัปคงอยู่ per-user
- [ ] ตรวจสอบ backup ล่าสุด (`php artisan inventory:backup`) และทดสอบกู้คืนบางส่วนบน staging
- [ ] รัน `php artisan config:cache && php artisan route:cache && php artisan view:cache` หลัง deploy
