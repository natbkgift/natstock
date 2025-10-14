<?php

use App\Console\Commands\BackfillProductBatches;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Services\LotService;
use App\Services\SkuService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('sku service generates sequential codes without duplicates', function (): void {
    /** @var SkuService $service */
    $service = app(SkuService::class);

    $codes = collect(range(1, 5))->map(fn () => $service->next());

    expect($codes->first())->toBe('SKU-0001')
        ->and($codes->last())->toBe('SKU-0005')
        ->and($codes->unique()->count())->toBe($codes->count());
});

test('lot service generates per-product sequence starting from LOT-02', function (): void {
    /** @var LotService $lotService */
    $lotService = app(LotService::class);

    $productA = Product::factory()->create();
    $productB = Product::factory()->create();

    $lotA1 = $lotService->nextFor($productA);
    $lotA2 = $lotService->nextFor($productA);
    $lotB1 = $lotService->nextFor($productB);

    expect($lotA1)->toBe('LOT-02')
        ->and($lotA2)->toBe('LOT-03')
        ->and($lotB1)->toBe('LOT-02')
        ->and($productA->batches()->where('lot_no', 'LOT-01')->exists())->toBeTrue()
        ->and($productB->batches()->where('lot_no', 'LOT-01')->exists())->toBeTrue();
});

test('backfill command migrates legacy quantities into product batches safely', function (): void {
    $category = Category::factory()->create();

    $legacyProduct = Product::withoutEvents(function () use ($category) {
        return Product::query()->create([
            'sku' => '',
            'name' => 'เจลล้างมือ',
            'note' => null,
            'category_id' => $category->id,
            'cost_price' => 0,
            'sale_price' => 0,
            'expire_date' => null,
            'reorder_point' => 0,
            'qty' => 18,
            'is_active' => true,
        ]);
    });

    expect($legacyProduct->sku)->toBe('');

    Artisan::call(BackfillProductBatches::class);

    $legacyProduct->refresh();

    expect($legacyProduct->sku)->toStartWith('SKU-')
        ->and($legacyProduct->qty)->toBe(0)
        ->and($legacyProduct->qtyCurrent())->toBe(18);

    $batch = ProductBatch::query()
        ->where('product_id', $legacyProduct->id)
        ->where('lot_no', 'LOT-01')
        ->first();

    expect($batch)->not()->toBeNull()
        ->and($batch->qty)->toBe(18)
        ->and(DB::table('product_lot_counters')->where('product_id', $legacyProduct->id)->value('next_no'))
            ->toBe(2);
});

test('product batch enforces unique lot numbers per product', function (): void {
    $product = Product::factory()->create();

    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'lot_no' => 'LOT-99',
    ]);

    expect(fn () => ProductBatch::factory()->create([
        'product_id' => $product->id,
        'lot_no' => 'LOT-99',
    ]))->toThrow(QueryException::class);
});
