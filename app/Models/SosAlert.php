<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SosAlert extends Model
{
    protected $fillable = [
        'order_id',
        'triggered_by',
        'triggered_by_id',
        'lat',
        'lng',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_at',
        'note',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'acknowledged_by');
    }
}
