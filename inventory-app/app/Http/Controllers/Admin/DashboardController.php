<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('access-viewer');

        $expiringDays = (int) $request->input('expiring_days', 30);
        if (! in_array($expiringDays, [30, 60, 90], true)) {
            $expiringDays = 30;
        }

        $expiringCounts = [
            30 => Product::query()->expiringIn(30)->count(),
            60 => Product::query()->expiringIn(60)->count(),
            90 => Product::query()->expiringIn(90)->count(),
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
        $formatted = number_format($value, 2);

        $thaiDigits = [
            '0' => '๐',
            '1' => '๑',
            '2' => '๒',
            '3' => '๓',
            '4' => '๔',
            '5' => '๕',
            '6' => '๖',
            '7' => '๗',
            '8' => '๘',
            '9' => '๙',
            ',' => ',',
            '.' => '.',
        ];

        return Str::of($formatted)->replace(array_keys($thaiDigits), array_values($thaiDigits));
    }
}
