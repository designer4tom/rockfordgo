@extends('layouts.admin')

@section('title', 'Order Report')
@section('page_title', 'Order Report')

@section('content')
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 mb-5">
        <div class="flex flex-wrap items-center gap-3">
            <input type="date" name="from" value="{{ $filters['from'] }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <input type="date" name="to" value="{{ $filters['to'] }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <select name="type" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_types') }}</option>
                <option value="ride" @selected($filters['type'] === 'ride')>{{ __('admin.ride') }}</option>
                <option value="parcel" @selected($filters['type'] === 'parcel')>{{ __('admin.parcel') }}</option>
            </select>
            <select name="zone_id" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_zones') }}</option>
                @foreach ($zones as $z)<option value="{{ $z->id }}" @selected((string) $filters['zoneId'] === (string) $z->id)>{{ $z->name }}</option>@endforeach
            </select>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.apply') }}</button>
        </div>
    </form>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        @foreach ([
            [__('admin.total_orders'), number_format($total), 'text-gray-900 dark:text-gray-100'],
            [__('admin.completed'), number_format($completed), 'text-green-600 dark:text-green-400'],
            [__('admin.cancelled'), number_format($cancelled), 'text-red-600 dark:text-red-400'],
            [__('admin.cancellation_rate'), $cancellationRate . '%', 'text-amber-600 dark:text-amber-400'],
        ] as [$label, $value, $color])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3 text-sm">{{ __('admin.orders_by_status') }}</h3>
            <canvas id="statusChart" height="180"></canvas>
        </div>
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3 text-sm">{{ __('admin.orders_by_hour') }}</h3>
            <canvas id="hourChart" height="90"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3 text-sm">{{ __('admin.orders_by_dow') }}</h3>
            <canvas id="dowChart" height="90"></canvas>
        </div>
        {{-- Cancellation analysis --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3 text-sm">{{ __('admin.cancellation_analysis') }}</h3>
            <div class="space-y-1.5 text-sm mb-3">
                @foreach (['user' => __('admin.customer'), 'driver' => __('admin.driver'), 'admin' => __('admin.admin')] as $by => $label)
                    @php($c = $cancelledBy[$by] ?? 0)
                    <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">{{ $label }}</span><span class="font-medium text-gray-800 dark:text-gray-100">{{ $cancelled > 0 ? round($c / $cancelled * 100) : 0 }}%</span></div>
                @endforeach
            </div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('admin.top_reasons') }}</p>
            <ul class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                @forelse ($reasons as $reason => $count)
                    <li class="truncate">• {{ $reason }} ({{ $count }})</li>
                @empty <li class="text-gray-400 dark:text-gray-400">{{ __('admin.none') }}</li> @endforelse
            </ul>
        </div>
    </div>

    {{-- Daily table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if (count($daily))
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.total') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.completed') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.cancelled') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.avg_amount') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.avg_distance') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($daily as $row)
                            <tr class="rr-row">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $row['d'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-400">{{ $row['total'] }}</td>
                                <td class="px-4 py-3 text-center text-green-600 dark:text-green-400">{{ $row['completed'] }}</td>
                                <td class="px-4 py-3 text-center text-red-600 dark:text-red-400">{{ $row['cancelled'] }}</td>
                                <td class="px-4 py-3 text-end text-gray-700 dark:text-gray-400">{{ number_format((float) $row['avg_amount'], 2) }}</td>
                                <td class="px-4 py-3 text-end text-gray-700 dark:text-gray-400">{{ number_format((float) $row['avg_distance'], 1) }} km</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-admin.empty-state message="{{ __('admin.no_orders_in_range') }}" />
        @endif
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const byStatus = @json($byStatus);
    const byHour = @json($byHour);
    const byDow = @json($byDow);
    const dowLabels = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    new Chart(document.getElementById('statusChart'), {
        type: 'pie',
        data: { labels: Object.keys(byStatus), datasets: [{ data: Object.values(byStatus),
            backgroundColor: ['#22c55e','#eab308','#3b82f6','#6366f1','#14b8a6','#ef4444','#9ca3af','#a855f7','#06b6d4','#f97316'] }] },
        options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } },
    });

    const hours = Array.from({length: 24}, (_, i) => i);
    new Chart(document.getElementById('hourChart'), {
        type: 'bar',
        data: { labels: hours.map(h => h + ':00'), datasets: [{ label: 'Orders', data: hours.map(h => byHour[h] || 0), backgroundColor: '#6366f1' }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });

    new Chart(document.getElementById('dowChart'), {
        type: 'bar',
        data: { labels: dowLabels, datasets: [{ label: 'Orders', data: [1,2,3,4,5,6,7].map(d => byDow[d] || 0), backgroundColor: '#14b8a6' }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });
</script>
@endpush
