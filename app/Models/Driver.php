<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'avatar',
        'wallet_balance',
        'due_amount',
        'can_accept_orders',
        'status',
        'is_online',
        'current_lat',
        'current_lng',
        'last_location_at',
        'average_rating',
        'total_trips',
        'acceptance_rate',
        'completion_rate',
        'cancellation_rate',
        'zone_id',
        'fcm_token',
        'withdrawal_method',
        'withdrawal_account',
        'rejection_reason',
        'is_active',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'deletion_requested_at',
    ];

    protected $casts = [
        'wallet_balance' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'can_accept_orders' => 'boolean',
        'is_online' => 'boolean',
        // Chat presence heartbeat — distinct from is_online (dispatch availability).
        'last_seen_at' => 'datetime',
        'current_lat' => 'decimal:8',
        'current_lng' => 'decimal:8',
        'last_location_at' => 'datetime',
        'average_rating' => 'decimal:2',
        'total_trips' => 'integer',
        'acceptance_rate' => 'decimal:2',
        'completion_rate' => 'decimal:2',
        'cancellation_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DriverDocument::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(DriverVehicle::class);
    }

    // FCM device tokens (a driver may have several devices).
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class, 'owner_id')->where('owner_type', 'driver');
    }

    // The driver's currently active vehicle (used by the API).
    public function activeVehicle(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DriverVehicle::class)->where('is_active', true);
    }

    public function shiftLogs(): HasMany
    {
        return $this->hasMany(DriverShiftLog::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function dueTransactions(): HasMany
    {
        return $this->hasMany(DueTransaction::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'owner_id')
            ->where('owner_type', 'driver');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(DriverStatusLog::class);
    }

    // ---------------------------------------------------------------------
    // Query scopes
    // ---------------------------------------------------------------------

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('is_online', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    // Drivers with an approved document expiring within the next $days days.
    public function scopeExpiringDocuments(Builder $query, int $days = 30): Builder
    {
        return $query->whereHas('documents', function (Builder $q) use ($days) {
            $q->where('status', 'approved')
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [today(), today()->addDays($days)]);
        });
    }

    // Drivers within $radiusKm of a point (Haversine, kilometres).
    public function scopeWithinRadius(Builder $query, float $lat, float $lng, float $radiusKm): Builder
    {
        $haversine = '(6371 * acos(cos(radians(?)) * cos(radians(current_lat)) * cos(radians(current_lng) - radians(?)) + sin(radians(?)) * sin(radians(current_lat))))';

        return $query->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->whereRaw("$haversine <= ?", [$lat, $lng, $lat, $radiusKm]);
    }

    // Order results by distance from a point (nearest first).
    public function scopeOrderByDistance(Builder $query, float $lat, float $lng): Builder
    {
        $haversine = '(6371 * acos(cos(radians(?)) * cos(radians(current_lat)) * cos(radians(current_lng) - radians(?)) + sin(radians(?)) * sin(radians(current_lat))))';

        return $query->orderByRaw("$haversine asc", [$lat, $lng, $lat]);
    }

    // ---------------------------------------------------------------------
    // Computed attributes
    // ---------------------------------------------------------------------

    // True when any approved document is already past its expiry date.
    public function getIsDocumentExpiredAttribute(): bool
    {
        return $this->documents()
            ->where('status', 'approved')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->exists();
    }

    // A driver may go online only when approved, active, and documents valid.
    public function getCanGoOnlineAttribute(): bool
    {
        return $this->status === 'approved'
            && $this->is_active
            && ! $this->is_document_expired;
    }

    // True once the driver has submitted all required onboarding documents +
    // a vehicle (insurance is optional). Used to route the app after login.
    public function getRegistrationCompletedAttribute(): bool
    {
        $types = $this->documents->pluck('type')->unique();

        $required = ['nid', 'driving_license', 'vehicle_registration', 'vehicle_photo'];
        foreach ($required as $type) {
            if (! $types->contains($type)) {
                return false;
            }
        }

        // NID + vehicle photo need both front & back; vehicle record must exist.
        $nid = $this->documents->firstWhere('type', 'nid');
        $vehiclePhoto = $this->documents->firstWhere('type', 'vehicle_photo');
        if (! $nid || ! $nid->back_image || ! $vehiclePhoto || ! $vehiclePhoto->back_image) {
            return false;
        }

        return $this->vehicles()->exists();
    }
}
