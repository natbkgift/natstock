<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function makePreviewCsvUpload(array $rows): UploadedFile
{
    $handle = fopen('php://temp', 'r+');
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    rewind($handle);
    $contents = stream_get_contents($handle) ?: '';
    fclose($handle);

    return UploadedFile::fake()->createWithContent('preview.csv', $contents);
}

it('previews first 20 rows and summarises csv data', function (): void {
    $user = User::factory()->create(['role' => 'admin']);

    $header = ['sku', 'qty', 'name', 'category', 'cost_price'];
    $rows = [$header];

    for ($i = 1; $i <= 22; $i++) {
        $rows[] = [
            sprintf('SKU-%03d', $i),
            (string) $i,
            'สินค้า ' . $i,
            'หมวดหมู่',
            '100.00',
        ];
    }

    $file = makePreviewCsvUpload($rows);

    $response = $this->actingAs($user)->post(
        route('import_export.preview'),
        ['file' => $file],
        ['Accept' => 'application/json']
    );

    $response->assertOk();
    $data = $response->json();

    expect($data['meta']['total_rows'])->toBe(22)
        ->and($data['meta']['ignored_columns'])->toContain('cost_price')
        ->and($data['html'])->toContain('ทั้งหมด 22 แถว (พรีวิวสูงสุด 20 แถว)')
        ->and($data['html'])->toContain('คอลัมน์ราคาไม่ถูกใช้งาน');
});

it('highlights invalid values with error messages', function (): void {
    $user = User::factory()->create(['role' => 'admin']);

    $rows = [
        ['sku', 'qty', 'expire_date', 'lot_no'],
        ['SKU-001', '-5', '2024/10/01', '12345678901234567'],
    ];

    $file = makePreviewCsvUpload($rows);

    $response = $this->actingAs($user)->post(
        route('import_export.preview'),
        ['file' => $file],
        ['Accept' => 'application/json']
    );

    $response->assertStatus(200);
    $html = $response->json('html');

    expect($html)->toContain('จำนวนต้องเป็นจำนวนเต็มที่มากกว่าหรือเท่ากับ 0')
        ->and($html)->toContain('รูปแบบวันหมดอายุต้องเป็น YYYY-MM-DD')
        ->and($html)->toContain('หมายเลขล็อตต้องมีความยาวไม่เกิน 16 ตัวอักษร');
});
