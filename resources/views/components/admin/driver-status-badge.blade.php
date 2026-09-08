@props(['status' => ''])

@php
    $map = [
        'pending' => 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300',
        'approved' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300',
        'rejected' => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300',
        'suspended' => 'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300',
        'blocked' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
    ];
    $classes = $map[$status] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300';
@endphp

<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">{{ ucfirst($status) }}</span>
