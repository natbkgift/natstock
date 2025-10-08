<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use App\Models\UserAlertState;
use App\Services\AlertSnapshotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function createDashboardUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function seedAlertFixtures(): void
{
    $category = Category::factory()->create();

    $lowStockProduct = Product::factory()->create([
        'category_id' => $category->id,
        'reorder_point' => 15,
        'is_active' => true,
        'qty' => 0,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $lowStockProduct->id,
        'sub_sku' => 'LOW-A',
        'qty' => 5,
        'expire_date' => Carbon::now()->addDays(7),
        'is_active' => true,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $lowStockProduct->id,
        'sub_sku' => 'LOW-B',
        'qty' => 3,
        'expire_date' => Carbon::now()->addDays(10),
        'is_active' => true,
    ]);

    $expiringProduct = Product::factory()->create([
        'category_id' => $category->id,
        'reorder_point' => 5,
        'is_active' => true,
        'qty' => 0,
    ]);

    ProductBatch::factory()->create([
        'product_id' => $expiringProduct->id,
        'sub_sku' => 'EXP-SOON',
        'qty' => 12,
        'expire_date' => Carbon::now()->addDays(5),
        'is_active' => true,
    ]);
}

it('displays persistent alert modal on dashboard when data exists', function () {
    Carbon::setTestNow('2024-05-03 09:30:00');

    $user = createDashboardUser();
    actingAs($user);
    seedAlertFixtures();

    $response = get(route('admin.dashboard'));
    $response->assertOk()
        ->assertSee('แจ้งเตือนสถานะคลังสินค้า')
        ->assertSee('สต็อกต่ำ (')
        ->assertSee('ล็อตใกล้หมดอายุ (');
});

it('marks alerts as read and hides modal after acknowledgement', function () {
    Carbon::setTestNow('2024-05-03 10:00:00');

    $user = createDashboardUser();
    actingAs($user);
    seedAlertFixtures();

    $snapshot = app(AlertSnapshotService::class)->buildSnapshot();
    $lowHash = $snapshot['low_stock']['payload_hash'];
    $expiringHash = $snapshot['expiring']['payload_hash'];

    postJson(route('admin.alerts.mark-read'), [
        'type' => 'low_stock',
        'payload_hash' => $lowHash,
    ])->assertOk();

    postJson(route('admin.alerts.mark-read'), [
        'type' => 'expiring',
        'payload_hash' => $expiringHash,
    ])->assertOk();

    $this->assertDatabaseHas(UserAlertState::class, [
        'user_id' => $user->id,
        'alert_type' => 'low_stock',
        'payload_hash' => $lowHash,
    ]);

    $this->assertDatabaseHas(UserAlertState::class, [
        'user_id' => $user->id,
        'alert_type' => 'expiring',
        'payload_hash' => $expiringHash,
    ]);

    $response = get(route('admin.dashboard'));
    $response->assertOk()
        ->assertDontSee('แจ้งเตือนสถานะคลังสินค้า');
});

it('snoozes alerts until end of day and suppresses modal', function () {
    Carbon::setTestNow(Carbon::parse('2024-05-04 14:15:00'));

    $user = createDashboardUser();
    actingAs($user);
    seedAlertFixtures();

    $snapshot = app(AlertSnapshotService::class)->buildSnapshot();
    $lowHash = $snapshot['low_stock']['payload_hash'];

    $response = postJson(route('admin.alerts.snooze'), [
        'type' => 'low_stock',
        'payload_hash' => $lowHash,
    ]);

    $response->assertOk();

    $state = UserAlertState::query()
        ->where('user_id', $user->id)
        ->where('alert_type', 'low_stock')
        ->where('payload_hash', $lowHash)
        ->first();

    expect($state)->not->toBeNull()
        ->and($state->snooze_until->format('Y-m-d H:i:s'))->toBe(Carbon::now()->endOfDay()->format('Y-m-d H:i:s'))
        ->and($state->read_at)->toBeNull();

    get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('แจ้งเตือนสถานะคลังสินค้า');
});

it('deduplicates alert states when the same payload hash is acknowledged repeatedly', function (): void {
    Carbon::setTestNow('2024-05-05 09:00:00');

    $user = createDashboardUser();
    actingAs($user);
    seedAlertFixtures();

    $snapshot = app(AlertSnapshotService::class)->buildSnapshot();
    $hash = $snapshot['low_stock']['payload_hash'];

    postJson(route('admin.alerts.mark-read'), [
        'type' => 'low_stock',
        'payload_hash' => $hash,
    ])->assertOk();

    postJson(route('admin.alerts.mark-read'), [
        'type' => 'low_stock',
        'payload_hash' => $hash,
    ])->assertOk();

    expect(UserAlertState::query()
        ->where('user_id', $user->id)
        ->where('alert_type', 'low_stock')
        ->where('payload_hash', $hash)
        ->count())->toBe(1);
});

it('tracks alert state per user so other operators still receive the modal', function (): void {
    Carbon::setTestNow('2024-05-06 08:30:00');

    $admin = createDashboardUser();
    $staff = User::factory()->create(['role' => 'staff']);

    actingAs($admin);
    seedAlertFixtures();

    $snapshot = app(AlertSnapshotService::class)->buildSnapshot();
    $expiringHash = $snapshot['expiring']['payload_hash'];
    $lowHash = $snapshot['low_stock']['payload_hash'];

    postJson(route('admin.alerts.mark-read'), [
        'type' => 'expiring',
        'payload_hash' => $expiringHash,
    ])->assertOk();

    postJson(route('admin.alerts.mark-read'), [
        'type' => 'low_stock',
        'payload_hash' => $lowHash,
    ])->assertOk();

    get(route('admin.dashboard'))->assertOk()->assertDontSee('แจ้งเตือนสถานะคลังสินค้า');

    actingAs($staff);

    get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('แจ้งเตือนสถานะคลังสินค้า');

    expect(UserAlertState::query()->where('user_id', $staff->id)->count())->toBe(0);
});
