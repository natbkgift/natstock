<?php

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use App\Services\StockMovementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

function movementOperator(string $role = 'staff'): User
{
    return User::factory()->create(['role' => $role]);
}

it('receives stock without a provided lot and creates the next sequence with a receive movement', function (): void {
    Carbon::setTestNow('2024-05-01 09:00:00');

    $user = movementOperator();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);
    $initialBatch = $product->batches()->where('lot_no', 'LOT-01')->firstOrFail();
    $initialBatch->update(['qty' => 0]);

    /** @var StockMovementService $service */
    $service = app(StockMovementService::class);
    $movement = $service->receive($product->fresh(), 10, null, 'รับล็อตใหม่');

    $product->refresh();
    $batch = $movement->batch->fresh();

    expect($movement->type)->toBe('receive')
        ->and($batch->lot_no)->toBe('LOT-02')
        ->and($batch->qty)->toBe(10)
        ->and($batch->received_at?->format('Y-m-d H:i:s'))
            ->toBe(Carbon::now()->format('Y-m-d H:i:s'))
        ->and($product->qty)->toBe(10);
});

it('issues stock using FEFO when no lot is provided and prevents negative balance', function (): void {
    Carbon::setTestNow('2024-05-02 11:15:00');

    $user = movementOperator();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);

    $product->batches()->where('lot_no', 'LOT-01')->firstOrFail()->update([
        'qty' => 0,
        'expire_date' => Carbon::parse('2024-12-31'),
    ]);

    $older = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'lot_no' => 'LOT-02',
        'qty' => 5,
        'expire_date' => Carbon::parse('2024-07-01'),
        'received_at' => Carbon::parse('2024-04-01'),
    ]);

    $earliest = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'lot_no' => 'LOT-03',
        'qty' => 8,
        'expire_date' => Carbon::parse('2024-06-01'),
        'received_at' => Carbon::parse('2024-04-15'),
    ]);

    /** @var StockMovementService $service */
    $service = app(StockMovementService::class);
    $movement = $service->issue($product->fresh(), 3, null, 'เบิก FEFO');

    $product->refresh();
    $older->refresh();
    $earliest->refresh();

    expect($movement->type)->toBe('issue')
        ->and($movement->batch_id)->toBe($earliest->id)
        ->and($earliest->qty)->toBe(5)
        ->and($older->qty)->toBe(5)
        ->and($product->qty)->toBe(10);

    $secondMovement = $service->issue($product->fresh(), 5, null, 'เบิกรอบสอง');

    $earliest->refresh();
    $older->refresh();
    $product->refresh();

    expect($secondMovement->batch_id)->toBe($earliest->id)
        ->and($earliest->qty)->toBe(0)
        ->and($product->qty)->toBe(5);

    expect(fn () => $service->issue($product->fresh(), 6, null, 'ลองเกิน'))->toThrow(ValidationException::class);
});

it('allows direct adjust movements and records before/after delta', function (): void {
    Carbon::setTestNow('2024-05-03 08:00:00');

    $user = movementOperator();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);

    $batch = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'lot_no' => 'LOT-10',
        'qty' => 5,
    ]);

    /** @var StockMovementService $service */
    $service = app(StockMovementService::class);
    $movement = $service->adjust($product->fresh(), 'LOT-10', 8, 'ปรับยอดตามนับจริง');

    $product->refresh();
    $batch->refresh();

    expect($movement->type)->toBe('adjust')
        ->and($movement->qty)->toBe(3)
        ->and($movement->note)->toContain('Δ+3')
        ->and($product->qty)->toBe(8);
});

it('processes ajax lot creation with validation and FEFO friendly defaults', function (): void {
    Carbon::setTestNow('2024-05-04 14:00:00');

    $user = movementOperator();
    actingAs($user);

    $product = Product::factory()->create();

    $response = postJson(route('admin.products.batches.store', $product), [
        'expire_date' => '2024-12-31',
        'note' => 'สร้างผ่าน ajax',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('batch.lot_no', 'LOT-02');
    $response->assertJsonPath('batch.qty', 0);
    $response->assertJsonPath('batch.expire_date', '2024-12-31');
    $response->assertJsonPath('batch.expire_date_th', '31 ธ.ค. 2024');

    $this->assertDatabaseHas('product_batches', [
        'product_id' => $product->id,
        'lot_no' => 'LOT-02',
        'expire_date' => '2024-12-31 00:00:00',
    ]);

    $invalid = postJson(route('admin.products.batches.store', $product), [
        'expire_date' => '31-12-2024',
    ]);

    $invalid->assertStatus(422);
    $invalid->assertJsonValidationErrors(['expire_date']);
});

it('prevents simultaneous issues from driving the quantity negative', function (): void {
    Carbon::setTestNow('2024-05-05 07:45:00');

    $user = movementOperator();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);
    $batch = $product->batches()->where('lot_no', 'LOT-01')->firstOrFail();
    $batch->update(['qty' => 20]);

    /** @var StockMovementService $service */
    $service = app(StockMovementService::class);

    $movementA = $service->issue($product->fresh(), 10, 'LOT-01', 'เบิกรอบที่ 1');
    $movementB = $service->issue($product->fresh(), 10, 'LOT-01', 'เบิกรอบที่ 2');

    $batch->refresh();
    $product->refresh();

    expect([$movementA->type, $movementB->type])->toEqual(['issue', 'issue'])
        ->and($batch->qty)->toBe(0)
        ->and($product->qty)->toBe(0);

    expect(fn () => $service->issue($product->fresh(), 1, 'LOT-01', 'ลองติดลบ'))
        ->toThrow(ValidationException::class);
});
