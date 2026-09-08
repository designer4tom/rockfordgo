<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParcelPricing extends Model
{
    // Explicit table name (plural of the model would be "parcel_pricings").
    protected $table = 'parcel_pricing';

    protected $fillable = [
        'name',
        'min_weight',
        'max_weight',
        'base_charge',
        'per_km_charge',
        'parcel_type',
        'is_active',
    ];

    protected $casts = [
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'base_charge' => 'decimal:2',
        'per_km_charge' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
