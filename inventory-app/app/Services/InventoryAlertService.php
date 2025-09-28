<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Support\Settings\SettingManager;
use Carbon\Carbon;

class InventoryAlertService
{
    public function __construct(private readonly SettingManager $settings)
    {
    }

    /**
     * @return array{
     *     expiring: array<int, array{days: int, count: int, items: array<int, array<string, mixed>>}>,
     *     low_stock: array{enabled: bool, count: int, items: array<int, array<string, mixed>>}
     * }
     */
    public function collectSummary(): array
    {
        $expiring = [];
        $daysList = $this->settings->getExpiringDays();

        foreach ($daysList as $days) {
            $query = Product::query()
                ->active()
                ->expiringIn($days)
                ->orderBy('expire_date')
                ->orderBy('name');

            $count = (clone $query)->count();
            $items = $query->limit(5)->get(['id', 'sku', 'name', 'expire_date', 'qty'])->map(function (Product $product) use ($days) {
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'expire_date' => optional($product->expire_date)->format('Y-m-d'),
                    'expire_date_thai' => optional($product->expire_date)->format('d/m/Y'),
                    'qty' => $product->qty,
                    'days' => $days,
                ];
            })->all();

            $expiring[] = [
                'days' => $days,
                'count' => $count,
                'items' => $items,
            ];
        }

        $lowStockEnabled = $this->settings->shouldNotifyLowStock();
        $lowStockCount = 0;
        $lowStockItems = [];

        if ($lowStockEnabled) {
            $lowStockQuery = Product::query()
                ->active()
                ->where('reorder_point', '>', 0)
                ->whereColumn('qty', '<=', 'reorder_point')
                ->orderBy('qty')
                ->orderBy('name');

            $lowStockCount = (clone $lowStockQuery)->count();
            $lowStockItems = $lowStockQuery->limit(5)->get(['id', 'sku', 'name', 'qty', 'reorder_point'])->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'qty' => $product->qty,
                    'reorder_point' => $product->reorder_point,
                ];
            })->all();
        }

        return [
            'expiring' => $expiring,
            'low_stock' => [
                'enabled' => $lowStockEnabled,
                'count' => $lowStockCount,
                'items' => $lowStockItems,
            ],
        ];
    }

    /**
     * @return array<int, User>
     */
    public function resolveRecipients(): array
    {
        return User::query()
            ->whereIn('role', ['admin', 'staff'])
            ->get()
            ->all();
    }

    public function buildLineMessage(array $summary): string
    {
        Carbon::setLocale('th');
        $lines = [];
        $today = Carbon::now('Asia/Bangkok')->translatedFormat('d F Y');
        $lines[] = 'สรุปแจ้งเตือนสินค้าประจำวัน ('.$today.')';

        foreach ($summary['expiring'] as $bucket) {
            $lines[] = '- สินค้าใกล้หมดอายุภายใน '.$bucket['days'].' วัน: '.$bucket['count'].' รายการ';
        }

        if ($summary['low_stock']['enabled']) {
            $lines[] = '- สินค้าสต็อกต่ำ: '.$summary['low_stock']['count'].' รายการ';
        }

        $lines[] = 'ตรวจสอบเพิ่มเติมที่ระบบคลังสินค้า';

        return implode("\n", $lines);
    }
}
