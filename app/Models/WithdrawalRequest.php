<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    protected $fillable = [
        'driver_id',
        'user_id',
        'amount',
        'method',
        'account_details',
        'status',
        'processed_by',
        'processed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'account_details' => 'array',
        'processed_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Whoever owns this request (driver or customer) — for unified admin display.
    public function ownerName(): string
    {
        return $this->user_id ? ($this->user->name ?? '—') : ($this->driver->name ?? '—');
    }

    public function ownerType(): string
    {
        return $this->user_id ? 'user' : 'driver';
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }
}
