<?php

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use App\Services\StockMovementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createMovementStaff(): User
{
    return User::factory()->create(['role' => 'staff']);
}

it('receives stock and creates the next lot automatically', function () {
    $user = createMovementStaff();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);
    $initialBatch = $product->batches()->where('lot_no', 'LOT-01')->firstOrFail();
    $initialBatch->update(['qty' => 0]);

    Carbon::setTestNow('2024-05-01 09:00:00');

    $service = app(StockMovementService::class);
    $movement = $service->receive($product->fresh(), 10, null, 'รับล็อตใหม่');

    $product->refresh();
    $batch = $movement->batch->fresh();

    expect($movement->type)->toBe('receive')
        ->and($batch->lot_no)->toBe('LOT-02')
        ->and($batch->qty)->toBe(10)
        ->and($batch->received_at)->not->toBeNull()
        ->and($product->qty)->toBe(10);
});

it('issues from the most suitable lot when none specified (FEFO)', function () {
    $user = createMovementStaff();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);

    $product->batches()->where('lot_no', 'LOT-01')->firstOrFail()->update([
        'qty' => 0,
        'expire_date' => Carbon::parse('2024-12-31'),
    ]);

    $first = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'lot_no' => 'LOT-02',
        'qty' => 5,
        'expire_date' => Carbon::parse('2024-07-01'),
        'received_at' => Carbon::parse('2024-04-01'),
    ]);

    $second = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'lot_no' => 'LOT-03',
        'qty' => 8,
        'expire_date' => Carbon::parse('2024-06-01'),
        'received_at' => Carbon::parse('2024-04-15'),
    ]);

    $service = app(StockMovementService::class);
    $movement = $service->issue($product->fresh(), 3, null, 'เบิก FEFO');

    $product->refresh();
    $first->refresh();
    $second->refresh();

    expect($movement->type)->toBe('issue')
        ->and($movement->batch_id)->toBe($second->id)
        ->and($second->qty)->toBe(5)
        ->and($first->qty)->toBe(5)
        ->and($product->qty)->toBe(10);
});

it('adjusts batch quantity and records adjust movement', function () {
    $user = createMovementStaff();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);

    $batch = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'lot_no' => 'LOT-10',
        'qty' => 5,
    ]);

    $service = app(StockMovementService::class);
    $movement = $service->adjust($product->fresh(), 'LOT-10', 8, 'ปรับยอดตามนับจริง');

    $product->refresh();
    $batch->refresh();

    expect($movement->type)->toBe('adjust')
        ->and($movement->qty)->toBe(3)
        ->and($movement->batch_id)->toBe($batch->id)
        ->and($batch->qty)->toBe(8)
        ->and($product->qty)->toBe(8);
});

it('prevents negative issues when called rapidly', function () {
    $user = createMovementStaff();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);
    $batch = $product->batches()->where('lot_no', 'LOT-01')->firstOrFail();
    $batch->update(['qty' => 20]);

    $service = app(StockMovementService::class);

    $movementA = $service->issue($product->fresh(), 10, 'LOT-01', 'เบิกรอบที่ 1');
    $movementB = $service->issue($product->fresh(), 10, 'LOT-01', 'เบิกรอบที่ 2');

    $batch->refresh();
    $product->refresh();

    expect([$movementA->type, $movementB->type])->toEqual(['issue', 'issue'])
        ->and($batch->qty)->toBe(0)
        ->and($product->qty)->toBe(0);

    expect(fn () => $service->issue($product->fresh(), 1, 'LOT-01', 'ทดสอบเกิน'))->toThrow(ValidationException::class);
});
