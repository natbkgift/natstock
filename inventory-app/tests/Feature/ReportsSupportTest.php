<?php

use App\Models\Product;
use App\Services\Reports\ProductReportService;
use App\Support\CsvExporter;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

test('csv exporter เขียนไฟล์พร้อม BOM และป้องกันสูตรอันตราย', function (): void {
    $response = CsvExporter::download('report.csv', ['หัว'], [['=1', '+2', '-3', '@4', 'ปกติ']]);

    expect($response)->toBeInstanceOf(StreamedResponse::class);

    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toBe('attachment; filename="report.csv"');

    // Capture the streamed content to test it
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    // Assert BOM is present
    expect($content)->toStartWith("\xEF\xBB\xBF");
    // Assert header is present
    expect($content)->toContain("หัว");
    // Assert each sanitized value is present
    expect($content)->toContain("'=1");
    expect($content)->toContain("''+2");
    expect($content)->toContain("''-3");
    expect($content)->toContain("'@4");
    expect($content)->toContain("ปกติ");
});

test('คำนวณมูลค่าสต็อกรวมได้ตรงตามจำนวนสินค้า', function (): void {
    config()->set('inventory.enable_price', true);

    $service = new ProductReportService();
    $products = Collection::make([
        new Product(['sku' => 'SKU-A', 'name' => 'สินค้า A', 'cost_price' => '10.00', 'qty' => 5]),
        new Product(['sku' => 'SKU-B', 'name' => 'สินค้า B', 'cost_price' => '25.50', 'qty' => 2]),
    ]);

    $expectedTotal = (10.00 * 5) + (25.50 * 2);
    $total = $service->calculateValuationTotal($products);

    expect($total)->toBe($expectedTotal);
});
