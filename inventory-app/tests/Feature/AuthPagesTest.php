<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

test('หน้าล็อกอินแสดงผลภาษาไทย', function () {
    get('/login')->assertStatus(200)->assertSee('เข้าสู่ระบบหลังบ้าน');
});

test('เมื่อเข้าสู่ระบบแล้วเข้าถึงแดชบอร์ดได้', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertStatus(200)
        ->assertSee('แดชบอร์ดภาพรวม');
});
