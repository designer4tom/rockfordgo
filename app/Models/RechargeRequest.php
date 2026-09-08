<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RechargeRequest extends Model
{
    protected $fillable = [
        'driver_id', 'amount', 'payment_method', 'payment_url',
        'transaction_id', 'status', 'due_cleared', 'balance_added', 'admin_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_cleared' => 'decimal:2',
        'balance_added' => 'decimal:2',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
