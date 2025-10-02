<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

test('admin can trigger test notification via get endpoint', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('admin.settings.test-notification.get'))
        ->assertRedirect(route('admin.settings.index'))
        ->assertSessionHas('status');
});
