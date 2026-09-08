<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverShiftLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'driver_id',
        'went_online_at',
        'went_offline_at',
        'total_minutes',
    ];

    protected $casts = [
        'went_online_at' => 'datetime',
        'went_offline_at' => 'datetime',
        'total_minutes' => 'integer',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
