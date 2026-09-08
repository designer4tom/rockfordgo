<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'avatar',
        'wallet_balance',
        'due_amount',
        'withdrawal_method',
        'withdrawal_account',
        'referral_code',
        'referred_by',
        'is_active',
        'fcm_token',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'deletion_requested_at',
    ];

    protected $casts = [
        'wallet_balance' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'is_active' => 'boolean',
        // Chat presence heartbeat.
        'last_seen_at' => 'datetime',
    ];

    // The user who referred this user.
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    // Users referred by this user.
    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function favouriteLocations(): HasMany
    {
        return $this->hasMany(FavouriteLocation::class);
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    // Wallet transactions belonging to this user (polymorphic-style owner columns).
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'owner_id')
            ->where('owner_type', 'user');
    }

    // Wallet withdrawal requests made by this customer.
    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'user_id');
    }

    // Sender delivery-charge due ledger (COD parcels).
    public function dueTransactions(): HasMany
    {
        return $this->hasMany(UserDueTransaction::class);
    }

    // FCM device tokens (a customer may have several devices).
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class, 'owner_id')->where('owner_type', 'user');
    }

    // Referral records where this user is the referrer.
    public function referralRecords(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    // Disputes raised by this customer (raised_by enum + raised_by_id columns).
    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class, 'raised_by_id')
            ->where('raised_by', 'user');
    }

    // Admin block/unblock history.
    public function statusLogs(): HasMany
    {
        return $this->hasMany(CustomerStatusLog::class);
    }

    // ----- Scopes -----

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }
}
