<?php

use App\Models\Product;
use App\Services\Reports\ProductReportService;
use App\Support\CsvExporter;
use Illuminate\Support\Collection;

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
}

test('csv exporter เขียนไฟล์พร้อม BOM และป้องกันสูตรอันตราย', function (): void {
    $result = CsvExporter::download('report.csv', ['หัว'], [['=1', '+2', '-3', '@4', 'ปกติ']]);
    [$prefix, $filename, $payload] = explode(':', $result, 3);

    assertSameValue('stream-download', $prefix);
    assertSameValue('report.csv', $filename);

    $content = base64_decode($payload);
    assertSameValue("\xEF\xBB\xBF", substr($content, 0, 3));
    $csvBody = substr($content, 3);

    assertTrue(str_contains($csvBody, "หัว"));
    assertTrue(str_contains($csvBody, "'=1,'+2,'-3,'@4,ปกติ"));
});

test('คำนวณมูลค่าสต็อกรวมได้ตรงตามจำนวนสินค้า', function (): void {
    $service = new ProductReportService();
    $products = Collection::make([
        new Product(['sku' => 'SKU-A', 'name' => 'สินค้า A', 'cost_price' => '10.00', 'qty' => 5]),
        new Product(['sku' => 'SKU-B', 'name' => 'สินค้า B', 'cost_price' => '25.50', 'qty' => 2]),
    ]);

    $total = $service->calculateValuationTotal($products);

    assertSameValue(10.00 * 5 + 25.50 * 2, $total);
});
