<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'order_id',
        'type',
        'category',
        'amount',
        'balance_before',
        'balance_after',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Resolve the owning model (user or driver) on demand.
    public function owner()
    {
        return $this->owner_type === 'driver'
            ? Driver::find($this->owner_id)
            : User::find($this->owner_id);
    }
}
