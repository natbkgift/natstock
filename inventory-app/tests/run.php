<?php
putenv('DB_DATABASE='.__DIR__.'/database.sqlite');
@unlink(__DIR__.'/database.sqlite');

require __DIR__.'/../bootstrap/autoload.php';

use App\Models\Product;
use App\Services\ImportService;
use App\Services\ProductService;
use App\Services\StockService;
use App\Support\Database;

require __DIR__.'/../scripts/migrate.php';

function resetDatabase(): void {
    $pdo = Database::connection();
    $pdo->exec('DELETE FROM stock_movements');
    $pdo->exec('DELETE FROM products');
    $pdo->exec('DELETE FROM categories');
    $pdo->exec('DELETE FROM users');
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('users','categories','products','stock_movements')");
}

function seedUsers(): void {
    $pdo = Database::connection();
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (:name, :email, :password, :role, :created_at, :updated_at)');
    $stmt->execute([
        'name' => 'ผู้ดูแลระบบ',
        'email' => 'admin@example.com',
        'password' => password_hash('password', PASSWORD_BCRYPT),
        'role' => 'admin',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

$tests = [];
function test(string $name, callable $fn) {
    global $tests;
    $tests[] = [$name, $fn];
}

function assertTrue($condition, string $message = 'ค่าที่ตรวจสอบต้องเป็นจริง'): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertEquals($expected, $actual, string $message = 'ค่าที่ตรวจสอบไม่ตรงกัน'): void {
    if ($expected != $actual) {
        throw new RuntimeException($message.' (คาดว่า '.var_export($expected, true).' แต่ได้ '.var_export($actual, true).')');
    }
}

test('สร้างและแก้ไขสินค้า', function () {
    resetDatabase();
    seedUsers();
    $pdo = Database::connection();
    $pdo->exec("INSERT INTO categories (name, note, is_active, created_at, updated_at) VALUES ('ทดสอบ', '', 1, datetime('now'), datetime('now'))");
    $categoryId = (int) $pdo->lastInsertId();

    $service = new ProductService();
    $id = $service->create([
        'sku' => 'T001',
        'name' => 'สินค้า A',
        'note' => '',
        'category_id' => $categoryId,
        'cost_price' => 10,
        'sale_price' => 20,
        'expire_date' => date('Y-m-d', strtotime('+30 days')),
        'reorder_point' => 5,
        'is_active' => 1,
        'quantity' => 15,
    ]);

    $product = Product::find($id);
    assertEquals('สินค้า A', $product->name);
    $service->update($id, [
        'sku' => 'T001',
        'name' => 'สินค้า B',
        'note' => 'แก้ไข',
        'category_id' => $categoryId,
        'cost_price' => 12,
        'sale_price' => 22,
        'expire_date' => date('Y-m-d', strtotime('+40 days')),
        'reorder_point' => 4,
        'is_active' => 1,
        'quantity' => 20,
    ]);
    $product = Product::find($id);
    assertEquals('สินค้า B', $product->name);
    assertEquals('แก้ไข', $product->note);
});

test('นำเข้าข้อมูลโหมด UPSERT และ SKIP', function () {
    resetDatabase();
    seedUsers();
    $pdo = Database::connection();
    $pdo->exec("INSERT INTO categories (name, note, is_active, created_at, updated_at) VALUES ('เริ่มต้น', '', 1, datetime('now'), datetime('now'))");
    $categoryId = (int) $pdo->lastInsertId();

    $service = new ImportService();
    $csvPath = __DIR__.'/import.csv';
    $rows = [
        ['sku','name','category','qty','cost_price','sale_price','expire_date','reorder_point','note','is_active'],
        ['S001','สินค้า 1','เริ่มต้น','10','5','7',date('Y-m-d', strtotime('+10 days')),'2','หมายเหตุ','1'],
        ['S001','สินค้า 1 ปรับ','เริ่มต้น','12','6','8',date('Y-m-d', strtotime('+12 days')),'3','หมายเหตุ2','1'],
    ];
    $fp = fopen($csvPath, 'w');
    foreach ($rows as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);

    $result = $service->import($csvPath, 'upsert', true, 1);
    assertEquals(2, $result['success']);
    $product = Product::findBySku('S001');
    assertEquals('สินค้า 1 ปรับ', $product->name);
    assertEquals(12, $product->quantity);

    $resultSkip = $service->import($csvPath, 'skip', true, 1);
    assertEquals(0, $resultSkip['success']);
    assertEquals(2, $resultSkip['skipped']);

    unlink($csvPath);
});

test('ตรวจสอบสต็อกต่ำและใกล้หมดอายุ', function () {
    resetDatabase();
    seedUsers();
    $pdo = Database::connection();
    $now = date('Y-m-d H:i:s');
    $pdo->exec("INSERT INTO categories (name, note, is_active, created_at, updated_at) VALUES ('หมวด A', '', 1, '$now', '$now')");
    $cat = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO products (sku, name, note, category_id, cost_price, sale_price, expire_date, reorder_point, is_active, quantity, created_at, updated_at) VALUES (:sku,:name,:note,:category_id,:cost_price,:sale_price,:expire_date,:reorder_point,:is_active,:quantity,:created_at,:updated_at)');
    $stmt->execute([
        'sku' => 'E001',
        'name' => 'สินค้าหมดอายุ',
        'note' => '',
        'category_id' => $cat,
        'cost_price' => 5,
        'sale_price' => 8,
        'expire_date' => date('Y-m-d', strtotime('+5 days')),
        'reorder_point' => 10,
        'is_active' => 1,
        'quantity' => 9,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $stmt->execute([
        'sku' => 'E002',
        'name' => 'สินค้าปกติ',
        'note' => '',
        'category_id' => $cat,
        'cost_price' => 5,
        'sale_price' => 8,
        'expire_date' => date('Y-m-d', strtotime('+120 days')),
        'reorder_point' => 5,
        'is_active' => 1,
        'quantity' => 20,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $products = Product::withCategory();
    $lowStock = array_filter($products, fn($p) => (int) $p['quantity'] <= (int) $p['reorder_point']);
    assertEquals(1, count($lowStock));
    $today = new DateTime('now', new DateTimeZone('UTC'));
    $expiring = array_filter($products, function ($p) use ($today) {
        if (!$p['expire_date']) { return false; }
        $expire = new DateTime($p['expire_date'], new DateTimeZone('UTC'));
        if ($expire < $today) { return false; }
        return $today->diff($expire)->days <= 30;
    });
    assertEquals(1, count($expiring));
});

test('การปรับยอดต้องสร้าง movement adjust', function () {
    resetDatabase();
    seedUsers();
    $pdo = Database::connection();
    $now = date('Y-m-d H:i:s');
    $pdo->exec("INSERT INTO categories (name, note, is_active, created_at, updated_at) VALUES ('หมวด B', '', 1, '$now', '$now')");
    $cat = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO products (sku, name, note, category_id, cost_price, sale_price, expire_date, reorder_point, is_active, quantity, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute(['A001','สินค้า', '', $cat, 5, 7, null, 5, 1, 10, $now, $now]);
    $product = Product::findBySku('A001');

    $service = new StockService();
    $service->move($product->id, 'adjust', 15, 'ปรับ', 1, date('Y-m-d H:i:s'));
    $updated = Product::find($product->id);
    assertEquals(15, $updated->quantity);
    $movement = Database::connection()->query('SELECT * FROM stock_movements')->fetch(PDO::FETCH_ASSOC);
    assertEquals('adjust', $movement['type']);
    assertTrue((int) $movement['amount'] >= 0);
});

test('สร้างไฟล์เทมเพลตสำหรับการนำเข้า', function () {
    $templateDir = __DIR__.'/../public/templates';
    $xlsxPath = $templateDir.'/product_template.xlsx';
    if (file_exists($xlsxPath)) {
        unlink($xlsxPath);
    }

    require __DIR__.'/../scripts/generate_templates.php';

    assertTrue(file_exists($xlsxPath), 'ต้องสร้างไฟล์ XLSX สำเร็จ');
    assertTrue(filesize($xlsxPath) > 0, 'ไฟล์ XLSX ต้องมีข้อมูล');
});

$total = count($tests);
$passed = 0;
foreach ($tests as [$name, $fn]) {
    try {
        $fn();
        echo "[ผ่าน] $name\n";
        $passed++;
    } catch (Throwable $e) {
        echo "[ล้มเหลว] $name: ".$e->getMessage()."\n";
    }
}

echo "สรุปผ่าน $passed / $total การทดสอบ\n";
