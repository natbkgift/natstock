<?php

namespace App\Console\Commands;

use App\Models\UserAlertState;
use App\Notifications\InventoryAlertNotification;
use App\Services\AlertSnapshotService;
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
        private readonly AlertSnapshotService $snapshotService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $channels = $this->settings->getNotifyChannels();
        $snapshot = $this->snapshotService->buildSnapshot();
        $summary = $this->alertService->collectSummary($snapshot);

        Log::channel('daily')->info('เริ่มสแกนแจ้งเตือนสินค้า', [
            'channels' => $channels,
            'expiring' => [
                'enabled' => $summary['expiring']['enabled'],
                'days' => $summary['expiring']['days'],
                'count' => $summary['expiring']['count'],
            ],
            'low_stock' => [
                'enabled' => $summary['low_stock']['enabled'],
                'count' => $summary['low_stock']['count'],
            ],
        ]);

        $recipients = $this->alertService->resolveRecipients();
        $this->syncUserAlertStates($recipients, $snapshot);

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
                'expiring' => URL::route('admin.reports.expiring-batches'),
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

    private function syncUserAlertStates(array $recipients, array $snapshot): void
    {
        if (empty($recipients)) {
            return;
        }

        $typeMap = [
            'low_stock' => 'low_stock',
            'expiring' => 'expiring',
        ];

        foreach ($typeMap as $alertType => $key) {
            $payloadHash = $snapshot[$key]['payload_hash'] ?? null;

            if (! $payloadHash) {
                continue;
            }

            foreach ($recipients as $user) {
                UserAlertState::query()->firstOrCreate([
                    'user_id' => $user->id,
                    'alert_type' => $alertType,
                    'payload_hash' => $payloadHash,
                ]);
            }
        }
    }
}
