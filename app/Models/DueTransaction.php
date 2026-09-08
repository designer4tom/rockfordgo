<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DueTransaction extends Model
{
    protected $fillable = [
        'driver_id',
        'order_id',
        'type',
        'amount',
        'due_before',
        'due_after',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_before' => 'decimal:2',
        'due_after' => 'decimal:2',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
