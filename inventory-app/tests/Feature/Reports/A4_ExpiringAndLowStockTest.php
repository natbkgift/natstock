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

function createAdminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('filters expiring batches by days and exports CSV without prices', function () {
    Carbon::setTestNow('2024-05-01 08:00:00');

    $user = createAdminUser();
    actingAs($user);

    $category = Category::factory()->create(['name' => 'ยาคงคลัง']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'reorder_point' => 10,
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'LOT-10',
        'expire_date' => Carbon::now()->addDays(10),
        'qty' => 5,
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'LOT-20',
        'expire_date' => Carbon::now()->addDays(20),
        'qty' => 8,
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'LOT-40',
        'expire_date' => Carbon::now()->addDays(40),
        'qty' => 12,
        'is_active' => true,
    ]);

    $response = get(route('admin.reports.expiring-batches', ['days' => 30]));

    $response->assertOk()
        ->assertSee('LOT-10')
        ->assertSee('LOT-20')
        ->assertDontSee('LOT-40');

    $csvResponse = get(route('admin.reports.expiring-batches', ['days' => 30, 'export' => 'csv']));
    $csvResponse->assertOk();

    $content = $csvResponse->streamedContent();
    expect($content)->toContain('sku,name,sub_sku,expire_date,qty,category')
        ->and($content)->toContain('LOT-10')
        ->and($content)->not->toContain('LOT-40');
});

it('lists low stock products with aggregated quantities and exports CSV', function () {
    Carbon::setTestNow('2024-05-02 09:00:00');

    $user = createAdminUser();
    actingAs($user);

    $category = Category::factory()->create(['name' => 'เวชภัณฑ์']);

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'reorder_point' => 20,
        'is_active' => true,
        'qty' => 0,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'LOW-1',
        'expire_date' => Carbon::now()->addDays(5),
        'qty' => 8,
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'LOW-2',
        'expire_date' => Carbon::now()->addDays(15),
        'qty' => 6,
        'is_active' => true,
    ]);

    $healthy = Product::factory()->create([
        'category_id' => $category->id,
        'reorder_point' => 5,
        'is_active' => true,
        'qty' => 100,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $healthy->id,
        'sub_sku' => 'HEALTHY',
        'expire_date' => Carbon::now()->addDays(60),
        'qty' => 50,
        'is_active' => true,
    ]);

    $response = get(route('admin.reports.low-stock'));
    $response->assertOk()
        ->assertSee($product->sku)
        ->assertSee('LOW-1')
        ->assertDontSee($healthy->sku);

    $csvResponse = get(route('admin.reports.low-stock', ['export' => 'csv']));
    $csvResponse->assertOk();

    $content = $csvResponse->streamedContent();
    expect($content)->toContain('sku,name,qty_total,reorder_point,category')
        ->and($content)->toContain($product->sku)
        ->and($content)->not->toContain($healthy->sku);
});
