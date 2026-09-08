@extends('layouts.admin')

@section('title', 'Customer Report')
@section('page_title', 'Customer Report')

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 mb-5">
        <div class="flex flex-wrap items-center gap-3">
            <input type="date" name="from" value="{{ $filters['from'] }}" class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
            <input type="date" name="to" value="{{ $filters['to'] }}" class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.apply') }}</button>
        </div>
    </form>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        @foreach ([
            [__('admin.new_customers'), number_format($summary['new']), 'text-gray-900 dark:text-gray-100'],
            [__('admin.active_ordered'), number_format($summary['active']), 'text-indigo-600 dark:text-indigo-400'],
            [__('admin.retained_2plus'), number_format($summary['retained']), 'text-green-600 dark:text-green-400'],
            [__('admin.retention_rate'), $summary['retention_rate'] . '%', 'text-amber-600 dark:text-amber-400'],
        ] as [$label, $value, $color])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <h3 class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 font-semibold text-gray-800 dark:text-gray-100">{{ __('admin.top_customers_by_spending') }}</h3>
        @if ($customers->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.customer') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.total_orders') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.total_spent') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.avg_order_value') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.last_order') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($customers as $c)
                            @php($avg = $c->orders_count > 0 ? $c->total_spent / $c->orders_count : 0)
                            <tr class="rr-row">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.customers.show', $c->id) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">{{ $c->name }}</a>
                                    <p class="text-xs text-gray-400 dark:text-gray-400">{{ maskPhone($c->phone) }}</p>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-400">{{ $c->orders_count }}</td>
                                <td class="px-4 py-3 text-end font-medium text-gray-800 dark:text-gray-100">{{ number_format((float) $c->total_spent, 2) }}</td>
                                <td class="px-4 py-3 text-end text-gray-600 dark:text-gray-400">{{ number_format($avg, 2) }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $c->last_order_at ? \Illuminate\Support\Carbon::parse($c->last_order_at)->format('d M Y') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($customers->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $customers->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_customers_found') }}" />
        @endif
    </div>
@endsection
