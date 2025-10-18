<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('auto assigns sku when none provided', function () {
    $user = User::factory()->create(['role' => 'staff']);
    actingAs($user);

    $payload = [
        'sku' => '',
        'name' => 'สินค้าไม่ระบุรหัส',
        'initial_qty' => 5,
        'reorder_point' => 1,
        'expire_in_days' => 30,
        'is_active' => 1,
        'category_id' => '',
        'new_category_name' => '',
    ];

    $response = post(route('admin.products.store'), $payload);
    $response->assertRedirect(route('admin.products.index'));

    $product = Product::where('name', 'สินค้าไม่ระบุรหัส')->first();

    expect($product)->not->toBeNull();
    expect($product->sku)->toMatch('/^SKU-\d{4}$/');
});
