<?php

namespace App\Jobs;

use App\Models\ScheduledNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Delivers a single scheduled broadcast row when its time arrives.
 */
class SendScheduledNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $scheduledNotificationId)
    {
    }

    public function handle(NotificationService $notifications): void
    {
        $scheduled = ScheduledNotification::find($this->scheduledNotificationId);
        if (! $scheduled || $scheduled->status !== 'pending') {
            return;
        }

        try {
            // target_type encodes the broadcast audience (e.g. all_customers).
            dispatch_sync(new BroadcastNotification(
                $scheduled->target_type,
                $scheduled->title,
                $scheduled->body,
                $scheduled->target_id ?: null,
            ));

            $scheduled->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            $scheduled->update(['status' => 'failed']);
        }
    }
}
