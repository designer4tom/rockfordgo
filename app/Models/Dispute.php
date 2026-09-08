<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    protected $fillable = [
        'order_id',
        'raised_by',
        'raised_by_id',
        'category',
        'description',
        'status',
        'admin_note',
        'resolved_by',
        'resolved_at',
        'refund_issued',
        'refund_amount',
    ];

    protected $casts = [
        'refund_issued' => 'boolean',
        'refund_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }
}
