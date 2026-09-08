<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'token',
        'platform',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * Register (or refresh) a token for an owner. A token is globally unique —
     * if it moves to a new owner (e.g. shared device), it's reassigned.
     */
    public static function register(string $ownerType, int $ownerId, string $token, ?string $platform = null): self
    {
        return static::updateOrCreate(
            ['token' => $token],
            ['owner_type' => $ownerType, 'owner_id' => $ownerId, 'platform' => $platform, 'last_used_at' => now()]
        );
    }
}
