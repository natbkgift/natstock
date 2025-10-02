<?php

use App\Models\User;
use App\Support\Settings\SettingManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

test('admin can trigger test notification via get endpoint', function () {
    app(SettingManager::class)->setArray('notify_channels', ['inapp']);

    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('admin.settings.test-notification.get'))
        ->assertRedirect(route('admin.settings.index'))
        ->assertSessionHas('status');
});

test('admin can trigger manual scan via get endpoint', function () {
    $user = User::factory()->create(['role' => 'admin']);

    Artisan::shouldReceive('call')
        ->once()
        ->with('inventory:scan-alerts')
        ->andReturn(0);

    actingAs($user)
        ->get(route('admin.settings.run-scan.get'))
        ->assertRedirect(route('admin.settings.index'))
        ->assertSessionHas('status', 'ดำเนินการสแกนแจ้งเตือนประจำวันเรียบร้อยแล้ว');
});

test('manual scan route reports error when artisan command fails', function () {
    $user = User::factory()->create(['role' => 'admin']);

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) {
            expect($message)->toBe('ไม่สามารถเรียกใช้คำสั่งสแกนแจ้งเตือนได้');
            expect($context)->toHaveKey('error');
            expect($context['error'])->toBe('failed to run command');

            return true;
        });

    Artisan::shouldReceive('call')
        ->once()
        ->with('inventory:scan-alerts')
        ->andThrow(new \Exception('failed to run command'));

    actingAs($user)
        ->get(route('admin.settings.run-scan.get'))
        ->assertRedirect(route('admin.settings.index'))
        ->assertSessionHas('error', 'ไม่สามารถดำเนินการสแกนแจ้งเตือนประจำวันได้ กรุณาลองใหม่อีกครั้ง');
});
