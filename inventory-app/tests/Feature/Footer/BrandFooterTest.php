<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders nat stock brand footer with current year', function () {
    $year = now()->year;

    $user = User::factory()->create(['role' => 'staff']);
    actingAs($user);

    $response = get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertSee("© {$year}", false);
    $response->assertSee('Nat Stock V 1.5', false);
    $response->assertSee('text-decoration-none', false);
    $response->assertSee('สงวนลิขสิทธิ์', false);
});
