<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    cleanupTmpDirectory();
    StockMovement::query()->delete();
});

afterEach(function (): void {
    cleanupTmpDirectory();
});

function cleanupTmpDirectory(): void
{
    $tmpDir = storage_path('app/tmp');
    if (!is_dir($tmpDir)) {
        return;
    }

    foreach (scandir($tmpDir) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        @unlink($tmpDir . DIRECTORY_SEPARATOR . $file);
    }
}

function makeImportCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'import_');
    $handle = fopen($path, 'wb');
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return $path;
}

function removeStoredToken(?string $token): void
{
    if ($token === null || $token === '') {
        return;
    }

    $path = storage_path('app/tmp/' . $token);
    if (is_file($path)) {
        @unlink($path);
    }
}

it('previews first 20 rows with validation highlights', function (): void {
    $service = new ImportService();

    $header = ['sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active'];
    $rows = [$header];

    for ($i = 1; $i <= 22; $i++) {
        $rows[] = [
            sprintf('SKU-%03d', $i),
            'สินค้า ' . $i,
            'หมวดนำเข้า',
            (string) (10 + $i),
            '15.00',
            '20.00',
            '2025-12-31',
            '5',
            'แถวที่ ' . $i,
            '1',
        ];
    }

    // แทรกแถวที่มีข้อผิดพลาดเพื่อให้พรีวิว highlight
    $rows[5] = ['', 'สินค้าที่ผิดพลาด', 'หมวดนำเข้า', '-5', 'x', 'y', 'invalid-date', '', '', '1'];

    $file = makeImportCsv($rows);
    $preview = $service->preview($file, 'UPSERT', true);

    expect($preview['summary']['total_rows'])->toBe(22)
        ->and($preview['summary']['error_rows'])->toBeGreaterThan(0)
        ->and(count($preview['preview_rows']))->toBe(20);

    $errorRow = collect($preview['preview_rows'])->firstWhere('row_number', 6);
    expect($errorRow)->not->toBeNull();
    expect($errorRow['errors'])->not->toBe([]);

    removeStoredToken($preview['file_token']);
    @unlink($file);
});

it('upserts batches in replace mode and records adjustments', function (): void {
    $service = new ImportService();
    $category = Category::factory()->create(['name' => 'อุปกรณ์การแพทย์']);
    $product = Product::factory()->create([
        'sku' => 'SKU-REPLACE',
        'name' => 'ถุงมือยาง',
        'category_id' => $category->id,
        'qty' => 20,
        'reorder_point' => 5,
    ]);
    $actor = User::factory()->create(['role' => 'admin']);

    $rows = [[
        'sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active',
    ], [
        'SKU-REPLACE', 'ถุงมือยาง', 'อุปกรณ์การแพทย์', '11', '12.00', '18.00', '', '5', 'ปรับยอดล่าสุด', '1',
    ]];

    $file = makeImportCsv($rows);
    $preview = $service->preview($file, 'UPSERT', false);
    $result = $service->commit($preview['file_token'], 'UPSERT', false, $actor);

    $product->refresh();
    $movement = StockMovement::query()->latest('id')->first();

    expect($result['summary']['updated'])->toBe(1)
        ->and($product->qty)->toBe(11)
        ->and($movement?->type)->toBe('out')
        ->and($movement?->qty)->toBe(9)
        ->and($movement?->note)->toContain('Δ-9');

    removeStoredToken($preview['file_token']);
    @unlink($file);
});

it('upserts batches in delta mode and records receive movements', function (): void {
    $service = new ImportService();
    $category = Category::factory()->create(['name' => 'เวชภัณฑ์']);
    $product = Product::factory()->create([
        'sku' => 'SKU-DELTA',
        'name' => 'ผ้าก๊อซ',
        'category_id' => $category->id,
        'qty' => 4,
        'reorder_point' => 2,
    ]);
    $actor = User::factory()->create(['role' => 'staff']);

    $rows = [[
        'sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active',
    ], [
        'SKU-DELTA', 'ผ้าก๊อซ', 'เวชภัณฑ์', '12', '8.00', '15.00', '', '2', 'เพิ่มยอด', '1',
    ]];

    $file = makeImportCsv($rows);
    $preview = $service->preview($file, 'UPSERT', false);
    $result = $service->commit($preview['file_token'], 'UPSERT', false, $actor);

    $product->refresh();
    $movement = StockMovement::query()->latest('id')->first();

    expect($result['summary']['updated'])->toBe(1)
        ->and($product->qty)->toBe(12)
        ->and($movement?->type)->toBe('in')
        ->and($movement?->qty)->toBe(8)
        ->and($movement?->note)->toContain('Δ+8');

    removeStoredToken($preview['file_token']);
    @unlink($file);
});

it('skips existing batches and receives new ones', function (): void {
    $service = new ImportService();
    $category = Category::factory()->create(['name' => 'ของใช้สำนักงาน']);
    Product::factory()->create([
        'sku' => 'SKU-SKIP',
        'name' => 'ปากกาเจล',
        'category_id' => $category->id,
        'qty' => 8,
        'reorder_point' => 3,
    ]);

    $rows = [[
        'sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active',
    ], [
        'SKU-SKIP', 'ปากกาเจล', 'ของใช้สำนักงาน', '99', '5.00', '10.00', '', '3', 'ไม่ควรถูกอัปเดต', '1',
    ]];

    $file = makeImportCsv($rows);
    $preview = $service->preview($file, 'SKIP', false);
    $result = $service->commit($preview['file_token'], 'SKIP', false, null);

    $product = Product::query()->where('sku', 'SKU-SKIP')->first();

    expect($result['summary']['skipped'])->toBe(1)
        ->and($product?->qty)->toBe(8)
        ->and(StockMovement::query()->count())->toBe(0);

    removeStoredToken($preview['file_token']);
    @unlink($file);
});

it('rolls back the entire file in strict mode using a single transaction when any row fails', function (): void {
    $service = new ImportService();
    $category = Category::factory()->create(['name' => 'หมวดเดิม']);
    $actor = User::factory()->create(['role' => 'admin']);

    $rows = [[
        'sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active',
    ],
        ['SKU-STRICT-A', 'สินค้าถูกต้อง', 'หมวดเดิม', '5', '10.00', '15.00', '', '2', '', '1'],
        ['SKU-STRICT-B', 'สินค้าขาดหมวด', 'หมวดใหม่', '7', '10.00', '15.00', '', '2', '', '1'],
    ];

    $file = makeImportCsv($rows);
    $preview = $service->preview($file, 'UPSERT', false);

    DB::beginTransaction();
    try {
        $result = $service->commit($preview['file_token'], 'UPSERT', false, $actor);
        if (($result['summary']['errors'] ?? 0) > 0) {
            throw new RuntimeException('strict: rollback all');
        }
        DB::commit();
    } catch (RuntimeException $exception) {
        DB::rollBack();
    }

    expect(Product::query()->where('sku', 'SKU-STRICT-A')->exists())->toBeFalse()
        ->and(Product::query()->where('sku', 'SKU-STRICT-B')->exists())->toBeFalse();

    removeStoredToken($preview['file_token']);
    @unlink($file);
});

it('commits valid rows and exports errors in lenient mode', function (): void {
    $service = new ImportService();
    $category = Category::factory()->create(['name' => 'เวชภัณฑ์']);

    $rows = [[
        'sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active',
    ],
        ['SKU-LENIENT-OK', 'สำลี', 'เวชภัณฑ์', '6', '5.00', '9.00', '', '2', '', '1'],
        ['SKU-LENIENT-BAD', '', 'เวชภัณฑ์', '-3', 'a', 'b', '2025-99-99', '', '', '1'],
    ];

    $file = makeImportCsv($rows);
    $preview = $service->preview($file, 'UPSERT', true);
    $result = $service->commit($preview['file_token'], 'UPSERT', true, null);

    $product = Product::query()->where('sku', 'SKU-LENIENT-OK')->first();

    expect($result['summary']['created'])->toBe(1)
        ->and($result['summary']['errors'])->toBeGreaterThan(0)
        ->and($result['error_url'])->not->toBeNull()
        ->and($product?->qty)->toBe(6);

    $errorUrl = $result['error_url'];
    $query = [];
    parse_str(parse_url($errorUrl, PHP_URL_QUERY) ?? '', $query);
    $token = $query['token'] ?? null;
    if ($token) {
        $errorPath = $service->resolveErrorFilePath($token);
        expect(is_file($errorPath))->toBeTrue();
        @unlink($errorPath);
    }

    removeStoredToken($preview['file_token']);
    @unlink($file);
});

it('ignores any price columns in the import file', function (): void {
    config()->set('inventory.enable_price', false);

    $service = new ImportService();
    $category = Category::factory()->create(['name' => 'เครื่องสำอาง']);

    $rows = [[
        'sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active',
    ],
        ['SKU-PRICE', 'โฟมล้างหน้า', 'เครื่องสำอาง', '4', '199.99', '299.99', '', '1', '', '1'],
    ];

    $file = makeImportCsv($rows);
    $preview = $service->preview($file, 'UPSERT', true);
    $service->commit($preview['file_token'], 'UPSERT', true, null);

    $product = Product::query()->where('sku', 'SKU-PRICE')->first();

    expect(number_format((float) $product?->cost_price, 2, '.', ''))->toBe('0.00')
        ->and(number_format((float) $product?->sale_price, 2, '.', ''))->toBe('0.00');

    removeStoredToken($preview['file_token']);
    @unlink($file);
});
