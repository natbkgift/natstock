<?php

namespace App\Console\Commands;

use App\Notifications\InventoryAlertNotification;
use App\Services\InventoryAlertService;
use App\Services\LineNotifyService;
use App\Support\Settings\SettingManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Mail\InventoryDailySummaryMail;

class InventoryScanAlertsCommand extends Command
{
    protected $signature = 'inventory:scan-alerts';

    protected $description = 'สแกนสินค้าใกล้หมดอายุและสต็อกต่ำ พร้อมส่งการแจ้งเตือนอัตโนมัติ';

    public function __construct(
        private readonly InventoryAlertService $alertService,
        private readonly SettingManager $settings,
        private readonly LineNotifyService $lineNotifyService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $channels = $this->settings->getNotifyChannels();
        $summary = $this->alertService->collectSummary();

        Log::channel('daily')->info('เริ่มสแกนแจ้งเตือนสินค้า', [
            'channels' => $channels,
            'expiring' => array_map(fn ($bucket) => ['days' => $bucket['days'], 'count' => $bucket['count']], $summary['expiring']),
            'low_stock_count' => $summary['low_stock']['count'],
        ]);

        $this->sendInAppNotifications($channels, $summary);
        $this->sendEmailNotifications($channels, $summary);
        $this->sendLineNotification($channels, $summary);

        Log::channel('daily')->info('สแกนแจ้งเตือนสินค้าเสร็จสิ้น');
        $this->info('สแกนแจ้งเตือนสินค้าเสร็จสิ้น');

        return Command::SUCCESS;
    }

    private function sendInAppNotifications(array $channels, array $summary): void
    {
        if (! in_array('inapp', $channels, true)) {
            return;
        }

        $recipients = $this->alertService->resolveRecipients();
        if (empty($recipients)) {
            return;
        }

        $payload = [
            'summary' => $summary,
            'links' => [
                'expiring' => URL::route('admin.reports.expiring'),
                'low_stock' => URL::route('admin.reports.low-stock'),
            ],
        ];

        foreach ($recipients as $recipient) {
            $recipient->notify(new InventoryAlertNotification($payload));
        }
    }

    private function sendEmailNotifications(array $channels, array $summary): void
    {
        if (! in_array('email', $channels, true)) {
            return;
        }

        $emails = $this->settings->getNotifyEmails();
        if (empty($emails)) {
            Log::channel('daily')->warning('ข้ามการส่งอีเมลแจ้งเตือนเนื่องจากไม่มีอีเมลปลายทาง');

            return;
        }

        Mail::to($emails)->send(new InventoryDailySummaryMail($summary));
    }

    private function sendLineNotification(array $channels, array $summary): void
    {
        if (! in_array('line', $channels, true)) {
            return;
        }

        $message = $this->alertService->buildLineMessage($summary);
        $this->lineNotifyService->send($message);
    }
}
