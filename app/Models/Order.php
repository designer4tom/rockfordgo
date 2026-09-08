<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    // Statuses that represent an order still in progress (between assignment and completion).
    public const ONGOING_STATUSES = [
        'accepted', 'go_to_pickup', 'confirm_arrival', 'picked_up', 'start_ride', 'dropped_off',
    ];

    // Statuses where admin intervention (cancel / reassign) is still meaningful.
    public const ACTIVE_STATUSES = [
        'scheduled', 'pending', 'accepted', 'go_to_pickup', 'confirm_arrival',
        'picked_up', 'start_ride', 'dropped_off',
    ];
    protected $fillable = [
        'order_number', 'user_id', 'driver_id', 'service_id', 'vehicle_category_id',
        'type', 'status',
        'pickup_address', 'pickup_lat', 'pickup_lng',
        'drop_address', 'drop_lat', 'drop_lng', 'stops',
        'otp', 'otp_verified_at',
        'sender_name', 'sender_phone', 'receiver_name', 'receiver_phone',
        'parcel_type', 'parcel_weight', 'parcel_size', 'parcel_photo', 'parcel_note',
        'is_cod', 'cod_amount', 'payment_timing',
        'proof_type', 'proof_data', 'proof_collected_at',
        'distance_km', 'duration_minutes', 'base_fare', 'distance_charge', 'time_charge',
        'surge_multiplier', 'surge_amount', 'delivery_charge',
        'coupon_id', 'coupon_discount', 'total_amount', 'admin_commission', 'driver_earning', 'tip_amount',
        'payment_method', 'payment_status', 'payment_intent_id',
        'scheduled_at', 'driver_assigned_at',
        'cancelled_by', 'cancellation_reason', 'cancellation_fee',
        'accepted_at', 'arrived_at', 'picked_up_at', 'started_at', 'dropped_at', 'completed_at', 'cancelled_at',
    ];

    protected $casts = [
        'stops' => 'array',
        'is_cod' => 'boolean',
        'pickup_lat' => 'decimal:8',
        'pickup_lng' => 'decimal:8',
        'drop_lat' => 'decimal:8',
        'drop_lng' => 'decimal:8',
        'cod_amount' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'duration_minutes' => 'integer',
        'base_fare' => 'decimal:2',
        'distance_charge' => 'decimal:2',
        'time_charge' => 'decimal:2',
        'surge_multiplier' => 'decimal:2',
        'surge_amount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'admin_commission' => 'decimal:2',
        'driver_earning' => 'decimal:2',
        'tip_amount' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
        'parcel_weight' => 'decimal:2',
        'otp_verified_at' => 'datetime',
        'proof_collected_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'driver_assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'arrived_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'started_at' => 'datetime',
        'dropped_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function vehicleCategory(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(OrderLocation::class);
    }

    public function dispatchLogs(): HasMany
    {
        return $this->hasMany(OrderDispatchLog::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function couponUsage(): HasOne
    {
        return $this->hasOne(CouponUsage::class);
    }

    // ----- Scopes -----

    public function scopeOngoing(Builder $query): Builder
    {
        return $query->whereIn('status', self::ONGOING_STATUSES);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    // ----- Helpers -----

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isCancellable(): bool
    {
        return $this->isActive();
    }

    // Subtotal before coupon discount (fare components + tip).
    public function getSubtotalAttribute(): float
    {
        return (float) $this->base_fare
            + (float) $this->distance_charge
            + (float) $this->time_charge
            + (float) $this->surge_amount
            + (float) $this->delivery_charge
            + (float) $this->tip_amount;
    }
}
