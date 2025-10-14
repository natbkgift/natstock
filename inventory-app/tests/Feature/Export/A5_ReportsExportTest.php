<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('exports expiring batches without price columns', function (): void {
    config()->set('inventory.enable_price', false);
    Carbon::setTestNow('2024-06-01 08:00:00');

    $user = User::factory()->create(['role' => 'admin']);
    actingAs($user);

    $category = Category::query()->firstOrCreate(
        ['name' => 'เวชภัณฑ์'],
        ['note' => 'อุปกรณ์การแพทย์และของใช้ในคลัง', 'is_active' => true]
    );
    $product = Product::factory()->create([
        'sku' => 'SKU-EXP',
        'name' => 'น้ำยาฆ่าเชื้อ',
        'category_id' => $category->id,
        'qty' => 0,
        'reorder_point' => 5,
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'EXP-30',
        'expire_date' => Carbon::now()->addDays(20),
        'qty' => 9,
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'EXP-90',
        'expire_date' => Carbon::now()->addDays(90),
        'qty' => 6,
        'is_active' => true,
    ]);

    $response = get(route('admin.reports.expiring-batches', ['days' => 30, 'export' => 'csv']));
    $response->assertOk();

    $content = $response->streamedContent();
    expect($content)->toContain('sku,name,lot_no,expire_date,qty,category')
        ->and($content)->toContain('EXP-30')
        ->and($content)->not->toContain('cost_price')
        ->and($content)->not->toContain('sale_price');
});

it('exports low stock overview without price columns', function (): void {
    config()->set('inventory.enable_price', false);

    $user = User::factory()->create(['role' => 'admin']);
    actingAs($user);

    $category = Category::factory()->create(['name' => 'อุปกรณ์']);

    $lowStock = Product::factory()->create([
        'sku' => 'SKU-LOW',
        'name' => 'หน้ากาก',
        'category_id' => $category->id,
        'qty' => 0,
        'reorder_point' => 10,
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $lowStock->id,
        'sub_sku' => 'LOW-A',
        'qty' => 4,
        'expire_date' => Carbon::now()->addDays(15),
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $lowStock->id,
        'sub_sku' => 'LOW-B',
        'qty' => 3,
        'expire_date' => Carbon::now()->addDays(25),
        'is_active' => true,
    ]);

    $healthy = Product::factory()->create([
        'sku' => 'SKU-OK',
        'name' => 'ถุงมือ',
        'category_id' => $category->id,
        'qty' => 50,
        'reorder_point' => 5,
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $healthy->id,
        'sub_sku' => 'OK-LOT',
        'qty' => 40,
        'expire_date' => Carbon::now()->addDays(40),
        'is_active' => true,
    ]);

    $response = get(route('admin.reports.low-stock', ['export' => 'csv']));
    $response->assertOk();

    $content = $response->streamedContent();
    expect($content)->toContain('sku,name,qty_total,reorder_point,category')
        ->and($content)->toContain('SKU-LOW')
        ->and($content)->not->toContain('SKU-OK')
        ->and($content)->not->toContain('cost_price');
});
