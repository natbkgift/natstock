<?php
$home = getenv('HOME') ?: realpath(__DIR__ . '/../../..');
$target = rtrim($home, '/').'/natstock_app/inventory-app/public/index.php';

if (!file_exists($target)) {
    http_response_code(500);
    echo '<h1>natstock_app ยังไม่พร้อม</h1>';
    echo '<p>ไม่พบไฟล์บูต Laravel ที่ <code>' . htmlspecialchars($target) . '</code>.</p>';
    echo '<p>กรุณาตรวจสอบการตั้งค่า Git Deployment ใน hPanel และให้แน่ใจว่าโค้ดถูกโคลนไปที่ <code>~/natstock_app</code>.</p>';
    exit;
}

require $target;
