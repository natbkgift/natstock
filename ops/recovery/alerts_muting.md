# การปิดเสียงแจ้งเตือน low stock / expiring ชั่วคราว

- **เวลาประมาณ**: 10 นาที

## Do
- แก้ไขค่าที่หน้า **Admin ▸ ตั้งค่าระบบ** หรือผ่าน tinker (`setting:set`)
- จดเวลาที่ปิดและผู้รับผิดชอบ พร้อมกำหนดเวลาปิดเสียงสิ้นสุด
- ล้าง record เฉพาะ payload ที่สร้างการแจ้งซ้ำ (ไม่ truncate ทั้งตาราง)

## Don't
- อย่าปิดทั้งสองแจ้งเตือนโดยไม่แจ้งทีมจัดซื้อ/พยาบาล
- อย่าลบ `user_alert_states` ทั้งหมดหากยังต้องการเก็บประวัติ snooze

## ขั้นตอนปิดแจ้งเตือน
1. ไปที่ **Admin ▸ ตั้งค่าระบบ**
2. ปิดสวิตช์ `แจ้งเตือนสต็อกต่ำ` หรือ `แจ้งเตือนล็อตใกล้หมดอายุ`
3. กดบันทึก แล้วรัน `php artisan inventory:scan-alerts` เพื่อรีเฟรชสถานะ
4. หากต้องปิดผ่าน CLI:
   ```php
   php artisan tinker --execute="app('settings')->set('low_stock_enabled', '0')"
   php artisan tinker --execute="app('settings')->set('expiring_enabled', '0')"
   ```

## การล้างสถานะผู้ใช้
```sql
DELETE FROM user_alert_states
WHERE payload_hash = '...'; -- ใช้ค่า hash จาก modal/snapshot
```

## เปิดใช้งานอีกครั้ง
- ตั้งค่ากลับเป็น `1`
- แจ้งทีมทุกคนให้รีเฟรช dashboard เพื่อรับ modal รอบใหม่
