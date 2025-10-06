<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAlertState extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'alert_type',
        'payload_hash',
        'snooze_until',
        'read_at',
    ];

    protected $casts = [
        'snooze_until' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSnoozed(): bool
    {
        if ($this->snooze_until === null) {
            return false;
        }

        return Carbon::now()->lessThan($this->snooze_until);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
