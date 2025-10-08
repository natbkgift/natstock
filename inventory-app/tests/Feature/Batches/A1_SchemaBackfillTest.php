<?php

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('creates unspecified batches for legacy quantities and keeps qtyCurrent accurate', function (): void {
    $productWithQty = Product::factory()->create([
        'sku' => 'SKU-LEGACY',
        'qty' => 18,
    ]);

    $productZero = Product::factory()->create([
        'sku' => 'SKU-ZERO',
        'qty' => 0,
    ]);

    $productWithExisting = Product::factory()->create([
        'sku' => 'SKU-EXIST',
        'qty' => 12,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $productWithExisting->id,
        'sub_sku' => $productWithExisting->sku . '-UNSPECIFIED',
        'qty' => 12,
        'is_active' => true,
    ]);

    $exitCode = Artisan::call('backfill:product-batches');

    expect($exitCode)->toBe(0);

    $this->assertDatabaseHas('product_batches', [
        'product_id' => $productWithQty->id,
        'sub_sku' => 'SKU-LEGACY-UNSPECIFIED',
        'qty' => 18,
        'is_active' => true,
    ]);

    expect($productWithQty->fresh()->qtyCurrent())->toBe(18);

    expect(ProductBatch::query()->where('product_id', $productZero->id)->count())->toBe(0);

    expect(ProductBatch::query()
        ->where('product_id', $productWithExisting->id)
        ->where('sub_sku', 'SKU-EXIST-UNSPECIFIED')
        ->count())->toBe(1);
});
