@extends('layouts.admin')

@section('title', 'Revenue Report')
@section('page_title', 'Revenue Report')

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($money = fn ($v) => number_format((float) $v, 0) . ' ' . $currency)

@section('content')
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 mb-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <input type="date" name="from" value="{{ $filters['from'] }}" placeholder="{{ __('admin.from') }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <input type="date" name="to" value="{{ $filters['to'] }}" placeholder="{{ __('admin.to') }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <select name="group_by" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                @foreach (['daily' => __('admin.daily'), 'weekly' => __('admin.weekly'), 'monthly' => __('admin.monthly')] as $v => $l)
                    <option value="{{ $v }}" @selected($filters['group_by'] === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <select name="service_type" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_services') }}</option>
                <option value="ride" @selected($filters['service_type'] === 'ride')>{{ __('admin.ride') }}</option>
                <option value="parcel" @selected($filters['service_type'] === 'parcel')>{{ __('admin.parcel') }}</option>
            </select>
            <select name="zone_id" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_zones') }}</option>
                @foreach ($zones as $z)<option value="{{ $z->id }}" @selected((string) $filters['zone_id'] === (string) $z->id)>{{ $z->name }}</option>@endforeach
            </select>
            <select name="payment_method" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_payments') }}</option>
                @foreach (['cash','online','wallet','cod'] as $pm)<option value="{{ $pm }}" @selected($filters['payment_method'] === $pm)>{{ ucfirst($pm) }}</option>@endforeach
            </select>
        </div>
        <div class="flex gap-2 mt-3">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.apply') }}</button>
            <a href="{{ route('admin.reports.revenue.export', request()->query()) }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.export_csv') }}</a>
            <a href="{{ route('admin.reports.revenue.pdf', request()->query()) }}" target="_blank" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.pdf') }}</a>
        </div>
    </form>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        @foreach ([
            [__('admin.gross_revenue'), $summary['gross'], 'text-gray-900 dark:text-gray-100'],
            [__('admin.commission'), $summary['commission'], 'text-indigo-600 dark:text-indigo-400'],
            [__('admin.driver_payout'), $summary['driver_payouts'], 'text-blue-600 dark:text-blue-400'],
            [__('admin.net_revenue'), $summary['net'], 'text-green-600 dark:text-green-400'],
        ] as [$label, $value, $color])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold {{ $color }}">{{ $money($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 mb-5">
        <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">{{ __('admin.revenue_trend_ride_parcel') }}</h3>
        @if ($breakdown->count())
            <canvas id="revChart" height="90"></canvas>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-400 py-8 text-center">{{ __('admin.no_data_range') }}</p>
        @endif
    </div>

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
            <x-admin.empty-state message="{{ __('admin.no_completed_orders_range') }}" />
        @endif
    </div>
@endsection

@push('scripts')
@if ($breakdown->count())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const rc = @json($chart);
    new Chart(document.getElementById('revChart'), {
        type: 'line',
        data: { labels: rc.labels, datasets: [
            { label: 'Ride', data: rc.ride, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.3 },
            { label: 'Parcel', data: rc.parcel, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', fill: true, tension: 0.3 },
        ]},
        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
    });
</script>
@endif
@endpush
