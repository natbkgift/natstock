<?php

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

it('handles replace, delta and skip modes correctly', function (): void {
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

    // UPSERT REPLACE
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
    expect($adjustMovement)->not->toBeNull();
    expect($adjustMovement->type)->toBe('adjust');
    expect($adjustMovement->qty)->toBe(15);
    expect($adjustMovement->note)->toContain('ปรับจาก 5 → 20');

    // UPSERT DELTA
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
    expect($deltaMovement)->not->toBeNull();
    expect($deltaMovement->type)->toBe('receive');
    expect($deltaMovement->qty)->toBe(3);

    // SKIP MODE
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

    $newBatch = ProductBatch::query()->where('product_id', $product->getKey())->where('lot_no', 'LOT-NEW')->first();
    expect($newBatch)->not->toBeNull();
    expect($newBatch->qty)->toBe(4);

    $skipMovement = StockMovement::query()->where('batch_id', $newBatch->getKey())->latest('id')->first();
    expect($skipMovement)->not->toBeNull();
    expect($skipMovement->type)->toBe('receive');
    expect($skipMovement->qty)->toBe(4);

    expect(StockMovement::count())->toBe(3);
});
