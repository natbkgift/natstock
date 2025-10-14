<?php

use App\Models\Product;
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

it('rolls back entire file when strict mode encounters an error', function (): void {
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
