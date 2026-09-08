<?php

namespace App\Jobs;

use App\Services\DispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs ~1s after a driver's decision window. If the driver never responded,
 * the order moves on to the next driver. No polling, no sleep — a single
 * delayed job per offer.
 */
class CheckOrderResponseJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId, public int $driverId)
    {
    }

    public function handle(DispatchService $dispatch): void
    {
        $dispatch->handleTimeout($this->orderId, $this->driverId);
    }
}
