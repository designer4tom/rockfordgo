<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'sender_type', 'sender_id', 'body', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    // Messages received by this side (i.e. sent by the other side).
    public function scopeReceivedBy(Builder $query, string $type): Builder
    {
        return $query->where('sender_type', $type === 'driver' ? 'user' : 'driver');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    // The sender model (User or Driver) — resolved on demand for display.
    public function sender(): ?Model
    {
        return $this->sender_type === 'driver'
            ? Driver::find($this->sender_id)
            : User::find($this->sender_id);
    }
}
