<?php
require __DIR__.'/../../bootstrap/autoload.php';

use App\Support\Database;

$pdo = Database::connection();

$now = date('Y-m-d H:i:s');

$pdo->exec("DELETE FROM users");
$pdo->exec("DELETE FROM categories");
$pdo->exec("DELETE FROM products");
$pdo->exec("DELETE FROM stock_movements");

$users = [
    ['name' => 'ผู้ดูแลระบบ', 'email' => 'admin@example.com', 'password' => password_hash('password', PASSWORD_BCRYPT), 'role' => 'admin'],
    ['name' => 'พนักงาน', 'email' => 'staff@example.com', 'password' => password_hash('password', PASSWORD_BCRYPT), 'role' => 'staff'],
    ['name' => 'ผู้ชม', 'email' => 'viewer@example.com', 'password' => password_hash('password', PASSWORD_BCRYPT), 'role' => 'viewer'],
];

$stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (:name, :email, :password, :role, :created_at, :updated_at)');
foreach ($users as $user) {
    $stmt->execute(array_merge($user, ['created_at' => $now, 'updated_at' => $now]));
}

$categories = [
    ['name' => 'ยาแก้ปวด', 'note' => 'หมวดหมู่ทั่วไป', 'is_active' => 1],
    ['name' => 'วิตามิน', 'note' => 'วิตามินและอาหารเสริม', 'is_active' => 1],
];

$stmt = $pdo->prepare('INSERT INTO categories (name, note, is_active, created_at, updated_at) VALUES (:name, :note, :is_active, :created_at, :updated_at)');
foreach ($categories as $category) {
    $stmt->execute(array_merge($category, ['created_at' => $now, 'updated_at' => $now]));
}

$categoryIds = $pdo->query('SELECT id FROM categories')->fetchAll(PDO::FETCH_COLUMN);

$products = [
    ['sku' => 'P001', 'name' => 'พาราเซตามอล 500mg', 'note' => '', 'category_id' => $categoryIds[0], 'cost_price' => 2.5, 'sale_price' => 5, 'expire_date' => date('Y-m-d', strtotime('+6 months')), 'reorder_point' => 50, 'is_active' => 1, 'quantity' => 120],
    ['sku' => 'P002', 'name' => 'วิตามินซี 1000mg', 'note' => '', 'category_id' => $categoryIds[1], 'cost_price' => 3.5, 'sale_price' => 7, 'expire_date' => date('Y-m-d', strtotime('+3 months')), 'reorder_point' => 30, 'is_active' => 1, 'quantity' => 40],
];

$stmt = $pdo->prepare('INSERT INTO products (sku, name, note, category_id, cost_price, sale_price, expire_date, reorder_point, is_active, quantity, created_at, updated_at) VALUES (:sku, :name, :note, :category_id, :cost_price, :sale_price, :expire_date, :reorder_point, :is_active, :quantity, :created_at, :updated_at)');
foreach ($products as $product) {
    $stmt->execute(array_merge($product, ['created_at' => $now, 'updated_at' => $now]));
}

echo "seeding completed\n";
