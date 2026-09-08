@props(['status' => ''])

@php
    $map = [
        'completed' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300',
        'dropped_off' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300',
        'pending' => 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300',
        'scheduled' => 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300',
        'accepted' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
        'go_to_pickup' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
        'confirm_arrival' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
        'picked_up' => 'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-300',
        'start_ride' => 'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-300',
        'cancelled' => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300',
        'rejected' => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300',
    ];
    $classes = $map[$status] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300';
    $label = ucwords(str_replace('_', ' ', $status));
@endphp

<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">{{ $label }}</span>
