<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    protected $fillable = [
        'name',
        'polygon',
        'shape',
        'center_lat',
        'center_lng',
        'radius_km',
        'is_active',
    ];

    protected $casts = [
        'polygon' => 'array',
        'center_lat' => 'decimal:8',
        'center_lng' => 'decimal:8',
        'radius_km' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'zone_services')
            ->withPivot('operating_hours_start', 'operating_hours_end')
            ->withTimestamps();
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function surgeRules(): HasMany
    {
        return $this->hasMany(SurgePricingRule::class);
    }
}
