@extends('layouts.admin')

@section('title', __('admin.dashboard'))

{{-- the greeting lives in the header bar, not in the page body --}}
@section('page_title', __('admin.welcome_back') . ', ' . adminUser()->name)
@section('page_subtitle', __('admin.dashboard_subtitle', ['app' => appName()]))

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
<div class="space-y-6">
    {{-- Row 1: Today stats --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stats-card class="dash-in" style="--i:0" title="{!! __('admin.today_rides') !!}" :value="$stats['today_rides']" id="stat-today-rides" color="indigo"
            :trend="$stats['today_rides_change']" trendLabel="{{ __('admin.vs_yesterday') }}">
            <x-slot:icon><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/></svg></x-slot:icon>
        </x-admin.stats-card>

        <x-admin.stats-card class="dash-in" style="--i:1" title="{!! __('admin.today_parcels') !!}" :value="$stats['today_parcels']" id="stat-today-parcels" color="emerald"
            :trend="$stats['today_parcels_change']" trendLabel="{{ __('admin.vs_yesterday') }}">
            <x-slot:icon><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></x-slot:icon>
        </x-admin.stats-card>

        <x-admin.stats-card class="dash-in" style="--i:2" title="{!! __('admin.today_revenue') !!}" value="{{ $currency }} {{ number_format($stats['today_revenue'], 0) }}" id="stat-today-revenue" color="amber"
            :trend="$stats['today_revenue_change']" trendLabel="{{ __('admin.vs_yesterday') }}">
            <x-slot:icon><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg></x-slot:icon>
        </x-admin.stats-card>

        <x-admin.stats-card class="dash-in" style="--i:3" title="{!! __('admin.online_drivers') !!}" :value="$stats['active_drivers']" id="stat-online-drivers" color="rose"
            subtitle="{{ __('admin.total') }}: {{ number_format($stats['total_drivers']) }}">
            <x-slot:icon><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg></x-slot:icon>
        </x-admin.stats-card>
    </div>

    {{-- Row 2: Alert cards. Each links to the screen that clears it; the link is
         dropped when the admin lacks permission for that module. --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.alert-card class="dash-in" style="--i:0" title="{{ __('admin.pending_approvals') }}" :count="$stats['pending_approvals']" id="stat-pending-approvals" color="amber"
            :link="adminCan('drivers', 'read') ? route('admin.drivers.pending') : null" />
        <x-admin.alert-card class="dash-in" style="--i:1" title="{{ __('admin.open_disputes') }}" :count="$stats['pending_disputes']" id="stat-pending-disputes" color="orange"
            :link="adminCan('disputes', 'read') ? route('admin.disputes.index') : null" />
        <x-admin.alert-card class="dash-in" style="--i:2" title="{{ __('admin.pending_withdrawals') }}" :count="$stats['pending_withdrawals']" id="stat-pending-withdrawals" color="blue"
            :link="adminCan('payments', 'read') ? route('admin.payments.withdrawals') : null" />
        <x-admin.alert-card class="dash-in" style="--i:3" title="{{ __('admin.active_sos') }}" :count="$stats['active_sos']" id="stat-active-sos" color="red"
            :link="adminCan('sos', 'read') ? route('admin.sos.index') : null" />
    </div>

    {{-- Charts row --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="dash-card dash-in lg:col-span-2 rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800" style="--i:0">
            <h3 class="mb-5 text-[15px] font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.revenue_last_7_days') }}</h3>
            <canvas id="revenueChart" height="110"></canvas>
        </div>
        <div class="dash-card dash-in rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800" style="--i:1">
            <h3 class="mb-5 text-[15px] font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.driver_status') }}</h3>
            <canvas id="driverStatusChart" height="220"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="dash-card dash-in lg:col-span-2 rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800" style="--i:0">
            <h3 class="mb-5 text-[15px] font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.orders_last_7_days') }}</h3>
            <canvas id="ordersChart" height="110"></canvas>
        </div>

        {{-- Recent driver registrations --}}
        <div class="dash-card dash-in rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800" style="--i:1">
            <h3 class="mb-5 text-[15px] font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.new_drivers') }}</h3>
            <ul class="space-y-3">
                @forelse ($recentDrivers as $driver)
                    <li class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-semibold shrink-0">
                            {{ strtoupper(substr($driver->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $driver->name }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-400">{{ $driver->phone }} · {{ $driver->created_at->diffForHumans() }}</p>
                        </div>
                        @if ($driver->status === 'pending')
                            <span class="text-xs rounded-full bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 px-2 py-0.5">{{ __('admin.pending') }}</span>
                        @endif
                    </li>
                @empty
                    <li class="text-sm text-gray-400 dark:text-gray-400 text-center py-6">{{ __('admin.no_drivers_yet') }}</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Recent orders --}}
    <div class="dash-in overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
            <h3 class="text-[15px] font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.recent_orders') }}</h3>
            @if (adminCan('orders', 'read'))
                <a href="{{ route('admin.orders.index') }}" class="group inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                    {{ __('admin.view_all_orders') }}
                    <svg class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="rr-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.type') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.customer') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.drivers') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.amount') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.time') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                    @forelse ($recentOrders as $order)
                        <tr class="rr-row">
                            <td class="px-6 py-3 font-mono text-gray-700 dark:text-gray-100">{{ $order->order_number }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $order->type === 'ride' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' }}">
                                    {{ ucfirst($order->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-100">{{ $order->user->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $order->driver->name ?? __('admin.searching') }}</td>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-100">{{ $currency }} {{ number_format($order->total_amount, 0) }}</td>
                            <td class="px-6 py-3">
                                <x-admin.status-badge :status="$order->status" />
                            </td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $order->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-400 dark:text-gray-400">{{ __('admin.no_orders_yet') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Expiring documents --}}
    @if ($expiringDocs->isNotEmpty())
        <div class="dash-in overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <h3 class="text-[15px] font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.documents_expiring_soon') }}</h3>
                @if (adminCan('drivers', 'read'))
                    <a href="{{ route('admin.drivers.expiring-documents') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">{{ __('admin.view_all') }}</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.drivers') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.document') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.expiry_date') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.days_left') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($expiringDocs as $doc)
                            @php($daysLeft = (int) round(now()->startOfDay()->diffInDays($doc->expiry_date, false)))
                            <tr class="rr-row">
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-100">{{ $doc->driver->name ?? '—' }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $doc->type) }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $doc->expiry_date->format('d M Y') }}</td>
                                <td class="px-6 py-3">
                                    <span class="font-medium {{ $daysLeft < 7 ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-100' }}">{{ $daysLeft }} {{ __('admin.days') }}</span>
                                </td>
                                <td class="px-6 py-3 text-end">
                                    <button class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('admin.notify_driver') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Online driver map --}}
    <div class="dash-in rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-5 flex items-center justify-between">
            <h3 class="text-[15px] font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.online_drivers_map') }}</h3>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/25 dark:text-emerald-400">
                <span class="relative flex h-1.5 w-1.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                </span>
                <span id="map-driver-count">0</span> {{ __('admin.online') }}
            </span>
        </div>
        @if ($mapsKey)
            <div id="driverMap" class="w-full h-80 rounded-lg bg-gray-100 dark:bg-gray-900/40"></div>
        @else
            <div class="w-full h-40 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-dashed border-gray-200 dark:border-gray-700 flex items-center justify-center text-center text-sm text-gray-400 dark:text-gray-400 px-4">
                {{ __('admin.maps_key_not_configured') }} — <a href="{{ route('admin.settings.map') }}" class="text-indigo-600 hover:underline ms-1">{{ __('admin.map') }}</a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const chartData = @json($charts);

    // Revenue line chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: @json(__('admin.ride')), data: chartData.ride_revenue, borderColor: '#1a56db', backgroundColor: 'rgba(26,86,219,.1)', tension: .3, fill: true },
                { label: @json(__('admin.parcel')), data: chartData.parcel_revenue, borderColor: '#057a55', backgroundColor: 'rgba(5,122,85,.1)', tension: .3, fill: true },
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
    });

    // Orders bar chart
    new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: @json(__('admin.ride')), data: chartData.ride_count, backgroundColor: '#1a56db' },
                { label: @json(__('admin.parcel')), data: chartData.parcel_count, backgroundColor: '#057a55' },
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    // Driver status doughnut
    const ds = chartData.driver_status;
    new Chart(document.getElementById('driverStatusChart'), {
        type: 'doughnut',
        data: {
            labels: [@json(__('admin.online')), @json(__('admin.offline')), @json(__('admin.suspended')), @json(__('admin.pending'))],
            datasets: [{ data: [ds.online, ds.offline, ds.suspended, ds.pending], backgroundColor: ['#057a55', '#9ca3af', '#c81e1e', '#c27803'] }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // Periodic stats refresh (every 60s) without reloading the page.
    setInterval(() => {
        fetch('{{ route('admin.dashboard.stats') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(d => {
                const cur = @json($currency);
                const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
                set('stat-today-rides', d.today_rides);
                set('stat-today-parcels', d.today_parcels);
                set('stat-today-revenue', cur + ' ' + Number(d.today_revenue).toLocaleString());
                set('stat-online-drivers', d.active_drivers);
                set('stat-pending-approvals', d.pending_approvals);
                set('stat-pending-disputes', d.pending_disputes);
                set('stat-pending-withdrawals', d.pending_withdrawals);
                set('stat-active-sos', d.active_sos);
            })
            .catch(() => {});
    }, 60000);

@if ($mapsKey)
    // ---- Live online-driver map ----
    let driverMap = null;
    let driverMarkers = [];

    window.initDriverMap = function () {
        driverMap = new google.maps.Map(document.getElementById('driverMap'), {
            center: { lat: {{ $mapCenter['lat'] }}, lng: {{ $mapCenter['lng'] }} },
            zoom: {{ $mapCenter['zoom'] }},
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });
        loadOnlineDrivers();
        setInterval(loadOnlineDrivers, 20000); // refresh every 20s
    };

    function loadOnlineDrivers() {
        fetch('{{ route('admin.dashboard.online-drivers') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(d => {
                const drivers = d.drivers || [];
                document.getElementById('map-driver-count').textContent = drivers.length;

                driverMarkers.forEach(m => m.setMap(null));
                driverMarkers = [];
                if (!driverMap) return;

                const bounds = new google.maps.LatLngBounds();
                drivers.forEach(dr => {
                    const pos = { lat: dr.lat, lng: dr.lng };
                    const marker = new google.maps.Marker({
                        position: pos, map: driverMap, title: dr.name,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 8, fillColor: '#057a55', fillOpacity: 1,
                            strokeColor: '#fff', strokeWeight: 2,
                        },
                    });
                    const info = new google.maps.InfoWindow({
                        content: '<div style="font-size:13px"><b>' + dr.name + '</b><br>' + (dr.phone || '') + '</div>',
                    });
                    marker.addListener('click', () => info.open(driverMap, marker));
                    driverMarkers.push(marker);
                    bounds.extend(pos);
                });
                if (drivers.length) driverMap.fitBounds(bounds, 60);
            })
            .catch(() => {});
    }
@endif
</script>
@if ($mapsKey)
<script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initDriverMap&loading=async" async defer></script>
@endif
@endpush
