# Feature Flags ที่เกี่ยวข้องกับ PR6

| Flag | ค่าเริ่มต้น | ครอบคลุม |
| --- | --- | --- |
| `INVENTORY_ENABLE_PRICE` (ไฟล์ `.env`) | `false` | ปิดช่องราคาในหน้า Product/Movement/Import และซ่อนคอลัมน์ราคาจากรายงาน/CSV ทั้งหมด |

## พฤติกรรมเมื่อปิด (ค่าเริ่มต้น)
- หน้า **สินค้า** จะมี banner แจ้งว่า "ระบบปิดการใช้งานราคาทุน/ราคาขายอยู่" และไม่แสดงช่อง `cost_price`/`sale_price`
- คำสั่งนำเข้าข้อมูลจะ strip ค่าราคาออก (แสดงในพรีวิวว่าเป็นคอลัมน์ ignored)
- รายงานทั้งหมด (รวม expiring/low-stock, export shortcuts) จะไม่มีคอลัมน์ราคาและไม่สามารถเรียกหน้า Valuation ได้ (404)

## วิธีเปิดกลับชั่วคราว
1. แก้ไฟล์ `.env` ให้ `INVENTORY_ENABLE_PRICE=true`
2. รัน `php artisan config:clear && php artisan config:cache`
3. ล้าง cache มุมมอง (`php artisan view:clear`) หาก UI ยังไม่อัปเดต
4. รีเฟรชหน้า **สินค้า** และ **รายงานมูลค่า** เพื่อตรวจว่ากลับมามีช่องราคาและ export แสดงคอลัมน์ครบ

## แผนตรวจสอบหลังเปิดใช้งานราคา
- สร้าง/แก้ไขสินค้า 1 รายการ แล้วตรวจว่าราคาบันทึกได้จริง (ค่าทศนิยมถูกต้อง)
- พรีวิว import ที่มีคอลัมน์ราคา ต้องไม่มีข้อความ ignored
- รายงาน **Valuation** (`/admin/reports/valuation`) ต้องดาวน์โหลด CSV ได้และมีคอลัมน์ราคาทุน/มูลค่ารวม

> หากต้องทดสอบระยะสั้นบนเครื่อง staging ให้ใช้ `php artisan tinker --execute="config(['inventory.enable_price' => true])"` แล้วทดสอบเฉพาะ session นั้น ๆ เพื่อหลีกเลี่ยงการแก้ `.env`
