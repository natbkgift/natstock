<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use App\Support\Settings\SettingManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AlertSnapshotService
{
    public function __construct(private readonly SettingManager $settings)
    {
    }

    /**
     * @return array{
     *     low_stock: array{enabled: bool, count: int, items: array<int, array<string, mixed>>, payload_hash: string|null},
     *     expiring: array{enabled: bool, days: int, count: int, items: array<int, array<string, mixed>>, payload_hash: string|null}
     * }
     */
    public function buildSnapshot(): array
    {
        $lowStockEnabled = $this->settings->shouldNotifyLowStock();
        $expiringEnabled = $this->settings->isExpiringAlertEnabled();

        $lowStock = [
            'enabled' => $lowStockEnabled,
            'count' => 0,
            'items' => [],
            'payload_hash' => null,
        ];

        if ($lowStockEnabled) {
            $query = $this->lowStockQuery();
            $ids = $this->cloneQueryForIds($query)->pluck('id')->all();
            $count = count($ids);
            $items = $query->limit(10)->get()->map(function (Product $product) {
                $qtyTotal = $product->qty_total ?? $product->qtyCurrent();

                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'qty_total' => (int) $qtyTotal,
                    'reorder_point' => (int) $product->reorder_point,
                ];
            })->all();

            $lowStock['count'] = $count;
            $lowStock['items'] = $items;
            $lowStock['payload_hash'] = $count > 0
                ? $this->buildPayloadHash('low_stock', $ids)
                : null;
        }

        $days = $this->settings->getExpiringLeadDays();
        $expiring = [
            'enabled' => $expiringEnabled,
            'days' => $days,
            'count' => 0,
            'items' => [],
            'payload_hash' => null,
        ];

        if ($expiringEnabled) {
            $expiringQuery = $this->expiringBatchQuery($days);
            $batchIds = $this->cloneQueryForIds($expiringQuery)->pluck('id')->all();
            $count = count($batchIds);
            $items = $expiringQuery->limit(10)->get()->map(function (ProductBatch $batch) {
                return [
                    'id' => $batch->id,
                    'sku' => $batch->product?->sku ?? '-',
                    'name' => $batch->product?->name ?? '-',
                    'sub_sku' => $batch->lot_no,
                    'lot_no' => $batch->lot_no,
                    'expire_date' => optional($batch->expire_date)->format('Y-m-d'),
                    'qty' => (int) $batch->qty,
                ];
            })->all();

            $expiring['count'] = $count;
            $expiring['items'] = $items;
            $expiring['payload_hash'] = $count > 0
                ? $this->buildPayloadHash('expiring', $batchIds, ['days' => $days])
                : null;
        }

        return [
            'low_stock' => $lowStock,
            'expiring' => $expiring,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function resolveRecipients(): Collection
    {
        return User::query()
            ->whereIn('role', ['admin', 'staff'])
            ->get();
    }

    public function buildPayloadHash(string $type, array $ids, array $context = []): string
    {
        sort($ids);

        return sha1(json_encode([
            'type' => $type,
            'ids' => $ids,
            'context' => $context,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return Builder
     */
    private function lowStockQuery(): Builder
    {
        return Product::query()
            ->isLowStockWithAggregatedQty()
            ->orderBy('qty_total')
            ->orderBy('products.name');
    }

    /**
     * @return Builder
     */
    private function expiringBatchQuery(int $days): Builder
    {
        return ProductBatch::query()
            ->with(['product:id,sku,name,category_id'])
            ->whereHas('product', fn (Builder $query) => $query->where('is_active', true))
            ->active()
            ->expiringIn($days)
            ->orderBy('expire_date')
            ->orderBy('lot_no');
    }

    private function cloneQueryForIds(Builder $builder): Builder
    {
        $clone = clone $builder;

        $clone->orders = [];
        $clone->reorders = [];

        $table = $builder->getModel()->getTable();

        return $clone->select("{$table}.id");
    }
}
