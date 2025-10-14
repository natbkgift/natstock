<?php

namespace App\Services;

use App\Services\AlertSnapshotService;
use App\Support\Settings\SettingManager;
use Carbon\Carbon;

class InventoryAlertService
{
    public function __construct(
        private readonly SettingManager $settings,
        private readonly AlertSnapshotService $snapshotService
    )
    {
    }

    /**
     * @param array<string, array<string, mixed>>|null $snapshot
     * @return array{
     *     expiring: array{enabled: bool, days: int, count: int, items: array<int, array<string, mixed>>},
     *     low_stock: array{enabled: bool, count: int, items: array<int, array<string, mixed>>}
     * }
     */
    public function collectSummary(?array $snapshot = null): array
    {
        $snapshot ??= $this->snapshotService->buildSnapshot();

        $expiringSnapshot = $snapshot['expiring'];
        $lowStockSnapshot = $snapshot['low_stock'];

        $expiringItems = [];

        if ($expiringSnapshot['enabled'] && $expiringSnapshot['count'] > 0) {
            $expiringItems = collect($expiringSnapshot['items'])
                ->take(5)
                ->map(function (array $item) use ($expiringSnapshot): array {
                    $expireDate = $item['expire_date'] ?? null;
                    $thaiDate = $expireDate ? Carbon::parse($expireDate)->format('d/m/Y') : null;

                    return [
                        'id' => $item['id'],
                        'sku' => $item['sku'],
                        'name' => $item['name'],
                        'lot_no' => $item['lot_no'] ?? $item['sub_sku'],
                        'sub_sku' => $item['lot_no'] ?? $item['sub_sku'],
                        'expire_date' => $expireDate,
                        'expire_date_thai' => $thaiDate,
                        'qty' => $item['qty'],
                        'days' => $expiringSnapshot['days'],
                    ];
                })
                ->all();
        }

        $lowStockItems = [];

        if ($lowStockSnapshot['enabled'] && $lowStockSnapshot['count'] > 0) {
            $lowStockItems = collect($lowStockSnapshot['items'])
                ->take(5)
                ->map(function (array $item): array {
                    return [
                        'id' => $item['id'],
                        'sku' => $item['sku'],
                        'name' => $item['name'],
                        'qty' => $item['qty_total'],
                        'reorder_point' => $item['reorder_point'],
                    ];
                })
                ->all();
        }

        return [
            'expiring' => [
                'enabled' => $expiringSnapshot['enabled'],
                'days' => $expiringSnapshot['days'],
                'count' => $expiringSnapshot['count'],
                'items' => $expiringItems,
            ],
            'low_stock' => [
                'enabled' => $lowStockSnapshot['enabled'],
                'count' => $lowStockSnapshot['count'],
                'items' => $lowStockItems,
            ],
        ];
    }

    /**
     * @return array<int, User>
     */
    public function resolveRecipients(): array
    {
        return $this->snapshotService->resolveRecipients()->all();
    }

    public function buildLineMessage(array $summary): string
    {
        Carbon::setLocale('th');
        $lines = [];
        $today = Carbon::now('Asia/Bangkok')->translatedFormat('d F Y');
        $lines[] = 'สรุปแจ้งเตือนสินค้าประจำวัน ('.$today.')';

        if ($summary['expiring']['enabled']) {
            $lines[] = '- ล็อตใกล้หมดอายุภายใน '.$summary['expiring']['days'].' วัน: '.$summary['expiring']['count'].' รายการ';
        }

        if ($summary['low_stock']['enabled']) {
            $lines[] = '- สินค้าสต็อกต่ำ: '.$summary['low_stock']['count'].' รายการ';
        }

        $lines[] = 'ตรวจสอบเพิ่มเติมที่ระบบคลังสินค้า';

        return implode("\n", $lines);
    }
}
