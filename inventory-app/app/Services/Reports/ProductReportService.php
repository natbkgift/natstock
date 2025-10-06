<?php

namespace App\Services\Reports;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator|Collection<int, Product>
     */
    public function expiring(array $filters, bool $paginate = true): LengthAwarePaginator|Collection
    {
        $days = (int) ($filters['days'] ?? 30);
        if (!in_array($days, [30, 60, 90], true)) {
            $days = 30;
        }

        $query = $this->baseQuery($filters)
            ->select([
                'products.id',
                'products.sku',
                'products.name',
                'products.category_id',
                'products.expire_date',
                'products.qty',
                'products.reorder_point',
                'products.is_active',
            ])
            ->expiringIn($days)
            ->whereNotNull('expire_date')
            ->selectRaw('DATEDIFF(expire_date, ?) as days_remaining', [Carbon::today()->toDateString()])
            ->orderBy('expire_date');

        return $this->execute($query, $paginate);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator|Collection<int, Product>
     */
    public function lowStock(array $filters, bool $paginate = true): LengthAwarePaginator|Collection
    {
        $query = $this->baseQuery($filters)
            ->select([
                'products.id',
                'products.sku',
                'products.name',
                'products.category_id',
                'products.expire_date',
                'products.qty',
                'products.reorder_point',
                'products.is_active',
            ])
            ->where('reorder_point', '>', 0)
            ->lowStock()
            ->orderBy('qty');

        return $this->execute($query, $paginate);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator|Collection<int, Product>
     */
    public function valuation(array $filters, bool $paginate = true): LengthAwarePaginator|Collection
    {
        if (!config('inventory.enable_price')) {
            $query = $this->baseQuery($filters)
                ->select([
                    'products.id',
                    'products.sku',
                    'products.name',
                    'products.category_id',
                    'products.qty',
                    'products.is_active',
                ])
                ->selectRaw('0 as total_value')
                ->orderBy('sku');

            return $this->execute($query, $paginate);
        }

        $query = $this->baseQuery($filters)
            ->select([
                'products.id',
                'products.sku',
                'products.name',
                'products.category_id',
                'products.cost_price',
                'products.qty',
                'products.is_active',
            ])
            ->selectRaw('(qty * cost_price) as total_value')
            ->orderBy('sku');

        return $this->execute($query, $paginate);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function calculateValuationTotal(Collection $products): float
    {
        if (!config('inventory.enable_price')) {
            return 0.0;
        }

        return $products->sum(function (Product $product): float {
            $cost = (float) $product->cost_price;
            $qty = (int) $product->qty;

            return $cost * $qty;
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function valuationTotal(array $filters): float
    {
        if (!config('inventory.enable_price')) {
            return 0.0;
        }

        $total = $this->baseQuery($filters)
            ->selectRaw('COALESCE(SUM(qty * cost_price), 0) as aggregate_total')
            ->value('aggregate_total');

        return (float) $total;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function baseQuery(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $categoryId = (int) ($filters['category_id'] ?? 0);
        $status = (string) ($filters['status'] ?? 'all');

        return Product::query()
            ->with(['category:id,name'])
            ->when($categoryId > 0, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($status === 'active', fn (Builder $query) => $query->active())
            ->when($status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            });
    }

    /**
     * @return LengthAwarePaginator|Collection<int, Product>
     */
    protected function execute(Builder $query, bool $paginate): LengthAwarePaginator|Collection
    {
        return $paginate
            ? $query->paginate(25)->withQueryString()
            : $query->get();
    }
}
