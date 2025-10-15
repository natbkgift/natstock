<?php

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('shows product summary without pricing information', function () {
    $user = User::factory()->create(['role' => 'admin']);
    actingAs($user);

    Carbon::setTestNow(now());

    $productA = Product::factory()->create([
        'sku' => 'SKU-9001',
        'name' => 'หน้ากากอนามัยรุ่นพรีเมียม',
        'qty' => 40,
    ]);
    $productB = Product::factory()->create([
        'sku' => 'SKU-9002',
        'name' => 'สเปรย์ฆ่าเชื้ออเนกประสงค์',
        'qty' => 20,
    ]);

    $batchA = $productA->batches()->first();
    $batchB = $productB->batches()->first();

    $batchA->update(['qty' => 40, 'lot_no' => 'LOT-A1']);
    $batchB->update(['qty' => 18, 'lot_no' => 'LOT-B1']);

    StockMovement::create([
        'product_id' => $productA->id,
        'batch_id' => $batchA->id,
        'type' => 'receive',
        'qty' => 40,
        'note' => 'รับเข้ารอบเช้า',
        'actor_id' => $user->id,
        'happened_at' => now()->subMinutes(5),
    ]);

    StockMovement::create([
        'product_id' => $productB->id,
        'batch_id' => $batchB->id,
        'type' => 'issue',
        'qty' => 2,
        'note' => 'เบิกใช้หน่วยงานภายใน',
        'actor_id' => $user->id,
        'happened_at' => now()->subMinutes(2),
    ]);

    $response = get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('สรุปรายการสินค้า', false);
    $response->assertSee('SKU-9001', false);
    $response->assertSee('หน้ากากอนามัยรุ่นพรีเมียม', false);
    $response->assertSee('SKU-9002', false);
    $response->assertSee('สเปรย์ฆ่าเชื้ออเนกประสงค์', false);
    $response->assertDontSee('มูลค่าสต็อกตามราคาทุนรวม', false);
    $response->assertSee('รับเข้า SKU-9001', false);
    $response->assertSee('เบิกออก SKU-9002', false);
});
