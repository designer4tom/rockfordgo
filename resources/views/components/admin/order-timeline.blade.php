@props(['order'])

@php
    // Ordered lifecycle steps mapped to their timestamp column + a human label.
    $steps = [
        ['key' => 'scheduled', 'at' => $order->scheduled_at, 'label' => 'Scheduled', 'desc' => $order->scheduled_at ? 'Scheduled for ' . $order->scheduled_at->format('d M Y, H:i') : 'Scheduled'],
        ['key' => 'pending', 'at' => $order->created_at, 'label' => 'Pending', 'desc' => 'Looking for driver'],
        ['key' => 'accepted', 'at' => $order->accepted_at, 'label' => 'Accepted', 'desc' => 'Driver ' . ($order->driver->name ?? '') . ' accepted'],
        ['key' => 'go_to_pickup', 'at' => $order->accepted_at, 'label' => 'Heading to Pickup', 'desc' => 'Driver heading to pickup'],
        ['key' => 'confirm_arrival', 'at' => $order->arrived_at, 'label' => 'Arrived', 'desc' => 'Driver arrived at pickup'],
        ['key' => 'picked_up', 'at' => $order->picked_up_at, 'label' => 'Picked Up', 'desc' => $order->type === 'parcel' ? 'Parcel picked up' : 'OTP verified'],
        ['key' => 'start_ride', 'at' => $order->started_at, 'label' => 'Started', 'desc' => ($order->type === 'parcel' ? 'Delivery' : 'Ride') . ' started'],
        ['key' => 'dropped_off', 'at' => $order->dropped_at, 'label' => 'Dropped Off', 'desc' => 'Reached destination'],
        ['key' => 'completed', 'at' => $order->completed_at, 'label' => 'Completed', 'desc' => 'Order completed'],
    ];

    // For cancelled/rejected orders, only show the path up to that terminal state.
    $cancelled = in_array($order->status, ['cancelled', 'rejected'], true);
@endphp

<ol class="relative border-s-2 border-gray-100 dark:border-gray-700 ms-3 space-y-6">
    @foreach ($steps as $step)
        @php($done = ! is_null($step['at']))
        <li class="ms-6">
            <span class="absolute -start-[9px] flex h-4 w-4 items-center justify-center rounded-full ring-4 ring-white dark:ring-gray-800
                {{ $done ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-600' }}"></span>
            <div class="{{ $done ? '' : 'opacity-40' }}">
                <p class="text-sm font-medium {{ $done ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">{{ $step['label'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $step['desc'] }}</p>
                @if ($done)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $step['at']->format('d M Y, H:i') }}</p>
                @endif
            </div>
        </li>
    @endforeach

    @if ($cancelled)
        <li class="ms-6">
            <span class="absolute -start-[9px] flex h-4 w-4 items-center justify-center rounded-full ring-4 ring-white dark:ring-gray-800 bg-red-500"></span>
            <div>
                <p class="text-sm font-medium text-red-700 dark:text-red-400">{{ ucfirst($order->status) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ ucfirst($order->status) }} by {{ $order->cancelled_by ?? 'system' }}@if($order->cancellation_reason): {{ $order->cancellation_reason }}@endif
                </p>
                @if ($order->cancelled_at)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $order->cancelled_at->format('d M Y, H:i') }}</p>
                @endif
            </div>
        </li>
    @endif
</ol>
