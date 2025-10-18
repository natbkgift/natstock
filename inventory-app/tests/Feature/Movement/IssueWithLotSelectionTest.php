<?php

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('requires selecting an available lot and issues stock correctly', function () {
    Carbon::setTestNow('2024-06-05 09:00:00');

    $user = User::factory()->create(['role' => 'staff']);
    actingAs($user);

    $product = Product::factory()->create([
        'qty' => 8,
        'reorder_point' => 2,
        'expire_date' => Carbon::now()->addDays(10),
    ]);

    $lotOne = $product->batches()->where('lot_no', 'LOT-01')->first();
    $lotOne->update([
        'qty' => 5,
        'expire_date' => Carbon::now()->addDays(10),
        'received_at' => Carbon::now()->subDay(),
    ]);

    $lotTwo = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'lot_no' => 'LOT-02',
        'qty' => 3,
        'expire_date' => Carbon::now()->addDays(3),
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'lot_no' => 'LOT-03',
        'qty' => 0,
        'expire_date' => Carbon::now()->addDays(5),
        'is_active' => true,
    ]);

    $product->update(['qty' => $product->batches()->where('is_active', true)->sum('qty')]);

    $response = get(route('admin.movements.index', [
        'form_type' => 'issue',
        'product_id' => $product->id,
    ]));

    $response->assertOk();

    $html = $response->getContent();
    $match = [];
    preg_match('/const batchCache = (.*?);/s', $html, $match);
    expect($match)->toHaveCount(2);

    $batchCache = json_decode($match[1], true);
    $productBatches = $batchCache[$product->id] ?? [];
    $availableLots = array_values(array_filter($productBatches, fn ($batch) => ($batch['qty'] ?? 0) > 0));

    expect($availableLots)->toHaveCount(2);
    expect($availableLots[0]['lot_no'])->toBe('LOT-02');
    expect(collect($availableLots)->pluck('lot_no'))->not->toContain('LOT-03');

    post(route('admin.products.issue', $product), [
        'qty' => 2,
        'lot_no' => 'LOT-02',
        'note' => 'เบิกสำหรับหน่วยงาน A',
    ])->assertRedirect(route('admin.movements.index', [
        'form_type' => 'issue',
        'product_id' => $product->id,
        'lot_no' => 'LOT-02',
    ]));

    $lotTwo->refresh();
    $product->refresh();

    expect($lotTwo->qty)->toBe(1);
    expect($product->qty)->toBe(6);

    $movement = StockMovement::where('product_id', $product->id)
        ->where('batch_id', $lotTwo->id)
        ->where('type', 'issue')
        ->first();

    expect($movement)->not->toBeNull();
    expect($movement->qty)->toBe(2);
});
