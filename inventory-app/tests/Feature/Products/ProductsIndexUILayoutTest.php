<?php

use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createStaff(): User {
    return User::factory()->create(['role' => 'staff']);
}

it('renders low-stock switch with auto width and nowrap label', function () {
    $user = createStaff();
    actingAs($user);

    $response = get(route('admin.products.index'));
    $response->assertStatus(200);

    // ตรวจสอบว่ามี custom switch และป้าย "สต็อกต่ำ"
    $response->assertSee('custom-switch', false);
    $response->assertSee('สต็อกต่ำ', false);

    // ตรวจสอบ style ที่ใช้ความกว้างอัตโนมัติและไม่ตัดบรรทัด
    $response->assertSee('flex-grow-0', false);
    $response->assertSee('white-space:nowrap', false);
});
