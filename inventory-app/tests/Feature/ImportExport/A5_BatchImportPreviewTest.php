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

it('blocks import preview when the feature is disabled', function (): void {
    $user = User::factory()->create(['role' => 'admin']);

    $file = makePreviewCsvUpload([
        ['sku', 'qty'],
        ['SKU-001', '5'],
    ]);

    $this->actingAs($user)
        ->post(route('import_export.preview'), ['file' => $file], ['Accept' => 'application/json'])
        ->assertNotFound();
});

it('returns 404 for invalid payloads when import is disabled', function (): void {
    $user = User::factory()->create(['role' => 'admin']);

    $file = makePreviewCsvUpload([
        ['sku', 'qty'],
        ['SKU-001', '-5'],
    ]);

    $this->actingAs($user)
        ->post(route('import_export.preview'), ['file' => $file], ['Accept' => 'application/json'])
        ->assertNotFound();
});
