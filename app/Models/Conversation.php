<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Chat between the customer and the driver of ONE order (ride or parcel).
 * Opened when the driver accepts, closed when the order completes/cancels.
 */
class Conversation extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'order_id', 'user_id', 'driver_id', 'status', 'last_message_at', 'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // ----- Relations -----

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // ----- Scopes -----

    // Conversations belonging to one side of the chat.
    public function scopeForParticipant(Builder $query, string $type, int $id): Builder
    {
        return $type === 'driver'
            ? $query->where('driver_id', $id)
            : $query->where('user_id', $id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    // ----- Helpers -----

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    // Is this customer/driver a participant of this conversation?
    public function isParticipant(string $type, int $id): bool
    {
        return $type === 'driver'
            ? (int) $this->driver_id === $id
            : (int) $this->user_id === $id;
    }

    // The other side of the conversation, as [type, id].
    public function counterpart(string $senderType): array
    {
        return $senderType === 'driver'
            ? ['user', (int) $this->user_id]
            : ['driver', (int) $this->driver_id];
    }
}
