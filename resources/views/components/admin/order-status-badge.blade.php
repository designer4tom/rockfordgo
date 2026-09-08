@props(['status' => '', 'pulse' => false])

@php
    $map = [
        'scheduled' => 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300',
        'pending' => 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300',
        'accepted' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
        'go_to_pickup' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
        'confirm_arrival' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
        'picked_up' => 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300',
        'start_ride' => 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300',
        'dropped_off' => 'bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300',
        'completed' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300',
        'cancelled' => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300',
        'rejected' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
    ];
    $classes = $map[$status] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300';
    $label = ucwords(str_replace('_', ' ', $status));
    $isPending = $status === 'pending';
@endphp

<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">
    @if ($isPending)
        <span class="relative flex h-1.5 w-1.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-500 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-yellow-600"></span>
        </span>
    @endif
    {{ $label }}
</span>
