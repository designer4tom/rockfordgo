@extends('layouts.admin')

@section('title', 'Revenue')
@section('page_title', 'Revenue Summary')

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($money = fn ($v) => number_format((float) $v, 0) . ' ' . $currency)

@section('content')
    {{-- Filters --}}
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 mb-5">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin.from') }}</label>
                <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin.to') }}</label>
                <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin.group_by') }}</label>
                <select name="group_by" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
                    @foreach (['day' => __('admin.day'), 'week' => __('admin.week'), 'month' => __('admin.month')] as $val => $label)
                        <option value="{{ $val }}" @selected($groupBy === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.apply') }}</button>
        </div>
    </form>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        @foreach ([
            [__('admin.gross_revenue'), $summary['gross'], 'text-gray-900 dark:text-gray-100'],
            [__('admin.admin_commission'), $summary['commission'], 'text-indigo-600 dark:text-indigo-400'],
            [__('admin.driver_payouts'), $summary['driver_payouts'], 'text-blue-600 dark:text-blue-400'],
            [__('admin.refunds'), $summary['refunds'], 'text-red-600 dark:text-red-400'],
            [__('admin.net_revenue'), $summary['net'], 'text-green-600 dark:text-green-400'],
        ] as [$label, $value, $color])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold {{ $color }}">{{ $money($value) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 mb-5">
        <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">{{ __('admin.revenue_over_time') }}</h3>
        @if ($breakdown->count())
            <canvas id="revenueChart" height="90"></canvas>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500 py-8 text-center">{{ __('admin.no_data_range') }}</p>
        @endif
    </div>

    {{-- Breakdown table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($breakdown->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.period') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.rides') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.parcels') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.gross') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.commission') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.refunds') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.net') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($breakdown as $row)
                            <tr class="rr-row">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $row['bucket'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $row['rides'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $row['parcels'] }}</td>
                                <td class="px-4 py-3 text-end text-gray-800 dark:text-gray-100">{{ number_format($row['gross'], 2) }}</td>
                                <td class="px-4 py-3 text-end text-indigo-600 dark:text-indigo-400">{{ number_format($row['commission'], 2) }}</td>
                                <td class="px-4 py-3 text-end text-red-600 dark:text-red-400">{{ number_format($row['refunds'], 2) }}</td>
                                <td class="px-4 py-3 text-end font-medium text-green-600 dark:text-green-400">{{ number_format($row['net'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-admin.empty-state :message="__('admin.no_completed_orders_range')" />
        @endif
    </div>
@endsection

@push('scripts')
@if ($breakdown->count())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const rev = @json($chart);
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: rev.labels,
            datasets: [
                { label: 'Gross', data: rev.gross, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.3 },
                { label: 'Net', data: rev.net, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.1)', fill: true, tension: 0.3 },
            ],
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
    });
</script>
@endif
@endpush
