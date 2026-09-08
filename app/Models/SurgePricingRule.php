<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurgePricingRule extends Model
{
    protected $fillable = [
        'name',
        'zone_id',
        'vehicle_category_id',
        'day_of_week',
        'start_time',
        'end_time',
        'multiplier',
        'is_manual',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'array',
        'multiplier' => 'decimal:2',
        'is_manual' => 'boolean',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function vehicleCategory(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class);
    }
}
