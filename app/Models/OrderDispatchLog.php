<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDispatchLog extends Model
{
    protected $fillable = [
        'order_id', 'driver_id', 'event', 'attempt', 'radius_km', 'meta',
    ];

    protected $casts = [
        'attempt' => 'integer',
        'radius_km' => 'decimal:2',
        'meta' => 'array',
    ];

    // Human-readable label per event (used by the admin timeline + CLI tracer).
    public const LABELS = [
        'offered' => 'Offered to driver',
        'accepted' => 'Accepted by driver',
        'rejected' => 'Rejected by driver',
        'timeout' => 'No response (timed out)',
        'no_driver_found' => 'No driver found',
        'cancelled' => 'Order cancelled',
        'reassigned' => 'Reassigned by admin',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->event] ?? $this->event;
    }

    // Tailwind badge classes per event (used by the admin dispatch-log timeline).
    public function badgeClass(): string
    {
        return [
            'offered' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
            'accepted' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300',
            'rejected' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            'timeout' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
            'no_driver_found' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
            'cancelled' => 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
            'reassigned' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
        ][$this->event] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200';
    }
}
