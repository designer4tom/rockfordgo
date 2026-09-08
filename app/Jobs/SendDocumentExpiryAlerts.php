<?php

namespace App\Jobs;

use App\Models\DriverDocument;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Daily job: warn drivers about documents expiring in 30 / 7 days, and about
 * documents that have already expired.
 */
class SendDocumentExpiryAlerts implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications): void
    {
        $buckets = [
            ['days' => 30, 'from' => today()->addDays(8), 'to' => today()->addDays(30)],
            ['days' => 7, 'from' => today()->addDay(), 'to' => today()->addDays(7)],
        ];

        foreach ($buckets as $bucket) {
            $event = 'doc_expiry_' . $bucket['days']; // doc_expiry_30 | doc_expiry_7
            DriverDocument::with('driver:id,phone')
                ->where('status', 'approved')
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$bucket['from'], $bucket['to']])
                ->each(function (DriverDocument $doc) use ($notifications, $bucket, $event) {
                    if (! $doc->driver) {
                        return;
                    }
                    $label = ucwords(str_replace('_', ' ', $doc->type));
                    $notifications->notifyEvent($event, 'driver', $doc->driver_id, 'Document expiring soon',
                        "আপনার {$label} {$bucket['days']} দিনে expire হবে। নতুন document upload করুন।", 'document_expiry',
                        ['document_id' => (string) $doc->id], $doc->driver->phone);
                });
        }

        // Already expired documents.
        DriverDocument::with('driver:id')
            ->where('status', 'approved')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->each(function (DriverDocument $doc) use ($notifications) {
                if (! $doc->driver) {
                    return;
                }
                $label = ucwords(str_replace('_', ' ', $doc->type));
                $notifications->sendPush('driver', $doc->driver_id, 'Document expired',
                    "আপনার {$label} expire হয়ে গেছে। অনুগ্রহ করে নতুন document upload করুন।", 'document_expiry',
                    ['document_id' => (string) $doc->id]);
            });
    }
}
