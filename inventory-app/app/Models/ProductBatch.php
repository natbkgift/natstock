<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sub_sku',
        'expire_date',
        'qty',
        'note',
        'is_active',
    ];

    protected $casts = [
        'expire_date' => 'date',
        'qty' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeExpiringIn(Builder $query, int $days): Builder
    {
        $today = now()->startOfDay();
        $endDate = $today->copy()->addDays($days);

        return $query
            ->whereNotNull('expire_date')
            ->whereBetween('expire_date', [$today->toDateString(), $endDate->toDateString()]);
    }
}
