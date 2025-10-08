<?php

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use App\Services\BatchResolver;
use App\Services\StockMovementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createStaffUser(): User
{
    return User::factory()->create(['role' => 'staff']);
}

it('receives stock and creates a new batch when needed', function () {
    $user = createStaffUser();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);
    $service = app(StockMovementService::class);

    Carbon::setTestNow(Carbon::parse('2024-04-01 10:00:00'));

    $result = $service->receive(
        $product,
        12,
        'LOT-NEW',
        Carbon::parse('2024-10-31'),
        'รับเข้าล็อตใหม่'
    );

    expect($result['batch']->sub_sku)->toBe('LOT-NEW')
        ->and($result['batch_after'])->toBe(12)
        ->and($result['product_after'])->toBe(12)
        ->and($result['movement']->type)->toBe('in');

    $product->refresh();

    expect($product->qty)->toBe(12)
        ->and(ProductBatch::where('product_id', $product->id)->where('sub_sku', 'LOT-NEW')->count())->toBe(1);
});

it('issues stock from unspecified batch automatically', function () {
    $user = createStaffUser();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0, 'sku' => 'ITEM-001']);
    $batch = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => $product->sku . '-UNSPECIFIED',
        'qty' => 15,
    ]);

    $service = app(StockMovementService::class);
    $result = $service->issue($product->fresh(), 5, null, 'เบิกออกทั่วไป');

    $batch->refresh();
    $product->refresh();

    expect($result['batch']->id)->toBe($batch->id)
        ->and($batch->qty)->toBe(10)
        ->and($product->qty)->toBe(10)
        ->and($result['movement']->type)->toBe('out');
});

it('adjusts batch quantity and records movement', function () {
    $user = createStaffUser();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);
    $batch = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'LOT-ADJ',
        'qty' => 20,
    ]);

    $service = app(StockMovementService::class);
    $result = $service->adjust($product->fresh(), 5, 'LOT-ADJ', 'ปรับยอดนับจริง');

    $batch->refresh();
    $product->refresh();

    expect($batch->qty)->toBe(5)
        ->and($product->qty)->toBe(5)
        ->and($result['movement']->type)->toBe('adjust')
        ->and($result['movement']->qty)->toBe(15);
});

it('aggregates product quantity across multiple batches after movements', function (): void {
    $user = createStaffUser();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);
    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'LOT-A',
        'qty' => 6,
    ]);
    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'LOT-B',
        'qty' => 4,
    ]);

    $service = app(StockMovementService::class);
    $service->receive($product->fresh(), 5, 'LOT-A', null, 'เติมล็อต A');
    $service->issue($product->fresh(), 2, 'LOT-B', 'เบิกล็อต B');

    expect($product->fresh()->qtyCurrent())->toBe(13);
});

it('prevents issuing more than available in a batch', function () {
    $user = createStaffUser();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);
    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'LOT-SMALL',
        'qty' => 3,
    ]);

    $service = app(StockMovementService::class);

    expect(fn () => $service->issue($product->fresh(), 5, 'LOT-SMALL', 'ทดสอบ'))
        ->toThrow(ValidationException::class);
});

it('rejects negative target quantity when adjusting a batch', function (): void {
    $user = createStaffUser();
    actingAs($user);

    $product = Product::factory()->create(['qty' => 0]);
    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'LOT-NEG',
        'qty' => 5,
    ]);

    $service = app(StockMovementService::class);

    expect(fn () => $service->adjust($product->fresh(), -1, 'LOT-NEG', 'ห้ามติดลบ'))
        ->toThrow(ValidationException::class);
});

it('creates product batch via ajax endpoint', function () {
    $user = createStaffUser();
    actingAs($user);

    $product = Product::factory()->create();

    $response = $this->postJson(route('admin.products.batches.store', $product), [
        'sub_sku' => 'LOT-AJAX',
        'expire_date' => '2024-12-31',
        'note' => 'สร้างผ่าน ajax',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('batch.sub_sku', 'LOT-AJAX');

    $this->assertDatabaseHas('product_batches', [
        'product_id' => $product->id,
        'sub_sku' => 'LOT-AJAX',
    ]);
});

it('rejects duplicate sub sku creation for a product', function () {
    $user = createStaffUser();
    actingAs($user);

    $product = Product::factory()->create();
    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => 'DUP-LOT',
    ]);

    $response = $this->postJson(route('admin.products.batches.store', $product), [
        'sub_sku' => 'DUP-LOT',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sub_sku']);
});

it('validates required sub sku when creating batch', function () {
    $user = createStaffUser();
    actingAs($user);

    $product = Product::factory()->create();

    $response = $this->postJson(route('admin.products.batches.store', $product), [
        'expire_date' => '2024-12-31',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sub_sku']);
});

it('maps the sentinel value to the unspecified batch automatically', function () {
    $user = createStaffUser();
    actingAs($user);

    $product = Product::factory()->create(['sku' => 'SKU-900']);
    $batch = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'sub_sku' => $product->sku . '-UNSPECIFIED',
        'qty' => 7,
    ]);

    $service = app(StockMovementService::class);

    $result = $service->receive($product->fresh(), 3, BatchResolver::UNSPECIFIED_TOKEN, null, 'เติมยอด default');

    $batch->refresh();

    expect($result['batch']->id)->toBe($batch->id)
        ->and($batch->qty)->toBe(10);
});
