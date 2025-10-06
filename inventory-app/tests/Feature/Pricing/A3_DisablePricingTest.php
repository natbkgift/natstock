<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

function makeStaff(): User
{
    return User::factory()->create(['role' => 'staff']);
}

it('strips pricing data on create and update when pricing is disabled', function (): void {
    config()->set('inventory.enable_price', false);
    $user = makeStaff();
    actingAs($user);

    $category = Category::factory()->create();

    $createResponse = post(route('admin.products.store'), [
        'sku' => 'SKU-DISABLE-01',
        'name' => 'สินค้าทดสอบ',
        'category_id' => $category->getKey(),
        'qty' => 5,
        'reorder_point' => 1,
        'is_active' => 1,
        'cost_price' => 123.45,
        'sale_price' => 999.99,
    ]);

    $createResponse->assertRedirect(route('admin.products.index'));

    $product = Product::query()->firstOrFail();
    expect(number_format((float) $product->cost_price, 2, '.', ''))->toBe('0.00')
        ->and(number_format((float) $product->sale_price, 2, '.', ''))->toBe('0.00');

    $updateResponse = put(route('admin.products.update', $product), [
        'sku' => 'SKU-DISABLE-01',
        'name' => 'สินค้าปรับปรุง',
        'category_id' => $category->getKey(),
        'qty' => 7,
        'reorder_point' => 2,
        'is_active' => 1,
        'cost_price' => 555.55,
        'sale_price' => 777.77,
    ]);

    $updateResponse->assertRedirect(route('admin.products.index'));

    $product->refresh();
    expect(number_format((float) $product->cost_price, 2, '.', ''))->toBe('0.00')
        ->and(number_format((float) $product->sale_price, 2, '.', ''))->toBe('0.00')
        ->and($product->name)->toBe('สินค้าปรับปรุง');
});

it('hides pricing fields in the product form and blocks valuation report when disabled', function (): void {
    config()->set('inventory.enable_price', false);
    $user = makeStaff();
    actingAs($user);

    $formResponse = get(route('admin.products.create'));
    $formResponse->assertOk();
    $formResponse->assertDontSee('ราคาทุน (บาท)');
    $formResponse->assertSee('ระบบนี้ปิดการใช้งานราคาทุน/ราคาขายแล้ว');

    $valuationResponse = get(route('admin.reports.valuation'));
    $valuationResponse->assertNotFound();
});

it('exports low stock report without price columns when disabled', function (): void {
    config()->set('inventory.enable_price', false);
    $user = makeStaff();
    actingAs($user);

    Product::factory()->lowStock()->create(['qty' => 3, 'reorder_point' => 10]);

    $response = get(route('admin.reports.low-stock', ['export' => 'csv']));
    $response->assertOk();

    $content = $response->streamedContent();
    expect($content)->not->toContain('ราคาทุน')->and($content)->not->toContain('cost_price');
});

it('shows pricing UI and keeps values when the flag is enabled', function (): void {
    config()->set('inventory.enable_price', true);
    $user = makeStaff();
    actingAs($user);

    $formResponse = get(route('admin.products.create'));
    $formResponse->assertOk();
    $formResponse->assertSee('ราคาทุน (บาท)');

    $category = Category::factory()->create();

    $createResponse = post(route('admin.products.store'), [
        'sku' => 'SKU-ENABLE-01',
        'name' => 'สินค้าเปิดราคา',
        'category_id' => $category->getKey(),
        'qty' => 4,
        'reorder_point' => 1,
        'is_active' => 1,
        'cost_price' => 88.25,
        'sale_price' => 99.99,
    ]);

    $createResponse->assertRedirect(route('admin.products.index'));

    $product = Product::query()->where('sku', 'SKU-ENABLE-01')->firstOrFail();
    expect(number_format((float) $product->cost_price, 2, '.', ''))->toBe('88.25')
        ->and(number_format((float) $product->sale_price, 2, '.', ''))->toBe('99.99');
});
