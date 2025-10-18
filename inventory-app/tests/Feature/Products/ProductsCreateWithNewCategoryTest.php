<?php

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('can create product with new category from form', function () {
    $user = User::factory()->create(['role' => 'staff']);
    actingAs($user);

    $payload = [
        'sku' => 'SKU-NEWCAT-01',
        'name' => 'สินค้าทดสอบหมวดใหม่',
        'initial_qty' => 10,
        'expire_in_days' => 45,
        'reorder_point' => 2,
        'is_active' => 1,
        'category_id' => '',
        'new_category_name' => 'หมวดใหม่ทดสอบ',
        'note' => 'เพิ่มหมวดหมู่ใหม่',
    ];

    $response = post(route('admin.products.store'), $payload);
    $response->assertRedirect(route('admin.products.index'));

    $category = Category::where('name', 'หมวดใหม่ทดสอบ')->first();
    expect($category)->not->toBeNull();

    $product = $category->products()->where('sku', 'SKU-NEWCAT-01')->first();
    expect($product)->not->toBeNull();
    expect($product->name)->toBe('สินค้าทดสอบหมวดใหม่');
});
