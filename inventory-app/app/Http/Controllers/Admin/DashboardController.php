<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\UserAlertState;
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
        $user = $request->user();

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

        $alertStates = $this->resolveAlertStates($user?->id, $snapshot);
        $shouldShowModal = $alertStates['low_stock']['show'] || $alertStates['expiring']['show'];

        return view('admin.dashboard', [
            'expiringDays' => $expiringSnapshot['days'],
            'expiringCount' => $expiringCount,
            'lowStockCount' => $lowStockCount,
            'recentMovements' => $recentMovements,
            'productSummary' => $productSummary,
            'alertStates' => $alertStates,
            'shouldShowAlerts' => $shouldShowModal,
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
