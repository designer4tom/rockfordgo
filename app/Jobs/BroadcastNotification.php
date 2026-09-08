<?php

namespace App\Jobs;

use App\Models\Driver;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Fans out an admin broadcast to every customer or driver in the target
 * audience, in batches, persisting an in-app row and attempting push for each.
 */
class BroadcastNotification implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    /**
     * @param  string  $target  all_customers | all_drivers | zone_customers | zone_drivers
     */
    public function __construct(
        public string $target,
        public string $title,
        public string $body,
        public ?int $zoneId = null,
    ) {
    }

    public function handle(NotificationService $notifications): void
    {
        [$model, $type] = str_contains($this->target, 'driver')
            ? [Driver::class, 'driver']
            : [User::class, 'user'];

        $query = $model::query();

        // Zone scoping (drivers have zone_id; customers are scoped via their orders' zones).
        if ($this->zoneId) {
            if ($type === 'driver') {
                $query->where('zone_id', $this->zoneId);
            } else {
                $query->whereHas('orders.driver', fn ($q) => $q->where('zone_id', $this->zoneId));
            }
        }

        $query->select('id')->chunkById(500, function ($rows) use ($notifications, $type) {
            $ids = $rows->pluck('id')->all();
            $notifications->sendPushToMany($type, $ids, $this->title, $this->body, 'broadcast');
        });
    }
}
