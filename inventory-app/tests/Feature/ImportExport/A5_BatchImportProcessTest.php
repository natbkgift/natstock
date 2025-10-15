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

it('blocks import processing when the feature is disabled', function (): void {
    Storage::fake('local');

    $user = User::factory()->create(['role' => 'admin']);

    $file = makeImportUpload([
        ['sku', 'qty', 'name', 'category', 'lot_no'],
        ['SKU-200', '5', 'สินค้า', 'หมวด', 'LOT-01'],
    ]);

    $this->actingAs($user)
        ->post(route('import_export.process'), [
            'file' => $file,
            'mode' => 'upsert_replace',
            'strict' => '1',
        ], ['Accept' => 'application/json'])
        ->assertNotFound();

    expect(Product::count())->toBe(0);
    expect(ProductBatch::count())->toBe(0);
    expect(StockMovement::count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('does not create error files when import processing is blocked', function (): void {
    Storage::fake('local');

    $user = User::factory()->create(['role' => 'admin']);

    $file = makeImportUpload([
        ['sku', 'qty', 'name', 'category', 'lot_no'],
        ['SKU-201', '-2', 'สินค้า', 'หมวด', 'LOT-ERR'],
    ]);

    $this->actingAs($user)
        ->post(route('import_export.process'), [
            'file' => $file,
            'mode' => 'upsert_delta',
            'strict' => '0',
        ], ['Accept' => 'application/json'])
        ->assertNotFound();

    expect(Storage::disk('local')->allFiles())->toBe([]);
    expect(Product::count())->toBe(0);
    expect(ProductBatch::count())->toBe(0);
    expect(StockMovement::count())->toBe(0);
});
