<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Services\AuditLogger;
use App\Services\StockMovementService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovementController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StockMovementService $stockMovementService
    )
    {
    }

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
            ->when(in_array($type, ['in', 'out', 'adjust'], true), fn ($query) => $query->where('type', $type))
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
                'type' => in_array($type, ['in', 'out', 'adjust'], true) ? $type : null,
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

    public function storeIn(Request $request): RedirectResponse
    {
        $this->authorize('create', StockMovement::class);

        $validated = $this->validateInboundOutbound($request);

        $product = Product::query()->findOrFail($validated['product_id']);
        $subSku = $this->normalizeIncomingSubSku($validated['sub_sku'] ?? null);
        $expireDate = $validated['expire_date'] !== null
            ? Carbon::createFromFormat('Y-m-d', $validated['expire_date'])->startOfDay()
            : null;

        $result = $this->stockMovementService->receive(
            $product,
            (int) $validated['qty'],
            $subSku,
            $expireDate,
            $validated['note'] ?? null
        );

        $this->auditLogger->log(
            'stock.in',
            'รับสินค้าเข้าคลัง',
            [
                'product_sku' => $result['product']->sku,
                'product_name' => $result['product']->name,
                'qty' => $validated['qty'],
                'batch_sub_sku' => $result['batch']->sub_sku,
                'batch_before_qty' => $result['batch_before'],
                'batch_after_qty' => $result['batch_after'],
                'before_qty' => $result['product_before'],
                'after_qty' => $result['product_after'],
            ],
            $result['movement'],
            $request->user(),
        );

        return redirect()
            ->route('admin.movements.index')
            ->with('status', 'ทำรายการสำเร็จ');
    }

    public function storeOut(Request $request): RedirectResponse
    {
        $this->authorize('create', StockMovement::class);

        $validated = $this->validateInboundOutbound($request);

        $product = Product::query()->findOrFail($validated['product_id']);
        $subSku = $this->normalizeIncomingSubSku($validated['sub_sku'] ?? null);

        $result = $this->stockMovementService->issue(
            $product,
            (int) $validated['qty'],
            $subSku,
            $validated['note'] ?? null
        );

        $this->auditLogger->log(
            'stock.out',
            'เบิกสินค้าออกจากคลัง',
            [
                'product_sku' => $result['product']->sku,
                'product_name' => $result['product']->name,
                'qty' => $validated['qty'],
                'batch_sub_sku' => $result['batch']->sub_sku,
                'batch_before_qty' => $result['batch_before'],
                'batch_after_qty' => $result['batch_after'],
                'before_qty' => $result['product_before'],
                'after_qty' => $result['product_after'],
            ],
            $result['movement'],
            $request->user(),
        );

        return redirect()
            ->route('admin.movements.index')
            ->with('status', 'ทำรายการสำเร็จ');
    }

    public function storeAdjust(Request $request): RedirectResponse
    {
        $this->authorize('create', StockMovement::class);

        $validated = $this->validateAdjust($request);

        $product = Product::query()->findOrFail($validated['product_id']);
        $subSku = $this->normalizeIncomingSubSku($validated['sub_sku']);

        $result = $this->stockMovementService->adjust(
            $product,
            (int) $validated['target_qty'],
            $subSku ?? '',
            $validated['note'] ?? null
        );

        $delta = $result['batch_after'] - $result['batch_before'];
        $deltaText = $delta > 0 ? "+{$delta}" : (string) $delta;

        $this->auditLogger->log(
            'stock.adjust',
            'ปรับปรุงยอดสต็อก',
            [
                'product_sku' => $result['product']->sku,
                'product_name' => $result['product']->name,
                'delta' => $delta,
                'batch_sub_sku' => $result['batch']->sub_sku,
                'batch_before_qty' => $result['batch_before'],
                'batch_after_qty' => $result['batch_after'],
                'before_qty' => $result['product_before'],
                'after_qty' => $result['product_after'],
            ],
            $result['movement'],
            $request->user(),
        );

        return redirect()
            ->route('admin.movements.index')
            ->with('status', "ปรับยอดสต็อกเรียบร้อย (Δ{$deltaText})");
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

    private function validateInboundOutbound(Request $request): array
    {
        return $request->validate(
            [
                'form_type' => ['required', 'in:in,out'],
                'product_id' => ['required', 'exists:products,id'],
                'qty' => ['required', 'integer', 'min:1'],
                'sub_sku' => ['nullable', 'string', 'max:64'],
                'expire_date' => ['nullable', 'date_format:Y-m-d'],
                'note' => ['nullable', 'string'],
            ],
            [
                'product_id.required' => 'กรุณาเลือกสินค้า',
                'product_id.exists' => 'สินค้าไม่ถูกต้อง',
                'qty.required' => 'กรุณาระบุจำนวน',
                'qty.integer' => 'จำนวนต้องเป็นตัวเลขจำนวนเต็ม',
                'qty.min' => 'จำนวนต้องมากกว่าศูนย์',
                'sub_sku.string' => 'รหัสล็อตต้องเป็นข้อความ',
                'sub_sku.max' => 'รหัสล็อตต้องไม่เกิน 64 ตัวอักษร',
                'expire_date.date_format' => 'รูปแบบวันหมดอายุไม่ถูกต้อง (ใช้รูปแบบ YYYY-MM-DD)',
                'note.string' => 'หมายเหตุต้องเป็นข้อความ',
            ]
        );
    }

    private function validateAdjust(Request $request): array
    {
        return $request->validate(
            [
                'form_type' => ['required', 'in:adjust'],
                'product_id' => ['required', 'exists:products,id'],
                'target_qty' => ['required', 'integer', 'min:0'],
                'sub_sku' => ['required', 'string', 'max:64'],
                'note' => ['nullable', 'string'],
            ],
            [
                'product_id.required' => 'กรุณาเลือกสินค้า',
                'product_id.exists' => 'สินค้าไม่ถูกต้อง',
                'target_qty.required' => 'กรุณาระบุจำนวนที่ควรเป็น',
                'target_qty.integer' => 'จำนวนต้องเป็นตัวเลขจำนวนเต็ม',
                'target_qty.min' => 'จำนวนต้องมากกว่าหรือเท่ากับศูนย์',
                'sub_sku.required' => 'กรุณาเลือกรหัสล็อต',
                'sub_sku.string' => 'รหัสล็อตต้องเป็นข้อความ',
                'sub_sku.max' => 'รหัสล็อตต้องไม่เกิน 64 ตัวอักษร',
                'note.string' => 'หมายเหตุต้องเป็นข้อความ',
            ]
        );
    }

    private function normalizeIncomingSubSku(?string $subSku): ?string
    {
        if ($subSku === null) {
            return null;
        }

        $subSku = trim($subSku);

        if ($subSku === '' || $subSku === '__UNSPECIFIED__') {
            return null;
        }

        return mb_substr($subSku, 0, 64);
    }

    private function resolvePrefillValues(Request $request): array
    {
        $allowedTabs = ['in', 'out', 'adjust'];
        $requestedTab = $request->string('form_type')->toString();
        $activeTab = old('form_type', in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'in');

        $resolver = function (string $tab, string $key, $default = null) use ($request, $activeTab) {
            if (old('form_type') === $tab && old($key) !== null) {
                return old($key);
            }

            if ($activeTab === $tab) {
                return $request->input($key, $default);
            }

            return $default;
        };

        $inProduct = $resolver('in', 'product_id');
        $outProduct = $resolver('out', 'product_id');
        $adjustProduct = $resolver('adjust', 'product_id');

        $productIds = array_values(array_unique(array_filter([
            $inProduct,
            $outProduct,
            $adjustProduct,
        ], fn ($value) => (int) $value > 0)));

        return [
            'active_tab' => $activeTab,
            'in' => [
                'product_id' => $inProduct,
                'qty' => $resolver('in', 'qty'),
                'sub_sku' => $resolver('in', 'sub_sku'),
                'expire_date' => $resolver('in', 'expire_date'),
                'note' => $resolver('in', 'note'),
            ],
            'out' => [
                'product_id' => $outProduct,
                'qty' => $resolver('out', 'qty'),
                'sub_sku' => $resolver('out', 'sub_sku'),
                'note' => $resolver('out', 'note'),
            ],
            'adjust' => [
                'product_id' => $adjustProduct,
                'target_qty' => $resolver('adjust', 'target_qty'),
                'sub_sku' => $resolver('adjust', 'sub_sku'),
                'note' => $resolver('adjust', 'note'),
            ],
            'product_ids' => $productIds,
        ];
    }

    /**
     * @param array<int, array{id:int, sku:string, name:string, qty:int, reorder_point:int, batches: \Illuminate\Support\Collection}> $productOptions
     * @return array<int, array<int, array{sub_sku: string, label: string, expire_date_th: ?string, qty: int}>>
     */
    private function buildBatchOptions(array $productOptions): array
    {
        $options = [];

        foreach ($productOptions as $product) {
            $options[$product['id']] = $product['batches']
                ->map(function (ProductBatch $batch) {
                    return [
                        'sub_sku' => $batch->sub_sku,
                        'label' => $this->formatBatchLabel($batch),
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
            ->with(['batches' => fn ($query) => $query->orderBy('sub_sku')])
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

    private function formatBatchLabel(ProductBatch $batch): string
    {
        $parts = [$batch->sub_sku];

        if ($batch->expire_date !== null) {
            $parts[] = $batch->expire_date->locale('th')->translatedFormat('d M Y');
        }

        return implode(' | ', $parts);
    }

    private function formatExpireDate(ProductBatch $batch): ?string
    {
        if ($batch->expire_date === null) {
            return null;
        }

        return $batch->expire_date->locale('th')->translatedFormat('d M Y');
    }
}
