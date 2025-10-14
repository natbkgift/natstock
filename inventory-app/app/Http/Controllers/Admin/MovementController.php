<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockMovement::class);

        $search = trim((string) $request->string('search')->toString());
        $type = $request->string('type')->toString();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $fromDate = $this->parseDate($dateFrom)?->startOfDay();
        $toDate = $this->parseDate($dateTo)?->endOfDay();

        $movementsQuery = StockMovement::query()
            ->with(['product', 'actor', 'batch'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('product', function ($subQuery) use ($search) {
                    $subQuery->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when(in_array($type, ['receive', 'issue', 'adjust'], true), fn ($query) => $query->where('type', $type))
            ->when($fromDate, fn ($query) => $query->where('happened_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('happened_at', '<=', $toDate))
            ->orderByDesc('happened_at');

        /** @var LengthAwarePaginator $movements */
        $movements = $movementsQuery->paginate(20)->appends($request->query());

        $prefill = $this->resolvePrefillValues($request);

        $productOptions = $this->loadProductOptions($prefill['product_ids']);

        $initialBatchOptions = $this->buildBatchOptions($productOptions);

        return view('admin.movements.index', [
            'movements' => $movements,
            'productOptions' => $productOptions,
            'initialBatchOptions' => $initialBatchOptions,
            'prefill' => $prefill,
            'filters' => [
                'search' => $search,
                'type' => in_array($type, ['receive', 'issue', 'adjust'], true) ? $type : null,
                'date_from' => $fromDate?->toDateString(),
                'date_to' => $toDate?->toDateString(),
            ],
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $term = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->select(['id', 'sku', 'name'])
            ->where('is_active', true)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('sku', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%");
                });
            })
            ->orderBy('sku')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $products->map(function (Product $product) {
                $label = sprintf('[%s] %s', $product->sku, $product->name);

                return [
                    'id' => $product->id,
                    'text' => $label,
                    'qty' => number_format($product->qtyCurrent()),
                ];
            })->values(),
        ]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolvePrefillValues(Request $request): array
    {
        $allowedTabs = ['receive', 'issue', 'adjust'];
        $requestedTab = $request->string('form_type')->toString();
        $activeTab = old('form_type', in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'receive');

        $resolver = function (string $tab, string $key, $default = null) use ($request, $activeTab) {
            if (old('form_type') === $tab && old($key) !== null) {
                return old($key);
            }

            if ($activeTab === $tab) {
                return $request->input($key, $default);
            }

            return $default;
        };

        $receiveProduct = $resolver('receive', 'product_id');
        $issueProduct = $resolver('issue', 'product_id');
        $adjustProduct = $resolver('adjust', 'product_id');

        $productIds = array_values(array_unique(array_filter([
            $receiveProduct,
            $issueProduct,
            $adjustProduct,
        ], fn ($value) => (int) $value > 0)));

        return [
            'active_tab' => $activeTab,
            'receive' => [
                'product_id' => $receiveProduct,
                'qty' => $resolver('receive', 'qty'),
                'expire_date' => $resolver('receive', 'expire_date'),
                'note' => $resolver('receive', 'note'),
            ],
            'issue' => [
                'product_id' => $issueProduct,
                'qty' => $resolver('issue', 'qty'),
                'lot_no' => $resolver('issue', 'lot_no'),
                'note' => $resolver('issue', 'note'),
            ],
            'adjust' => [
                'product_id' => $adjustProduct,
                'new_qty' => $resolver('adjust', 'new_qty'),
                'lot_no' => $resolver('adjust', 'lot_no'),
                'note' => $resolver('adjust', 'note'),
            ],
            'product_ids' => $productIds,
        ];
    }

    /**
     * @param array<int, array{id:int, sku:string, name:string, qty:int, reorder_point:int, batches: \Illuminate\Support\Collection}> $productOptions
     * @return array<int, array<int, array{lot_no: string, expire_date_th: ?string, qty: int}>>
     */
    private function buildBatchOptions(array $productOptions): array
    {
        $options = [];

        foreach ($productOptions as $product) {
            $options[$product['id']] = $product['batches']
                ->map(function (ProductBatch $batch) {
                    return [
                        'lot_no' => $batch->lot_no,
                        'expire_date_th' => $this->formatExpireDate($batch),
                        'qty' => (int) $batch->qty,
                    ];
                })
                ->values()
                ->all();
        }

        return $options;
    }

    /**
     * @param array<int> $productIds
     * @return array<int, array{id:int, sku:string, name:string, qty:int, reorder_point:int, batches: \Illuminate\Support\Collection}>
     */
    private function loadProductOptions(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        return Product::query()
            ->whereIn('id', $productIds)
            ->with(['batches' => fn ($query) => $query->where('is_active', true)->orderBy('lot_no')])
            ->orderBy('sku')
            ->get()
            ->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'qty' => $product->qtyCurrent(),
                    'reorder_point' => $product->reorder_point,
                    'batches' => $product->batches,
                ];
            })
            ->keyBy('id')
            ->all();
    }

    private function formatExpireDate(ProductBatch $batch): ?string
    {
        if ($batch->expire_date === null) {
            return null;
        }

        return $batch->expire_date->locale('th')->translatedFormat('d M Y');
    }
}
