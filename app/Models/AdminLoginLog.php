<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLoginLog extends Model
{
    // Only created_at is tracked on this table.
    const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'ip_address',
        'user_agent',
        'status',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
