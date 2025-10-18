<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('shows import-export menu for privileged users when feature enabled', function (string $role) {
    config()->set('inventory.import_enabled', true);

    $user = User::factory()->create(['role' => $role]);
    actingAs($user);

    $dashboard = get(route('admin.dashboard'));
    $dashboard->assertOk();
    $dashboard->assertSee('นำเข้าส่งออกไฟล์', false);
    $dashboard->assertSee(route('import_export.index'), false);

    get(route('import_export.index'))->assertOk();
})->with([
    'staff',
    'admin',
]);
