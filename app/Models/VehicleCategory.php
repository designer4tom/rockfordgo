<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleCategory extends Model
{
    protected $fillable = [
        'service_id',
        'name',
        'icon',
        'base_fare',
        'per_km_rate',
        'per_minute_rate',
        'minimum_fare',
        'capacity',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'base_fare' => 'decimal:2',
        'per_km_rate' => 'decimal:2',
        'per_minute_rate' => 'decimal:2',
        'minimum_fare' => 'decimal:2',
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(DriverVehicle::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
