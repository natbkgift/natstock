<?php

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\ActivityPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2024-05-01 08:15:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('presents receive movement in readable Thai', function () {
    $user = User::factory()->create(['role' => 'staff']);
    $product = Product::factory()->create(['sku' => 'SKU-5555', 'name' => 'ชุด PPE']);
    $batch = $product->batches()->first();
    $batch->update(['lot_no' => 'LOT-R1', 'qty' => 20]);

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'batch_id' => $batch->id,
        'type' => 'receive',
        'qty' => 20,
        'note' => null,
        'actor_id' => $user->id,
        'happened_at' => now(),
    ])->fresh(['product', 'batch', 'actor']);

    $presenter = app(ActivityPresenter::class);
    $text = $presenter->presentMovement($movement);

    expect($text)->toContain('รับเข้า SKU-5555 - ชุด PPE (LOT-R1) จำนวน +20');
    expect($text)->toContain('โดย ' . $user->name);
});

it('presents issue movement with negative quantity', function () {
    $user = User::factory()->create(['role' => 'staff']);
    $product = Product::factory()->create(['sku' => 'SKU-6666', 'name' => 'ถุงมือแพทย์']);
    $batch = $product->batches()->first();
    $batch->update(['lot_no' => 'LOT-I1', 'qty' => 12]);

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'batch_id' => $batch->id,
        'type' => 'issue',
        'qty' => 5,
        'note' => 'เบิกส่งห้องฉุกเฉิน',
        'actor_id' => $user->id,
        'happened_at' => now(),
    ])->fresh(['product', 'batch', 'actor']);

    $text = app(ActivityPresenter::class)->presentMovement($movement);

    expect($text)->toContain('เบิกออก SKU-6666 - ถุงมือแพทย์ (LOT-I1) จำนวน -5');
    expect($text)->toContain('โดย ' . $user->name);
});

it('presents adjust movement with before and after quantities', function () {
    $user = User::factory()->create(['role' => 'staff']);
    $product = Product::factory()->create(['sku' => 'SKU-7777', 'name' => 'แอลกอฮอล์ล้างมือ']);
    $batch = $product->batches()->first();
    $batch->update(['lot_no' => 'LOT-ADJ', 'qty' => 7]);

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'batch_id' => $batch->id,
        'type' => 'adjust',
        'qty' => 3,
        'note' => 'ตรวจนับ Δ-3',
        'actor_id' => $user->id,
        'happened_at' => now(),
    ])->fresh(['product', 'batch', 'actor']);

    $text = app(ActivityPresenter::class)->presentMovement($movement);

    expect($text)->toContain('ปรับยอด SKU-7777 - แอลกอฮอล์ล้างมือ (LOT-ADJ) จาก 10 เป็น 7');
    expect($text)->toContain('โดย ' . $user->name);
});
