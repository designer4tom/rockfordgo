<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerStatusLog extends Model
{
    protected $fillable = [
        'user_id',
        'changed_by',
        'old_status',
        'new_status',
        'reason',
    ];

    protected $casts = [
        'old_status' => 'boolean',
        'new_status' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'changed_by');
    }
}
