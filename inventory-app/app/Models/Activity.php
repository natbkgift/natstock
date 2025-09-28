<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'description',
        'actor_id',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'happened_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'happened_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
