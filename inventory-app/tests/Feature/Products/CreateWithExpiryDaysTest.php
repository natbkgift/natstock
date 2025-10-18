<?php

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('creates lot-01 with expire date derived from number of days', function () {
    Carbon::setTestNow('2024-06-01 10:00:00');

    $user = User::factory()->create(['role' => 'staff']);
    actingAs($user);

    $payload = [
        'sku' => '',
        'name' => 'เจลแอลกอฮอล์ขวดใหญ่',
        'initial_qty' => 25,
        'reorder_point' => 5,
        'expire_in_days' => 15,
        'category_id' => '',
        'new_category_name' => '',
        'is_active' => 1,
    ];

    post(route('admin.products.store'), $payload)
        ->assertRedirect(route('admin.products.index'));

    $product = Product::where('name', 'เจลแอลกอฮอล์ขวดใหญ่')->firstOrFail();
    $expectedExpire = Carbon::now()->addDays(15)->toDateString();

    expect($product->expire_date?->toDateString())->toBe($expectedExpire);
    expect($product->qty)->toBe(25);

    $batch = $product->batches()->where('lot_no', 'LOT-01')->first();
    expect($batch)->not->toBeNull();
    expect($batch->qty)->toBe(25);
    expect($batch->expire_date?->toDateString())->toBe($expectedExpire);

    $movement = StockMovement::where('product_id', $product->id)
        ->where('batch_id', $batch->id)
        ->where('type', 'receive')
        ->first();

    expect($movement)->not->toBeNull();
    expect($movement->qty)->toBe(25);
});
