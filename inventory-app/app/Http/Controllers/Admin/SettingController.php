<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SystemSettingRequest;
use App\Services\AuditLogger;
use App\Services\NotificationTestService;
use App\Support\Settings\SettingManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingManager $settings,
        private readonly NotificationTestService $testService,
        private readonly AuditLogger $auditLogger
    ) {
    }

    public function index(Request $request): View
    {
        Gate::authorize('access-admin');

        $notifyChannels = $this->settings->getNotifyChannels();
        $availableChannels = config('inventory.notify_channel_options', []);

        return view('admin.settings.index', [
            'values' => [
                'site_name' => $this->settings->getSiteName(),
                'alert_expiring_days' => $this->settings->getString('alert_expiring_days'),
                'expiring_days' => $this->settings->getExpiringLeadDays(),
                'notify_low_stock' => $this->settings->getBool('notify_low_stock', true),
                'low_stock_enabled' => $this->settings->shouldNotifyLowStock(),
                'expiring_enabled' => $this->settings->isExpiringAlertEnabled(),
                'notify_channels' => $notifyChannels,
                'notify_emails' => $this->settings->getString('notify_emails'),
                'daily_scan_time' => $this->settings->getString('daily_scan_time'),
            ],
            'channelOptions' => $availableChannels,
            'lineTokenConfigured' => filled(config('services.line_notify.token')),
        ]);
    }

    public function update(SystemSettingRequest $request): RedirectResponse
    {
        Gate::authorize('access-admin');

        $data = $request->validated();

        $this->settings->setString('site_name', $data['site_name']);
        $normalizedDays = preg_replace('/\s+/', '', $data['alert_expiring_days']);
        $this->settings->setString('alert_expiring_days', (string) $normalizedDays);
        $this->settings->setString('expiring_days', (string) $data['expiring_days']);
        $this->settings->setBool('notify_low_stock', $request->boolean('notify_low_stock'));
        $this->settings->setBool('low_stock_enabled', $request->boolean('low_stock_enabled'));
        $this->settings->setBool('expiring_enabled', $request->boolean('expiring_enabled'));
        $this->settings->setArray('notify_channels', $data['notify_channels']);
        $this->settings->setString('notify_emails', (string) ($data['notify_emails'] ?? ''));
        $this->settings->setString('daily_scan_time', $data['daily_scan_time']);

        $this->auditLogger->log(
            'settings.updated',
            'อัปเดตการตั้งค่าระบบแจ้งเตือน',
            [
                'alert_expiring_days' => $normalizedDays,
                'expiring_days' => (int) $data['expiring_days'],
                'notify_low_stock' => $request->boolean('notify_low_stock'),
                'low_stock_enabled' => $request->boolean('low_stock_enabled'),
                'expiring_enabled' => $request->boolean('expiring_enabled'),
                'notify_channels' => $data['notify_channels'],
                'notify_emails' => $data['notify_emails'] ?? '',
                'daily_scan_time' => $data['daily_scan_time'],
                'site_name' => $data['site_name'],
            ],
            null,
            $request->user(),
        );

        return redirect()->route('admin.settings.index')->with('status', 'บันทึกการตั้งค่าระบบเรียบร้อยแล้ว');
    }

    public function testNotification(Request $request): RedirectResponse
    {
        Gate::authorize('access-admin');

        $channels = $this->settings->getNotifyChannels();

        $result = $this->testService->sendTestNotification($channels, $request->user());

        $this->auditLogger->log(
            'settings.test_notification',
            'ทดสอบการส่งการแจ้งเตือนจากหน้าตั้งค่า',
            ['channels' => $channels],
            null,
            $request->user(),
        );

        return redirect()->route('admin.settings.index')->with($result['status'], $result['message']);
    }

    // Optional GET endpoint to trigger test notification when accessed via URL directly
    public function testNotificationGet(Request $request): RedirectResponse
    {
        return $this->testNotification($request);
    }

    public function runScan(Request $request): RedirectResponse
    {
        Gate::authorize('access-admin');

        try {
            Artisan::call('inventory:scan-alerts');
        } catch (Throwable $exception) {
            Log::error('ไม่สามารถเรียกใช้คำสั่งสแกนแจ้งเตือนได้', [
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('admin.settings.index')
                ->with('error', 'ไม่สามารถดำเนินการสแกนแจ้งเตือนประจำวันได้ กรุณาลองใหม่อีกครั้ง');
        }

        $this->auditLogger->log(
            'alerts.scan_manual',
            'ผู้ใช้เรียกสแกนแจ้งเตือนสินค้าด้วยตนเอง',
            [],
            null,
            $request->user(),
        );

        return redirect()->route('admin.settings.index')->with('status', 'ดำเนินการสแกนแจ้งเตือนประจำวันเรียบร้อยแล้ว');
    }
}
