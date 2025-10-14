<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Services\Reports\ProductReportService;
use App\Support\CsvExporter;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ProductReportService $reports)
    {
    }

    public function index(): View
    {
        Gate::authorize('access-viewer');

        return view('admin.reports.index', [
            'pricingEnabled' => (bool) config('inventory.enable_price'),
        ]);
    }

    public function expiringBatches(Request $request)
    {
        Gate::authorize('access-viewer');

        $filters = $this->prepareExpiringBatchFilters($request);
        $query = $this->buildExpiringBatchesQuery($filters);

        if ($this->isCsvExport($request)) {
            $batches = $query->get();

            return $this->exportExpiringBatchesCsv($batches, $filters);
        }

        $batches = $query->paginate(25)->withQueryString();
        $categories = $this->loadCategories();

        return view('admin.reports.expiring_batches', [
            'batches' => $batches,
            'categories' => $categories,
            'filters' => $filters,
            'dayOptions' => [7, 30, 60, 90],
        ]);
    }

    public function lowStock(Request $request)
    {
        Gate::authorize('access-viewer');

        $filters = $this->prepareLowStockFilters($request);
        $query = $this->buildLowStockQuery($filters);

        if ($this->isCsvExport($request)) {
            $products = $query->get();

            return $this->exportLowStockCsv($products);
        }

        $products = $query->paginate(25)->withQueryString();
        $categories = $this->loadCategories();

        return view('admin.reports.low_stock', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    public function valuation(Request $request)
    {
        Gate::authorize('access-viewer');

        if (!config('inventory.enable_price')) {
            abort(404);
        }

        $filters = $this->prepareFilters($request, false);

        if ($this->isCsvExport($request)) {
            $products = $this->reports->valuation($filters, false);

            return $this->exportValuationCsv($products);
        }

        $products = $this->reports->valuation($filters);
        $categories = $this->loadCategories();
        $totalValue = $this->reports->valuationTotal($filters);

        return view('admin.reports.valuation', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
            'summary' => $this->buildSummary($filters, $products, $categories, [
                'total_value' => $totalValue,
            ]),
            'totalValue' => $totalValue,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepareFilters(Request $request, bool $includeDays): array
    {
        $filters = [
            'category_id' => $request->integer('category_id'),
            'status' => $request->string('status')->toString() ?: 'all',
            'search' => trim((string) $request->string('search')->toString()),
        ];

        if ($includeDays) {
            $days = $request->integer('days', 30);
            $filters['days'] = in_array($days, [30, 60, 90], true) ? $days : 30;
        }

        return $filters;
    }

    /**
     * @return array{days: int, category_id: int, search: string, active_only: bool}
     */
    private function prepareExpiringBatchFilters(Request $request): array
    {
        $allowed = [7, 30, 60, 90];
        $days = $request->integer('days', 30);
        if (! in_array($days, $allowed, true)) {
            $days = 30;
        }

        return [
            'days' => $days,
            'category_id' => $request->integer('category_id'),
            'search' => trim((string) $request->string('search')->toString()),
            'active_only' => $request->has('active') ? $request->boolean('active') : true,
        ];
    }

    /**
     * @return array{category_id: int, search: string}
     */
    private function prepareLowStockFilters(Request $request): array
    {
        return [
            'category_id' => $request->integer('category_id'),
            'search' => trim((string) $request->string('search')->toString()),
        ];
    }

    protected function isCsvExport(Request $request): bool
    {
        return strtolower($request->string('export')->toString()) === 'csv';
    }

    protected function loadCategories(): Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @param  LengthAwarePaginator|Collection<int, mixed>  $items
     * @return array<int, string>
     */
    protected function buildSummary(array $filters, LengthAwarePaginator|Collection $items, Collection $categories, array $extra = []): array
    {
        $total = $items instanceof LengthAwarePaginator ? $items->total() : $items->count();
        $summary = [sprintf('จำนวน %d รายการ', $total)];

        if (isset($extra['days'])) {
            $summary[] = sprintf('ภายใน %d วัน', (int) $extra['days']);
        }

        $summary[] = 'หมวดหมู่: ' . $this->categoryLabel((int) ($filters['category_id'] ?? 0), $categories);
        $summary[] = 'สถานะ: ' . $this->statusLabel($filters['status'] ?? 'all');

        if (($filters['search'] ?? '') !== '') {
            $summary[] = sprintf('คำค้น: "%s"', $filters['search']);
        }

        if (isset($extra['total_value'])) {
            $summary[] = 'มูลค่ารวม: ' . number_format((float) $extra['total_value'], 2, '.', ',');
        }

        return $summary;
    }

    protected function categoryLabel(int $categoryId, Collection $categories): string
    {
        if ($categoryId <= 0) {
            return 'ทั้งหมด';
        }

        $category = $categories->firstWhere('id', $categoryId);

        return $category?->name ?? 'ทั้งหมด';
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'ใช้งาน',
            'inactive' => 'ปิดใช้งาน',
            default => 'ทั้งหมด',
        };
    }

    private function buildExpiringBatchesQuery(array $filters): Builder
    {
        $query = ProductBatch::query()
            ->with(['product.category'])
            ->expiringIn($filters['days'])
            ->when($filters['active_only'], fn (Builder $builder) => $builder->active())
            ->whereHas('product', function (Builder $productQuery) use ($filters): void {
                $productQuery
                    ->when($filters['active_only'], fn (Builder $builder) => $builder->where('is_active', true))
                    ->when($filters['category_id'] > 0, fn (Builder $builder) => $builder->where('category_id', $filters['category_id']));
            });

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(function (Builder $subQuery) use ($like): void {
                $subQuery
                    ->where('lot_no', 'like', $like)
                    ->orWhereHas('product', function (Builder $productQuery) use ($like): void {
                        $productQuery
                            ->where('sku', 'like', $like)
                            ->orWhere('name', 'like', $like);
                    });
            });
        }

        return $query->orderBy('expire_date')->orderBy('lot_no');
    }

    private function buildLowStockQuery(array $filters): Builder
    {
        $query = Product::query()
            ->isLowStockWithAggregatedQty()
            ->with([
                'category:id,name',
                'batches' => function (HasMany $relation): void {
                    $relation->active()->orderBy('expire_date')->orderBy('lot_no')->take(3);
                },
            ])
            ->orderBy('qty_total')
            ->orderBy('products.name');

        if ($filters['category_id'] > 0) {
            $query->where('products.category_id', $filters['category_id']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(function (Builder $subQuery) use ($like): void {
                $subQuery
                    ->where('products.sku', 'like', $like)
                    ->orWhere('products.name', 'like', $like);
            });
        }

        return $query;
    }

    protected function exportExpiringBatchesCsv(Collection $batches, array $filters)
    {
        $days = (int) ($filters['days'] ?? 30);
        $filename = sprintf('expiring_batches_%dd_%s.csv', $days, Carbon::today()->format('Ymd'));

        $rows = $batches->map(function (ProductBatch $batch): array {
            $product = $batch->product;
            $categoryName = $product?->category?->name ?? '-';

            return [
                $product?->sku ?? '-',
                $product?->name ?? '-',
                $batch->lot_no ?? '-',
                optional($batch->expire_date)->format('Y-m-d'),
                (string) $batch->qty,
                $categoryName,
            ];
        });

        return CsvExporter::download(
            $filename,
            ['sku', 'name', 'lot_no', 'expire_date', 'qty', 'category'],
            $rows
        );
    }

    protected function exportLowStockCsv(Collection $products)
    {
        $filename = sprintf('low_stock_%s.csv', Carbon::today()->format('Ymd'));

        $rows = $products->map(function (Product $product): array {
            $categoryName = $product->category->name ?? '-';
            $qtyTotal = $product->qty_total ?? $product->qtyCurrent();

            return [
                $product->sku,
                $product->name,
                (string) $qtyTotal,
                (string) $product->reorder_point,
                $categoryName,
            ];
        });

        return CsvExporter::download(
            $filename,
            ['sku', 'name', 'qty_total', 'reorder_point', 'category'],
            $rows
        );
    }

    protected function exportValuationCsv(Collection $products)
    {
        $filename = sprintf('valuation_%s.csv', Carbon::today()->format('Ymd'));
        $total = $this->reports->calculateValuationTotal($products);

        $rows = $products->map(function ($product): array {
            $categoryName = $product->category->name ?? '-';
            $costPrice = number_format((float) $product->cost_price, 2, '.', '');
            $qty = (int) $product->qty;
            $totalValue = number_format($qty * (float) $product->cost_price, 2, '.', '');

            return [
                $product->sku,
                $product->name,
                $categoryName,
                $costPrice,
                (string) $qty,
                $totalValue,
            ];
        });

        $footer = ['รวมทั้งสิ้น', '', '', '', '', number_format($total, 2, '.', '')];

        return CsvExporter::download(
            $filename,
            ['รหัสสินค้า', 'ชื่อสินค้า', 'หมวดหมู่', 'ราคาทุนต่อหน่วย', 'คงเหลือ', 'มูลค่ารวม'],
            $rows,
            $footer
        );
    }
}
