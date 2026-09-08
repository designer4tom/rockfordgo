<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Print the full dispatch + lifecycle trace of one order:
 *   php artisan order:trace 123
 *   php artisan order:trace RR-000123
 *
 * Shows who the order was offered to, who rejected / didn't respond / accepted,
 * the live in-flight dispatch state, and the status timeline. Use this to debug
 * "order created but driver app didn't receive it".
 */
class TraceOrderCommand extends Command
{
    protected $signature = 'order:trace {order : Order id or order_number}';

    protected $description = 'Trace an order’s dispatch attempts and status timeline';

    public function handle(): int
    {
        $key = $this->argument('order');
        $order = Order::with(['driver:id,name,phone', 'dispatchLogs.driver:id,name,phone'])
            ->where('id', $key)
            ->orWhere('order_number', $key)
            ->first();

        if (! $order) {
            $this->error("Order [{$key}] not found.");

            return self::FAILURE;
        }

        $this->newLine();
        $this->line("<options=bold>Order #{$order->order_number}</> (id {$order->id})  type=<fg=cyan>{$order->type}</>  status=<fg=yellow>{$order->status}</>");
        $this->line('Pickup: ' . $order->pickup_address);
        $this->line('Driver: ' . ($order->driver ? "{$order->driver->name} ({$order->driver->phone})" : '— none —'));
        $this->newLine();

        // ---- Dispatch attempts ----
        $this->line('<options=bold>Dispatch attempts</>');
        if ($order->dispatchLogs->isEmpty()) {
            $this->warn('  No dispatch logs — the order was never offered to any driver.');
            $this->line('  → check: queue worker running? any online+approved+free driver in radius? DispatchOrderToDrivers queued?');
        } else {
            $rows = $order->dispatchLogs->sortBy('id')->map(function ($l) {
                return [
                    $l->created_at?->format('H:i:s'),
                    $l->attempt ?: '—',
                    $l->label(),
                    $l->driver ? "{$l->driver->name} (#{$l->driver_id})" : '—',
                    $l->radius_km ? $l->radius_km . ' km' : '—',
                ];
            })->all();
            $this->table(['Time', 'Try', 'Event', 'Driver', 'Radius'], $rows);

            $offered = $order->dispatchLogs->where('event', 'offered')->count();
            $rejected = $order->dispatchLogs->where('event', 'rejected')->count();
            $timeout = $order->dispatchLogs->where('event', 'timeout')->count();
            $this->line("  Summary: offered to <fg=cyan>{$offered}</> driver(s) · <fg=red>{$rejected}</> rejected · <fg=yellow>{$timeout}</> no-response");
        }
        $this->newLine();

        // ---- Live in-flight state (cache) ----
        $state = Cache::get("order:dispatch:{$order->id}");
        if ($state) {
            $this->line('<options=bold>Live dispatch state (in progress)</>');
            $this->line('  currently offered to driver: ' . ($state['current'] ?? '—'));
            $this->line('  tried so far: ' . (implode(', ', $state['tried'] ?? []) ?: '—'));
            $this->line('  attempt: ' . ($state['attempt'] ?? 0) . '  radius: ' . ($state['radius'] ?? '—') . ' km');
            $this->newLine();
        }

        // ---- Status timeline ----
        $this->line('<options=bold>Status timeline</>');
        $stages = [
            'created_at' => 'Created', 'accepted_at' => 'Accepted', 'arrived_at' => 'Arrived at pickup',
            'picked_up_at' => 'Picked up', 'started_at' => 'Started', 'dropped_at' => 'Dropped off',
            'completed_at' => 'Completed', 'cancelled_at' => 'Cancelled',
        ];
        foreach ($stages as $col => $label) {
            if (! empty($order->{$col})) {
                $this->line(sprintf('  <fg=green>✓</> %-18s %s', $label, $order->{$col}));
            }
        }
        $this->newLine();

        return self::SUCCESS;
    }
}
