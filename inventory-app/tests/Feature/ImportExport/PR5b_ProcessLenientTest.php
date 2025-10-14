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

it('commits valid rows and generates error csv when lenient mode is used', function (): void {
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

    $batch = ProductBatch::query()->where('product_id', $product->getKey())->where('lot_no', 'LOT-A')->first();
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
    expect($csv)->toContain('จำนวนต้องเป็นจำนวนเต็มไม่ติดลบ');
    expect($csv)->toContain('SKU-101');
});
