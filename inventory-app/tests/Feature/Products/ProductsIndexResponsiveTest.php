<?php

use function Pest\Laravel\get;
use function Pest\Laravel\actingAs;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders products index mobile-friendly', function (): void {
    // Authenticate as staff
    $user = User::factory()->create(['role' => 'staff']);
    actingAs($user);

    // Ensure there is at least one product so action buttons render
    Product::factory()->create();

    // Render products index
    $response = get(route('admin.products.index'));
    $response->assertStatus(200);

    // Filters should stack on mobile (have col-12)
    $response->assertSee('form-row align-items-end flex-md-nowrap');
    $response->assertSee('form-group col-md-4 col-12');
    $response->assertSee('form-group col-md-3 col-12');
    $response->assertSee('form-group col-md-2 col-12');

    // Action buttons container should allow wrap
    $response->assertSee('btn-group btn-group-sm d-flex flex-wrap');

    // Buttons have spacing utilities
    $response->assertSee('btn btn-outline-secondary mb-1 mr-1');
    $response->assertSee('btn btn-outline-danger mb-1 mr-1');
    $response->assertSee('btn btn-outline-primary mb-1');
});
