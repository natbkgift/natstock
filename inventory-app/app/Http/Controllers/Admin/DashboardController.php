<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\UserAlertState;
use App\Services\AlertSnapshotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __construct(private readonly AlertSnapshotService $alerts)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('access-viewer');

        $pricingEnabled = (bool) config('inventory.enable_price');
        $snapshot = $this->alerts->buildSnapshot();
        $user = $request->user();

        $lowStockSnapshot = $snapshot['low_stock'];
        $expiringSnapshot = $snapshot['expiring'];

        $lowStockCount = $lowStockSnapshot['count'];
        $expiringCount = $expiringSnapshot['count'];

        $stockValue = 0.0;
        $stockValueFormatted = 'ปิดใช้งาน';

        if ($pricingEnabled) {
            $stockValue = (float) ($this->totalStockValue() ?? 0);
            $stockValueFormatted = $this->formatThaiNumber($stockValue);
        }

        $recentMovements = StockMovement::query()
            ->with(['product', 'actor'])
            ->orderByDesc('happened_at')
            ->limit(10)
            ->get();

        $alertStates = $this->resolveAlertStates($user?->id, $snapshot);
        $shouldShowModal = $alertStates['low_stock']['show'] || $alertStates['expiring']['show'];

        return view('admin.dashboard', [
            'expiringDays' => $expiringSnapshot['days'],
            'expiringCount' => $expiringCount,
            'lowStockCount' => $lowStockCount,
            'pricingEnabled' => $pricingEnabled,
            'stockValueFormatted' => $stockValueFormatted,
            'recentMovements' => $recentMovements,
            'alertStates' => $alertStates,
            'shouldShowAlerts' => $shouldShowModal,
        ]);
    }

    private function formatThaiNumber(float $value): string
    {
        // แสดงเลขอารบิก ไม่มีทศนิยม
        return number_format($value, 0);
    }

    private function totalStockValue(): ?string
    {
        return \App\Models\Product::query()->selectRaw('SUM(qty * cost_price) as total')->value('total');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function resolveAlertStates(?int $userId, array $snapshot): array
    {
        $result = [
            'low_stock' => [
                'show' => false,
                'count' => $snapshot['low_stock']['count'],
                'items' => array_slice($snapshot['low_stock']['items'], 0, 10),
                'payload_hash' => $snapshot['low_stock']['payload_hash'],
            ],
            'expiring' => [
                'show' => false,
                'count' => $snapshot['expiring']['count'],
                'items' => array_slice($snapshot['expiring']['items'], 0, 10),
                'payload_hash' => $snapshot['expiring']['payload_hash'],
                'days' => $snapshot['expiring']['days'],
            ],
        ];

        if ($userId === null) {
            return $result;
        }

        foreach (['low_stock', 'expiring'] as $type) {
            $payloadHash = $result[$type]['payload_hash'];

            if (! $snapshot[$type]['enabled'] || $result[$type]['count'] <= 0 || ! $payloadHash) {
                continue;
            }

            $state = UserAlertState::query()
                ->where('user_id', $userId)
                ->where('alert_type', $type)
                ->where('payload_hash', $payloadHash)
                ->first();

            if ($state === null || (! $state->isRead() && ! $state->isSnoozed())) {
                $result[$type]['show'] = true;
            }
        }

        return $result;
    }
}
