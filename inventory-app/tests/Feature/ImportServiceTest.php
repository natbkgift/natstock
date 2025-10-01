<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

if (!function_exists('assertSameValue')) {
    function assertSameValue(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $text = $message !== '' ? $message : sprintf('Expected %s but received %s', var_export($expected, true), var_export($actual, true));
            throw new \AssertionError($text);
        }
    }

    function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new \AssertionError($message !== '' ? $message : 'Expected condition to be true.');
        }
    }

    function assertNotNull(mixed $value, string $message = ''): void
    {
        if ($value === null) {
            throw new \AssertionError($message !== '' ? $message : 'Expected value to be not null.');
        }
    }

    function assertNull(mixed $value, string $message = ''): void
    {
        if ($value !== null) {
            throw new \AssertionError($message !== '' ? $message : 'Expected value to be null.');
        }
    }

    function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new \AssertionError($message !== '' ? $message : sprintf('Failed asserting that "%s" contains "%s".', $haystack, $needle));
        }
    }
}

test('นำเข้าสินค้าใหม่และสร้าง movement ชนิด in', function (): void {
    $service = new ImportService();
    $file = FakeUploadedFile::make('import_new.csv', [
        ['sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active'],
        ['SKU001', 'ปลากระป๋อง', 'อาหารสด', '12', '25.50', '35.00', '2025-12-31', '5', 'ล็อตแรก', '1'],
    ]);

    $preview = $service->preview($file, 'UPSERT', true);

    assertSameValue(1, $preview['summary']['total_rows']);
    assertSameValue(0, $preview['summary']['error_rows']);
    assertTrue($preview['can_commit']);
    assertNull($preview['file_error']);

    $user = new User(['id' => 1, 'name' => 'Admin', 'role' => 'admin']);
    $result = $service->commit($preview['file_token'], 'UPSERT', true, $user);

    assertSameValue(1, $result['summary']['created']);
    assertSameValue(0, $result['summary']['updated']);
    assertSameValue(0, $result['summary']['errors']);
    assertNull($result['error_url']);

    $product = Product::query()->where('sku', 'SKU001')->first();
    $category = Category::query()->firstWhere('name', 'อาหารสด');
    $movements = StockMovement::all();

    assertNotNull($product);
    assertNotNull($category);
    assertSameValue(12, $product->qty);
    assertSameValue('25.50', number_format((float) $product->cost_price, 2, '.', ''));
    assertSameValue($category->getKey(), $product->category_id);
    assertSameValue(1, count($movements));
    assertSameValue('in', $movements[0]->type);
    assertSameValue(12, $movements[0]->qty);
    assertSameValue('import:create', $movements[0]->note);
    assertSameValue($user->getKey(), $movements[0]->actor_id);
});

test('อัปเดตสินค้าเดิมในโหมด UPSERT และบันทึก movement ปรับยอด', function (): void {
    $service = new ImportService();
    $category = Category::create(['name' => 'ขนม']);
    Product::create([
        'sku' => 'SKU002',
        'name' => 'มันฝรั่งทอด',
        'category_id' => $category->getKey(),
        'cost_price' => '10.00',
        'sale_price' => '15.00',
        'qty' => 5,
        'reorder_point' => 2,
        'is_active' => true,
    ]);

    $file = FakeUploadedFile::make('import_update.csv', [
        ['sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active'],
        ['SKU002', 'มันฝรั่งรสใหม่', 'ขนม', '9', '12.00', '18.00', '', '3', '', '1'],
    ]);

    $preview = $service->preview($file, 'UPSERT', true);
    $result = $service->commit($preview['file_token'], 'UPSERT', true, null);

    assertSameValue(1, $result['summary']['updated']);
    assertSameValue(0, $result['summary']['created']);

    $product = Product::query()->where('sku', 'SKU002')->first();
    $movements = StockMovement::all();

    assertNotNull($product);
    assertSameValue(1, count($movements));
    assertSameValue('in', $movements[0]->type);
    assertSameValue(4, $movements[0]->qty);
    assertStringContains('Δ+4', $movements[0]->note);
    assertSameValue('มันฝรั่งรสใหม่', $product->name);
    assertSameValue('12.00', number_format((float) $product->cost_price, 2, '.', ''));
    assertSameValue(9, $product->qty);
});

test('ข้ามสินค้าเมื่อเลือกโหมด SKIP', function (): void {
    $service = new ImportService();
    $category = Category::create(['name' => 'เครื่องเขียน']);
    Product::create([
        'sku' => 'SKU003',
        'name' => 'ปากกาน้ำเงิน',
        'category_id' => $category->getKey(),
        'cost_price' => '5.00',
        'qty' => 20,
        'reorder_point' => 5,
        'is_active' => true,
    ]);

    $file = FakeUploadedFile::make('import_skip.csv', [
        ['sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active'],
        ['SKU003', 'ปากกาน้ำเงิน', 'เครื่องเขียน', '30', '6.00', '8.00', '', '4', '', '1'],
    ]);

    $preview = $service->preview($file, 'SKIP', false);
    $result = $service->commit($preview['file_token'], 'SKIP', false, null);

    assertSameValue(1, $result['summary']['skipped']);
    assertSameValue(0, $result['summary']['updated']);

    $product = Product::query()->where('sku', 'SKU003')->first();
    assertNotNull($product);
    assertSameValue(20, $product->qty);
    assertSameValue([], StockMovement::all()->all());
});

test('ส่งออก error.csv เมื่อพบข้อมูลผิดพลาด', function (): void {
    $service = new ImportService();
    $file = FakeUploadedFile::make('import_error.csv', [
        ['sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active'],
        ['INVALID SKU', '', 'หมวด', '-1', '-5', 'x', '2025-99-99', '-2', '', '9'],
    ]);

    $preview = $service->preview($file, 'UPSERT', false);
    assertTrue(!$preview['can_commit']);

    $result = $service->commit($preview['file_token'], 'UPSERT', false, null);

    assertSameValue(1, $result['summary']['errors']);
    assertNotNull($result['error_url']);
});

test('สร้างหมวดหมู่ใหม่เมื่อเปิดใช้ auto create', function (): void {
    $service = new ImportService();
    $file = FakeUploadedFile::make('import_category.csv', [
        ['sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active'],
        ['SKU004', 'สบู่เหลว', 'ของใช้ส่วนตัว', '8', '45.00', '', '', '', '', '1'],
    ]);

    $preview = $service->preview($file, 'UPSERT', true);
    $service->commit($preview['file_token'], 'UPSERT', true, null);

    $category = Category::query()->firstWhere('name', 'ของใช้ส่วนตัว');
    assertNotNull($category);
});

test('แจ้งข้อผิดพลาดเมื่อหัวคอลัมน์ไม่ครบ', function (): void {
    $service = new ImportService();
    $file = FakeUploadedFile::make('invalid_header.csv', [
        ['sku', 'name', 'category'],
        ['SKU005', 'สินค้า', 'หมวด'],
    ]);

    $preview = $service->preview($file, 'UPSERT', true);

    assertNotNull($preview['file_error']);
    assertSameValue('', $preview['file_token']);
    assertTrue(!$preview['can_commit']);
});

class FakeUploadedFile
{
    public function __construct(private readonly string $path, private readonly string $originalName)
    {
    }

    public static function make(string $name, array $rows): self
    {
        $path = tempnam(sys_get_temp_dir(), 'import_');
        $handle = fopen($path, 'wb');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return new self($path, $name);
    }

    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    public function getClientOriginalExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    public function getRealPath(): string
    {
        return $this->path;
    }

    public function getPathname(): string
    {
        return $this->path;
    }

    public function getSize(): int
    {
        return filesize($this->path);
    }
}