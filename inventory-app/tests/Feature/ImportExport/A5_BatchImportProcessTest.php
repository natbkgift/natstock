<?php

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

if (!function_exists('makeImportUpload')) {
    function makeImportUpload(array $rows, string $name = 'import.csv'): UploadedFile
    {
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        return UploadedFile::fake()->createWithContent($name, $contents);
    }
}

it('handles UPSERT replace, UPSERT delta and SKIP modes with the correct stock movements', function (): void {
    Storage::fake('local');

    $user = User::factory()->create(['role' => 'admin']);

    $product = Product::factory()->create([
        'sku' => 'SKU-200',
        'qty' => 0,
    ]);

    $existingBatch = ProductBatch::factory()->create([
        'product_id' => $product->getKey(),
        'lot_no' => 'LOT-EXIST',
        'qty' => 5,
        'is_active' => true,
    ]);

    $product->update(['qty' => 5]);
    StockMovement::query()->delete();

    $replaceFile = makeImportUpload([
        ['sku', 'qty', 'name', 'category', 'lot_no'],
        [$product->sku, '20', 'สินค้า REPLACE', 'หมวดทดสอบ', 'LOT-EXIST'],
    ]);

    $replaceResponse = $this->actingAs($user)->post(
        route('import_export.process'),
        [
            'file' => $replaceFile,
            'mode' => 'upsert_replace',
            'strict' => '1',
        ],
        ['Accept' => 'application/json']
    );

    $replaceResponse->assertOk();
    $replaceResponse->assertJsonPath('summary.batches_updated', 1);
    $replaceResponse->assertJsonPath('summary.movements_created', 1);

    $existingBatch->refresh();
    expect($existingBatch->qty)->toBe(20);

    $adjustMovement = StockMovement::query()->latest('id')->first();
    expect($adjustMovement)->not->toBeNull()
        ->and($adjustMovement->type)->toBe('adjust')
        ->and($adjustMovement->qty)->toBe(15)
        ->and($adjustMovement->note)->toContain('ปรับจาก 5 → 20');

    $deltaFile = makeImportUpload([
        ['sku', 'qty', 'name', 'category', 'lot_no'],
        [$product->sku, '3', 'สินค้า DELTA', 'หมวดทดสอบ', 'LOT-EXIST'],
    ]);

    $deltaResponse = $this->actingAs($user)->post(
        route('import_export.process'),
        [
            'file' => $deltaFile,
            'mode' => 'upsert_delta',
            'strict' => '1',
        ],
        ['Accept' => 'application/json']
    );

    $deltaResponse->assertOk();
    $deltaResponse->assertJsonPath('summary.batches_updated', 1);
    $deltaResponse->assertJsonPath('summary.movements_created', 1);

    $existingBatch->refresh();
    expect($existingBatch->qty)->toBe(23);

    $deltaMovement = StockMovement::query()->latest('id')->first();
    expect($deltaMovement)->not->toBeNull()
        ->and($deltaMovement->type)->toBe('receive')
        ->and($deltaMovement->qty)->toBe(3);

    $skipFile = makeImportUpload([
        ['sku', 'qty', 'name', 'category', 'lot_no'],
        [$product->sku, '99', 'สินค้า SKIP', 'หมวดทดสอบ', 'LOT-EXIST'],
        [$product->sku, '4', 'สินค้า SKIP', 'หมวดทดสอบ', 'LOT-NEW'],
    ]);

    $skipResponse = $this->actingAs($user)->post(
        route('import_export.process'),
        [
            'file' => $skipFile,
            'mode' => 'skip',
            'strict' => '0',
        ],
        ['Accept' => 'application/json']
    );

    $skipResponse->assertOk();
    $skipResponse->assertJsonPath('summary.rows_ok', 2);
    $skipResponse->assertJsonPath('summary.batches_created', 1);
    $skipResponse->assertJsonPath('summary.movements_created', 1);

    $existingBatch->refresh();
    expect($existingBatch->qty)->toBe(23);

    $newBatch = ProductBatch::query()
        ->where('product_id', $product->getKey())
        ->where('lot_no', 'LOT-NEW')
        ->first();

    expect($newBatch)->not->toBeNull();
    expect($newBatch->qty)->toBe(4);

    $skipMovement = StockMovement::query()->where('batch_id', $newBatch->getKey())->latest('id')->first();
    expect($skipMovement)->not->toBeNull()
        ->and($skipMovement->type)->toBe('receive')
        ->and($skipMovement->qty)->toBe(4);

    expect(StockMovement::count())->toBe(3);
});

it('rolls back the entire file when strict mode encounters validation errors', function (): void {
    Storage::fake('local');

    $user = User::factory()->create(['role' => 'admin']);

    $rows = [
        ['sku', 'qty', 'name', 'category'],
        ['SKU-001', '10', 'สินค้า 1', 'หมวดหมู่ A'],
        ['SKU-002', '-5', 'สินค้า 2', 'หมวดหมู่ B'],
    ];

    $file = makeImportUpload($rows);

    $response = $this->actingAs($user)->post(
        route('import_export.process'),
        [
            'file' => $file,
            'mode' => 'upsert_replace',
            'strict' => '1',
        ],
        ['Accept' => 'application/json']
    );

    $response->assertStatus(422);
    $response->assertJsonPath('summary.rows_ok', 0);
    $response->assertJsonPath('summary.rows_error', 1);
    $response->assertJsonPath('summary.strict_rolled_back', true);

    expect(Product::count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('commits valid rows and generates an error csv when lenient mode is selected', function (): void {
    Storage::fake('local');

    $user = User::factory()->create(['role' => 'admin']);

    $rows = [
        ['sku', 'qty', 'name', 'category', 'lot_no'],
        ['SKU-100', '5', 'สินค้า 100', 'หมวด A', 'LOT-A'],
        ['SKU-101', '-1', 'สินค้า 101', 'หมวด B', 'LOT-B'],
    ];

    $file = makeImportUpload($rows);

    $response = $this->actingAs($user)->post(
        route('import_export.process'),
        [
            'file' => $file,
            'mode' => 'upsert_delta',
            'strict' => '0',
        ],
        ['Accept' => 'application/json']
    );

    $response->assertOk();
    $response->assertJsonPath('summary.rows_ok', 1);
    $response->assertJsonPath('summary.rows_error', 1);
    $response->assertJsonPath('summary.movements_created', 1);

    $product = Product::query()->where('sku', 'SKU-100')->first();
    expect($product)->not->toBeNull();

    $batch = ProductBatch::query()
        ->where('product_id', $product->getKey())
        ->where('lot_no', 'LOT-A')
        ->first();

    expect($batch)->not->toBeNull();
    expect($batch->qty)->toBe(5);

    $movement = StockMovement::query()->where('batch_id', $batch->getKey())->first();
    expect($movement)->not->toBeNull();
    expect($movement->type)->toBe('receive');
    expect($movement->qty)->toBe(5);

    $errorUrl = $response->json('error_csv_url');
    expect($errorUrl)->not->toBeNull();

    $query = [];
    parse_str(parse_url($errorUrl, PHP_URL_QUERY) ?? '', $query);
    $errorPath = Arr::get($query, 'path');

    expect($errorPath)->not->toBeNull();
    Storage::disk('local')->assertExists($errorPath);

    $csv = Storage::disk('local')->get($errorPath);
    expect($csv)->toContain('จำนวนต้องเป็นจำนวนเต็มไม่ติดลบ')
        ->and($csv)->toContain('SKU-101');
});
