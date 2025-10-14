# การปิดเสียงแจ้งเตือน Low Stock / Expiring ชั่วคราว

- **เวลาประมาณ**: 10 นาที
- **ใช้เมื่อ**: แจ้งเตือนซ้ำซ้อน, แจ้งผิดพลาดระหว่าง maintenance, หรือระหว่าง rollback

## Do
- แจ้งทีมจัดซื้อ/พยาบาลก่อนปิดทุกครั้งและระบุช่วงเวลาที่จะเปิดกลับ
- บันทึกค่าเดิมของการตั้งค่าก่อนปรับ (เช่น `low_stock_enabled=1`)
- ล้างเฉพาะ record ที่มี `payload_hash` ตรงกับแจ้งเตือนที่ต้องการปิด ไม่ลบทั้งตาราง

## Don’t
- อย่าปิดทั้งสองแจ้งเตือนพร้อมกันนานกว่า 1 รอบกะโดยไม่แจ้งผู้บริหาร
- อย่า truncate ตาราง `user_alert_states` เพราะทำให้ประวัติการ snooze หายหมด

## ขั้นตอนปิดแจ้งเตือนผ่าน UI
1. ไปที่ **Admin ▸ ตั้งค่าระบบ**
2. ปิดสวิตช์ `แจ้งเตือนสต็อกต่ำ` หรือ `แจ้งเตือนล็อตใกล้หมดอายุ`
3. กดบันทึก แล้วรัน `php artisan inventory:scan-alerts` เพื่อรีเฟรช snapshot

## ปิดผ่าน CLI (กรณีเข้า UI ไม่ได้)
```bash
php artisan tinker --execute="app('settings')->set('low_stock_enabled', '0')"
php artisan tinker --execute="app('settings')->set('expiring_enabled', '0')"
```

## การล้างสถานะผู้ใช้แบบเฉพาะเจาะจง
1. หา `payload_hash` จาก modal หรือจาก `app(AlertSnapshotService::class)->buildSnapshot()`
2. ลบเฉพาะแถวที่ตรงกับ hash และผู้ใช้เป้าหมาย
   ```sql
   DELETE FROM user_alert_states
   WHERE payload_hash = 'HASH-ตัวอย่าง'
     AND user_id = 123;
   ```
3. แจ้งผู้ใช้ให้รีเฟรช dashboard เพื่อตรวจสอบผล

## เปิดใช้งานอีกครั้ง
1. ตั้งค่ากลับเป็น `1` ผ่าน UI หรือคำสั่ง tinker ด้านบน
2. รัน `php artisan inventory:scan-alerts`
3. แจ้งทีมทุกคนให้รีเฟรช dashboard และยืนยันว่ามี modal กลับมาปกติ

> บันทึกวันที่/เวลาที่ปิดและเปิดแจ้งเตือนทุกครั้งใน incident log เพื่อใช้ประกอบการตรวจสอบย้อนหลัง
