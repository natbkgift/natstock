<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
