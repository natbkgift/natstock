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
        $value = (float) $this->cost_price * (int) $this->qty;

        return number_format($value, 2, '.', '');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->qty <= $this->reorder_point;
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
}
