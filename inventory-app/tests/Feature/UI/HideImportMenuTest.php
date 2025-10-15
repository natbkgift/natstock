<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('hides import menu entries from the navigation', function () {
    $user = User::factory()->create(['role' => 'staff']);
    actingAs($user);

    $response = get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertDontSee('นำเข้าไฟล์', false);
    $response->assertDontSee('นำเข้าส่งออกไฟล์', false);
});

it('blocks direct access to import related routes', function () {
    $user = User::factory()->create(['role' => 'staff']);
    actingAs($user);

    get(route('admin.import.index'))->assertNotFound();
    get(route('import_export.index'))->assertNotFound();
});
