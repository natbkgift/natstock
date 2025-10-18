<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('renders persistent dashboard alert cards without popup actions', function () {
    Carbon::setTestNow('2024-05-12 09:00:00');

    $category = Category::factory()->create();

    $lowStockProduct = Product::factory()->create([
        'category_id' => $category->id,
        'reorder_point' => 15,
        'qty' => 5,
        'is_active' => true,
    ]);

    $expiringProduct = Product::factory()->create([
        'category_id' => $category->id,
        'reorder_point' => 5,
        'qty' => 20,
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $expiringProduct->id,
        'lot_no' => 'LOT-02',
        'qty' => 12,
        'expire_date' => Carbon::now()->addDays(3),
        'is_active' => true,
    ]);

    $user = User::factory()->create(['role' => 'admin']);
    actingAs($user);

    $response = get(route('admin.dashboard'));

    $response->assertOk()
        ->assertSee('สินค้าสต็อกต่ำ', false)
        ->assertSee($lowStockProduct->sku, false)
        ->assertSee('ล็อตใกล้หมดอายุ / หมดอายุ', false)
        ->assertSee('LOT-02', false)
        ->assertSee('ดูรายงานสต็อกต่ำทั้งหมด', false)
        ->assertSee('ดูรายงานล็อตครบกำหนดทั้งหมด', false)
        ->assertDontSee('dashboardAlertModal', false)
        ->assertDontSee('งดเตือน', false)
        ->assertDontSee('ทำเครื่องหมายว่าอ่านแล้ว', false);
});
