<?php

namespace App\Models;

use App\Models\ProductBatch;
use App\Services\SkuService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'note',
        'category_id',
        'cost_price',
        'sale_price',
        'expire_date',
        'reorder_point',
        'qty',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'expire_date' => 'date',
        'reorder_point' => 'integer',
        'qty' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (blank($product->sku)) {
                $product->sku = app(SkuService::class)->next();
            }
        });

        static::created(function (Product $product): void {
            DB::transaction(function () use ($product): void {
                $counter = DB::table('product_lot_counters')
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if ($counter === null) {
                    DB::table('product_lot_counters')->insert([
                        'product_id' => $product->id,
                        'next_no' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $hasInitialBatch = $product->batches()
                    ->where('lot_no', 'LOT-01')
                    ->exists();

                if (!$hasInitialBatch) {
                    $product->batches()->create([
                        'lot_no' => 'LOT-01',
                        'qty' => 0,
                        'is_active' => true,
                    ]);
                }

                DB::table('product_lot_counters')
                    ->where('product_id', $product->id)
                    ->update([
                        'next_no' => 2,
                        'updated_at' => now(),
                    ]);
            });
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class, 'product_id');
    }

    public function qtyCurrent(): int
    {
        // รวมยอดคงเหลือจาก batch ที่เปิดใช้งาน หากยังไม่มี batch ให้ fallback ไปใช้ qty เดิม
        if ($this->relationLoaded('batches')) {
            if ($this->batches->isEmpty()) {
                return (int) $this->qty;
            }

            return (int) $this->batches->where('is_active', true)->sum('qty');
        }

        $activeQty = (int) $this->batches()->where('is_active', true)->sum('qty');

        if ($activeQty > 0) {
            return $activeQty;
        }

        return $this->batches()->exists()
            ? 0
            : (int) $this->qty;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithBatchQuantityTotals(Builder $query): Builder
    {
        $batchTotals = ProductBatch::query()
            ->select('product_id')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN qty ELSE 0 END) as active_qty')
            ->selectRaw('COUNT(*) as total_batches')
            ->groupBy('product_id');

        return $query
            ->select('products.*')
            ->selectRaw(self::batchQuantityTotalExpression() . ' as qty_total')
            ->leftJoinSub($batchTotals, 'batch_totals', 'batch_totals.product_id', '=', 'products.id');
    }

    public function scopeIsLowStockWithAggregatedQty(Builder $query): Builder
    {
        return $query
            ->withBatchQuantityTotals()
            ->where('products.is_active', true)
            ->where('products.reorder_point', '>', 0)
            ->whereRaw(self::batchQuantityTotalExpression() . ' <= products.reorder_point');
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('qty', '<=', 'reorder_point');
    }

    public function scopeExpiringIn(Builder $query, int $days): Builder
    {
        $today = Carbon::today();
        $endDate = $today->copy()->addDays($days);

        return $query
            ->whereNotNull('expire_date')
            ->whereBetween('expire_date', [$today->toDateString(), $endDate->toDateString()]);
    }

    public function getStockValueAttribute(): string
    {
        if (!config('inventory.enable_price')) {
            return '0.00';
        }

        $value = (float) $this->cost_price * (int) $this->qty;

        return number_format($value, 2, '.', '');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->isLowStock();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if ($this->expire_date === null) {
            return false;
        }

        $today = Carbon::today();
        $limit = $today->copy()->addDays(30);

        return $this->expire_date->between($today, $limit);
    }

    public function isLowStock(): bool
    {
        $reorderPoint = (int) $this->reorder_point;

        if ($reorderPoint <= 0) {
            return false;
        }

        return $this->qtyCurrent() <= $reorderPoint;
    }

    public static function batchQuantityTotalExpression(): string
    {
        return 'CASE WHEN COALESCE(batch_totals.total_batches, 0) > 0 '
            . 'THEN COALESCE(batch_totals.active_qty, 0) ELSE products.qty END';
    }
}
