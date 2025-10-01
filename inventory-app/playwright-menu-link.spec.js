
import { test, expect } from '@playwright/test';

// ตัวอย่าง: ตรวจสอบเมนู ลิงก์ และฟังก์ชันหลักของหน้าเว็บ


// ฟังก์ชันล็อกอิน
async function login(page) {
  await page.goto('http://localhost:8000/login');
  await page.fill('input[name="email"]', 'admin@example.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  // รอให้เข้าสู่ระบบสำเร็จ (อาจต้องปรับ selector ตามจริง)
  await page.waitForURL('http://localhost:8000/');
}

test.describe('ตรวจสอบเมนูและลิงก์', () => {
  test('ทุกเมนูและลิงก์ต้องนำไปยังหน้าที่ถูกต้อง', async ({ page }) => {
    await login(page);
    // ตัวอย่าง: ตรวจสอบเมนูหลัก
    await expect(page.locator('nav')).toBeVisible();
    await page.click('text=สินค้า');
    await expect(page).toHaveURL(/.*products/);
    await page.click('text=หมวดหมู่');
    await expect(page).toHaveURL(/.*categories/);
    // เพิ่มเมนูอื่น ๆ ตามต้องการ
  });
});

test.describe('ตรวจสอบฟังก์ชันหลักของแต่ละหน้า', () => {
  test('ค้นหาสินค้า', async ({ page }) => {
    await login(page);
    await page.goto('http://localhost:8000/products');
    await page.fill('input[name="search"]', 'ทดสอบ');
    await page.click('button[type="submit"]');
    await expect(page.locator('table')).toContainText('ทดสอบ');
  });

  test('เพิ่มสินค้า', async ({ page }) => {
    await login(page);
    await page.goto('http://localhost:8000/products/create');
    await page.fill('input[name="name"]', 'สินค้าใหม่');
    await page.fill('input[name="sku"]', 'SKU-NEW');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/.*products/);
    await expect(page.locator('table')).toContainText('สินค้าใหม่');
  });

  // เพิ่ม test สำหรับแก้ไข/ลบ/ฟอร์มอื่น ๆ ตามต้องการ
});
