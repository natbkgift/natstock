<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Reports\ProductReportService;
use App\Support\CsvExporter;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

        return view('admin.reports.index');
    }

    public function expiring(Request $request)
    {
        Gate::authorize('access-viewer');

        $filters = $this->prepareFilters($request, true);

        if ($this->isCsvExport($request)) {
            $products = $this->reports->expiring($filters, false);

            return $this->exportExpiringCsv($products, $filters);
        }

        $products = $this->reports->expiring($filters);
        $categories = $this->loadCategories();

        return view('admin.reports.expiring', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
            'summary' => $this->buildSummary($filters, $products, $categories, [
                'days' => $filters['days'],
            ]),
        ]);
    }

    public function lowStock(Request $request)
    {
        Gate::authorize('access-viewer');

        $filters = $this->prepareFilters($request, false);

        if ($this->isCsvExport($request)) {
            $products = $this->reports->lowStock($filters, false);

            return $this->exportLowStockCsv($products);
        }

        $products = $this->reports->lowStock($filters);
        $categories = $this->loadCategories();

        return view('admin.reports.low-stock', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
            'summary' => $this->buildSummary($filters, $products, $categories),
        ]);
    }

    public function valuation(Request $request)
    {
        Gate::authorize('access-viewer');

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

    protected function productStatus(bool $isActive): string
    {
        return $isActive ? 'ใช้งาน' : 'ปิดใช้งาน';
    }

    protected function exportExpiringCsv(Collection $products, array $filters)
    {
        $days = (int) ($filters['days'] ?? 30);
        $filename = sprintf('expiring_%dd_%s.csv', $days, Carbon::today()->format('Ymd'));

        $rows = $products->map(function ($product): array {
            $categoryName = $product->category->name ?? '-';
            $expireDate = $product->expire_date instanceof Carbon
                ? $product->expire_date->format('Y-m-d')
                : (string) $product->expire_date;

            return [
                $product->sku,
                $product->name,
                $categoryName,
                $expireDate,
                (string) $product->qty,
                (string) $product->reorder_point,
                $this->productStatus((bool) $product->is_active),
            ];
        });

        return CsvExporter::download(
            $filename,
            ['รหัสสินค้า', 'ชื่อสินค้า', 'หมวดหมู่', 'วันหมดอายุ', 'คงเหลือ', 'จุดสั่งซื้อซ้ำ', 'สถานะ'],
            $rows
        );
    }

    protected function exportLowStockCsv(Collection $products)
    {
        $filename = sprintf('low_stock_%s.csv', Carbon::today()->format('Ymd'));

        $rows = $products->map(function ($product): array {
            $categoryName = $product->category->name ?? '-';
            $expireDate = $product->expire_date instanceof Carbon
                ? $product->expire_date->format('Y-m-d')
                : (string) $product->expire_date;

            return [
                $product->sku,
                $product->name,
                $categoryName,
                (string) $product->qty,
                (string) $product->reorder_point,
                $expireDate,
                $this->productStatus((bool) $product->is_active),
            ];
        });

        return CsvExporter::download(
            $filename,
            ['รหัสสินค้า', 'ชื่อสินค้า', 'หมวดหมู่', 'คงเหลือ', 'จุดสั่งซื้อซ้ำ', 'วันหมดอายุ', 'สถานะ'],
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
