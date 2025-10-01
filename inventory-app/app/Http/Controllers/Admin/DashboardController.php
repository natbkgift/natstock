<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('access-viewer');

        $expiringDays = (int) $request->input('expiring_days', 30);
        if (! in_array($expiringDays, [30, 60, 90], true)) {
            $expiringDays = 30;
        }

        $today = Carbon::today();
        $expiringBoundaries = [
            30 => $today->copy()->addDays(30),
            60 => $today->copy()->addDays(60),
            90 => $today->copy()->addDays(90),
        ];

        $expiringCountsResult = Product::query()
            ->whereNotNull('expire_date')
            ->selectRaw(
                'SUM(CASE WHEN expire_date BETWEEN :today AND :d30 THEN 1 ELSE 0 END) as expiring_30,
                  SUM(CASE WHEN expire_date BETWEEN :today AND :d60 THEN 1 ELSE 0 END) as expiring_60,
                  SUM(CASE WHEN expire_date BETWEEN :today AND :d90 THEN 1 ELSE 0 END) as expiring_90',
                [
                    'today' => $today->toDateString(),
                    'd30' => $expiringBoundaries[30]->toDateString(),
                    'd60' => $expiringBoundaries[60]->toDateString(),
                    'd90' => $expiringBoundaries[90]->toDateString(),
                ]
            )
            ->first();

        $expiringCounts = [
            30 => (int) ($expiringCountsResult->expiring_30 ?? 0),
            60 => (int) ($expiringCountsResult->expiring_60 ?? 0),
            90 => (int) ($expiringCountsResult->expiring_90 ?? 0),
        ];

        $selectedExpiringCount = $expiringCounts[$expiringDays];
        $lowStockCount = Product::query()->where('is_active', true)->lowStock()->count();
        $stockValue = (float) (Product::query()->selectRaw('SUM(qty * cost_price) as total')->value('total') ?? 0);
        $stockValueFormatted = $this->formatThaiNumber($stockValue);

        $recentMovements = StockMovement::query()
            ->with(['product', 'actor'])
            ->orderByDesc('happened_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'expiringDays' => $expiringDays,
            'expiringCounts' => $expiringCounts,
            'selectedExpiringCount' => $selectedExpiringCount,
            'lowStockCount' => $lowStockCount,
            'stockValueFormatted' => $stockValueFormatted,
            'recentMovements' => $recentMovements,
        ]);
    }

    private function formatThaiNumber(float $value): string
    {
        // แสดงเลขอารบิก ไม่มีทศนิยม
        return number_format($value, 0);
    }
}
