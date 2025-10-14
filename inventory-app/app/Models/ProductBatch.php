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
        'lot_no',
        'expire_date',
        'qty',
        'received_at',
        'is_active',
        'note',
    ];

    protected $casts = [
        'expire_date' => 'date',
        'qty' => 'integer',
        'is_active' => 'boolean',
        'received_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    public function getSubSkuAttribute(): ?string
    {
        return $this->attributes['lot_no'] ?? null;
    }

    public function setSubSkuAttribute(?string $value): void
    {
        $this->attributes['lot_no'] = $value;
    }
}
