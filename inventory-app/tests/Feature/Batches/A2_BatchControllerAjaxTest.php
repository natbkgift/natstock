<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAs(User::factory()->create(['role' => 'staff']));
});

it('creates a new lot via ajax and returns formatted data', function () {
    $product = Product::factory()->create();

    $response = $this->postJson(route('admin.products.batches.store', $product), [
        'expire_date' => '2024-12-31',
        'note' => 'สร้างผ่าน ajax',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('batch.lot_no', 'LOT-02');
    $response->assertJsonPath('batch.qty', 0);
    $response->assertJsonPath('batch.expire_date', '2024-12-31');
    $this->assertNotEmpty($response->json('batch.expire_date_th'));

    $this->assertDatabaseHas('product_batches', [
        'product_id' => $product->id,
        'lot_no' => 'LOT-02',
    ]);
});

it('validates the expire date format when creating a lot', function () {
    $product = Product::factory()->create();

    $response = $this->postJson(route('admin.products.batches.store', $product), [
        'expire_date' => '31-12-2024',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['expire_date']);
});
