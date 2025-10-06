<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'product_id',
        'type',
        'qty',
        'note',
        'actor_id',
        'happened_at',
    ];

    protected $casts = [
        'qty' => 'integer',
        'happened_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function batch(): BelongsTo
    {
        // ผูก movement กับ lot เฉพาะที่เลือกไว้ (ถ้าไม่มีให้เป็น null ได้)
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    public function getFormattedQtyAttribute(): string
    {
        $qty = number_format($this->qty);

        switch ($this->type) {
            case 'in':
                return '+' . $qty;
            case 'out':
                return '-' . $qty;
            case 'adjust':
                return 'Δ' . $qty;
            default:
                return $qty;
        }
    }
}
