<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\AlertSnapshotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private readonly AlertSnapshotService $alerts)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('access-viewer');

        $snapshot = $this->alerts->buildSnapshot();

        $lowStockSnapshot = $snapshot['low_stock'];
        $expiringSnapshot = $snapshot['expiring'];

        $lowStockCount = $lowStockSnapshot['count'];
        $expiringCount = $expiringSnapshot['count'];

        $recentMovements = StockMovement::query()
            ->with(['product', 'actor', 'batch'])
            ->orderByDesc('happened_at')
            ->limit(10)
            ->get();

        $productSummary = $this->loadProductSummary();

        return view('admin.dashboard', [
            'expiringDays' => $expiringSnapshot['days'],
            'expiringCount' => $expiringCount,
            'lowStockCount' => $lowStockCount,
            'recentMovements' => $recentMovements,
            'productSummary' => $productSummary,
            'alerts' => [
                'low_stock' => [
                    'count' => $lowStockSnapshot['count'],
                    'items' => array_slice($lowStockSnapshot['items'], 0, 10),
                ],
                'expiring' => [
                    'count' => $expiringSnapshot['count'],
                    'items' => array_slice($expiringSnapshot['items'], 0, 10),
                    'days' => $expiringSnapshot['days'],
                ],
            ],
        ]);
    }

    private function loadProductSummary(): Collection
    {
        $movementSub = DB::table('stock_movements')
            ->select('product_id', DB::raw('MAX(happened_at) as last_moved_at'))
            ->groupBy('product_id');

        return Product::query()
            ->select('products.id', 'products.sku', 'products.name', 'products.qty')
            ->selectRaw('COALESCE(movement_stats.last_moved_at, products.updated_at, products.created_at) as last_moved_at')
            ->leftJoinSub($movementSub, 'movement_stats', 'movement_stats.product_id', '=', 'products.id')
            ->withSum(['batches as active_qty_total' => fn ($query) => $query->where('is_active', true)], 'qty')
            ->withCount(['batches as active_batches_count' => fn ($query) => $query->where('is_active', true)])
            ->orderByDesc('last_moved_at')
            ->orderBy('products.name')
            ->limit(10)
            ->get()
            ->map(function (Product $product) {
                $totalQty = $product->active_qty_total ?? $product->qty;

                return [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'qty' => (int) $totalQty,
                    'active_batches' => (int) ($product->active_batches_count ?? 0),
                ];
            });
    }
}
