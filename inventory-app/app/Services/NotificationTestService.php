<?php

namespace App\Services;

use App\Mail\InventoryDailySummaryMail;
use App\Models\User;
use App\Notifications\InventoryAlertNotification;
use App\Support\Settings\SettingManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class NotificationTestService
{
    public function __construct(
        private readonly SettingManager $settings,
        private readonly InventoryAlertService $alertService,
        private readonly LineNotifyService $lineNotifyService,
    ) {
    }

    /**
     * @param array<int, string> $channels
     * @return array{status: string, message: string}
     */
    public function sendTestNotification(array $channels, ?User $tester = null): array
    {
        if (empty($channels)) {
            return [
                'status' => 'warning',
                'message' => 'ยังไม่ได้เปิดใช้งานช่องทางการแจ้งเตือนใด ๆ',
            ];
        }

        $summary = $this->alertService->collectSummary();
        $sentChannels = [];

        if (in_array('inapp', $channels, true) && $tester !== null) {
            $payload = [
                'summary' => $summary,
                'links' => [
                    'expiring' => URL::route('admin.reports.expiring'),
                    'low_stock' => URL::route('admin.reports.low-stock'),
                ],
                'is_test' => true,
            ];
            $tester->notify(new InventoryAlertNotification($payload));
            $sentChannels[] = config('inventory.notify_channel_options.inapp');
        }

        if (in_array('email', $channels, true)) {
            $emails = $this->settings->getNotifyEmails();
            if (! empty($emails)) {
                Mail::to($emails)->send(InventoryDailySummaryMail::forTest($summary));
                $sentChannels[] = config('inventory.notify_channel_options.email');
            } else {
                Log::channel('daily')->warning('ไม่สามารถส่งอีเมลทดสอบได้เนื่องจากไม่มีอีเมลปลายทาง');
            }
        }

        if (in_array('line', $channels, true)) {
            $message = '[ทดสอบ] ระบบคลังสินค้าแจ้งเตือนทดสอบจากหน้าตั้งค่า';
            if ($this->lineNotifyService->send($message)) {
                $sentChannels[] = config('inventory.notify_channel_options.line');
            }
        }

        if (empty($sentChannels)) {
            return [
                'status' => 'warning',
                'message' => 'ไม่สามารถส่งการแจ้งเตือนทดสอบได้ กรุณาตรวจสอบการตั้งค่า',
            ];
        }

        return [
            'status' => 'status',
            'message' => 'ส่งการแจ้งเตือนทดสอบไปยังช่องทาง: '.implode(', ', $sentChannels),
        ];
    }
}
