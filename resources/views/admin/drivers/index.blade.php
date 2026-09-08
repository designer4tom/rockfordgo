@extends('layouts.admin')

@section('title', 'Drivers')
@section('page_title', 'Driver Management')
@section('page_subtitle', __('admin.drivers_subtitle'))

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    @php($driverCols = [
        'id' => __('admin.id'),
        'driver' => __('admin.driver'),
        'phone' => __('admin.phone'),
        'vehicle' => __('admin.vehicle'),
        'zone' => __('admin.zone'),
        'status' => __('admin.status'),
        'rating' => __('admin.rating'),
        'trips' => __('admin.trips'),
        'due' => __('admin.due'),
        'joined' => __('admin.joined'),
        'actions' => __('admin.actions'),
    ])
    @php($hiddenCols = adminUser()?->hiddenColumns('drivers') ?? [])
    @php($colHide = fn (string $k) => in_array($k, $hiddenCols, true) ? 'hidden ' : '')

    @php($fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/12 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100')
    @php($labelClass = 'mb-1.5 block text-[13px] font-semibold text-gray-700 dark:text-gray-300')

    <div class="flex items-center justify-between mb-3 gap-3 flex-wrap">
        <div class="flex items-center gap-3">
            <x-admin.search-box :action="route('admin.drivers.index')"
                                :value="$filters['search'] ?? ''"
                                :preserve="$filters"
                                placeholder="{{ __('admin.name_or_phone') }}" />
        </div>
        <div class="flex items-center gap-2">
            <x-admin.export-menu
                export-route="admin.drivers.export"
                :import-route="adminCan('drivers', 'write') ? 'admin.drivers.import' : null"
                template-route="admin.drivers.import.template"
                :query="request()->query()" />

            <x-admin.column-picker table="drivers" :columns="$driverCols" :hidden="$hiddenCols" />

            <x-admin.filter-drawer :action="route('admin.drivers.index')" :reset-url="route('admin.drivers.index')" :applied="$filters">
                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.search') }}</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('admin.name_or_phone') }}" class="{{ $fieldClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.status') }}</label>
                    <select name="status" class="{{ $fieldClass }}">
                        <option value="">{{ __('admin.all_statuses') }}</option>
                        @foreach (['pending','approved','rejected','suspended','blocked'] as $st)
                            <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.zones') }}</label>
                    <select name="zone_id" class="{{ $fieldClass }}">
                        <option value="">{{ __('admin.all_zones') }}</option>
                        @foreach ($zones as $z)<option value="{{ $z->id }}" @selected(($filters['zone_id'] ?? '') == $z->id)>{{ $z->name }}</option>@endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.vehicle_categories') }}</label>
                    <select name="vehicle_category_id" class="{{ $fieldClass }}">
                        <option value="">{{ __('admin.all_vehicles') }}</option>
                        @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(($filters['vehicle_category_id'] ?? '') == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.online') }}</label>
                    <select name="online" class="{{ $fieldClass }}">
                        <option value="">{{ __('admin.all_online') }}</option>
                        <option value="online" @selected(($filters['online'] ?? '') === 'online')>{{ __('admin.online') }}</option>
                        <option value="offline" @selected(($filters['online'] ?? '') === 'offline')>{{ __('admin.offline') }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('admin.from') }}</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="{{ $fieldClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('admin.to') }}</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="{{ $fieldClass }}">
                    </div>
                </div>
            </x-admin.filter-drawer>

            @if (adminCan('drivers', 'write'))
                <a href="{{ route('admin.drivers.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('admin.add_driver') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Stats row — each card filters the table below it. --}}
    @php($activeStat = match (true) {
        request()->input('online') === 'online' => 'online',
        request()->input('status') === 'pending' => 'pending',
        request()->input('status') === 'suspended' => 'suspended',
        request()->input('status') === 'blocked' => 'blocked',
        ! request()->hasAny(['status', 'online', 'search', 'zone_id', 'vehicle_category_id', 'from', 'to']) => 'total',
        default => null,
    })

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-5">
        @foreach ([
            ['key' => 'total',     'label' => __('admin.total'),     'value' => $stats['total'],     'color' => 'text-gray-900 dark:text-gray-100',      'ring' => 'ring-gray-300 dark:ring-gray-500',    'params' => []],
            ['key' => 'online',    'label' => __('admin.online'),    'value' => $stats['online'],    'color' => 'text-green-600 dark:text-green-400',    'ring' => 'ring-green-400',   'params' => ['online' => 'online']],
            ['key' => 'pending',   'label' => __('admin.pending'),   'value' => $stats['pending'],   'color' => 'text-yellow-600 dark:text-yellow-400',  'ring' => 'ring-yellow-400',  'params' => ['status' => 'pending']],
            ['key' => 'suspended', 'label' => __('admin.suspended'), 'value' => $stats['suspended'], 'color' => 'text-orange-600 dark:text-orange-400',  'ring' => 'ring-orange-400',  'params' => ['status' => 'suspended']],
            ['key' => 'blocked',   'label' => __('admin.blocked'),   'value' => $stats['blocked'],   'color' => 'text-red-600 dark:text-red-400',        'ring' => 'ring-red-400',     'params' => ['status' => 'blocked']],
        ] as $card)
            @php($isActive = $activeStat === $card['key'])
            <a href="{{ route('admin.drivers.index', $card['params']) }}"
               class="dash-card block rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 {{ $isActive ? 'ring-2 ' . $card['ring'] : '' }}">
                <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ $card['label'] }}
                    @if ($isActive)
                        <svg class="h-3 w-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    @endif
                </p>
                <p class="mt-1 text-2xl font-bold {{ $card['color'] }}">{{ $card['value'] }}</p>
            </a>
        @endforeach
    </div>

    {{-- Table. Row selection was removed, so the bulk-action bar went with it;
         status changes are done per driver from the driver detail screen. --}}
    <div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            @if ($drivers->count())
                <div class="overflow-x-auto">
                    <table class="rr-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th data-col="id" class="{{ $colHide('id') }}px-4 py-3.5 text-start">{{ __('admin.id') }}</th>
                                <th data-col="driver" class="{{ $colHide('driver') }}px-4 py-3.5 text-start">{{ __('admin.driver') }}</th>
                                <th data-col="phone" class="{{ $colHide('phone') }}px-4 py-3.5 text-start">{{ __('admin.phone') }}</th>
                                <th data-col="vehicle" class="{{ $colHide('vehicle') }}px-4 py-3.5 text-start">{{ __('admin.vehicle') }}</th>
                                <th data-col="zone" class="{{ $colHide('zone') }}px-4 py-3.5 text-start">{{ __('admin.zone') }}</th>
                                <th data-col="status" class="{{ $colHide('status') }}px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                                <th data-col="rating" class="{{ $colHide('rating') }}px-4 py-3.5 text-center">{{ __('admin.rating') }}</th>
                                <th data-col="trips" class="{{ $colHide('trips') }}px-4 py-3.5 text-center">{{ __('admin.trips') }}</th>
                                <th data-col="due" class="{{ $colHide('due') }}px-4 py-3.5 text-end">{{ __('admin.due') }}</th>
                                <th data-col="joined" class="{{ $colHide('joined') }}px-4 py-3.5 text-start">{{ __('admin.joined') }}</th>
                                <th data-col="actions" class="{{ $colHide('actions') }}px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @foreach ($drivers as $driver)
                                @php($vehicleCat = optional($driver->vehicles->first())->vehicleCategory)
                                @php($rating = (float) $driver->average_rating)
                                <tr class="rr-row">
                                    <td data-col="id" class="{{ $colHide('id') }}px-4 py-3">
                                        <span class="rounded-md bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-gray-500 dark:bg-gray-700/60 dark:text-gray-400">#{{ $driver->id }}</span>
                                    </td>

                                    <td data-col="driver" class="{{ $colHide('driver') }}px-4 py-3">
                                        <a href="{{ route('admin.drivers.show', $driver->id) }}" class="group/name flex items-center gap-2.5">
                                            <span class="relative shrink-0">
                                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 text-xs font-bold text-white shadow-sm">{{ strtoupper(substr($driver->name, 0, 1)) }}</span>
                                                <span class="absolute -bottom-0.5 -end-0.5 h-3 w-3 rounded-full border-2 border-white dark:border-gray-800 {{ $driver->is_online ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate font-semibold text-gray-800 transition-colors group-hover/name:text-indigo-600 dark:text-gray-100 dark:group-hover/name:text-indigo-400">{{ $driver->name }}</span>
                                                <span class="block text-[11px] {{ $driver->is_online ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">
                                                    {{ $driver->is_online ? __('admin.online') : __('admin.offline') }}
                                                </span>
                                            </span>
                                        </a>
                                    </td>

                                    <td data-col="phone" class="{{ $colHide('phone') }}px-4 py-3 font-mono text-[13px] text-gray-600 dark:text-gray-400">{{ $driver->phone }}</td>

                                    <td data-col="vehicle" class="{{ $colHide('vehicle') }}px-4 py-3">
                                        @if ($vehicleCat)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700 ring-1 ring-sky-100 dark:bg-sky-900/25 dark:text-sky-300 dark:ring-sky-900/50">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1h-4m4 0V8a1 1 0 011-1h2.6a1 1 0 01.7.3l3.4 3.4a1 1 0 01.3.7V16a1 1 0 01-1 1h-1"/></svg>
                                                {{ $vehicleCat->name }}
                                            </span>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">—</span>
                                        @endif
                                    </td>

                                    <td data-col="zone" class="{{ $colHide('zone') }}px-4 py-3">
                                        @if ($driver->zone)
                                            <span class="inline-flex items-center gap-1.5 text-[13px] text-gray-600 dark:text-gray-400">
                                                <svg class="h-3.5 w-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                {{ $driver->zone->name }}
                                            </span>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">—</span>
                                        @endif
                                    </td>

                                    <td data-col="status" class="{{ $colHide('status') }}px-4 py-3"><x-admin.driver-status-badge :status="$driver->status" /></td>

                                    <td data-col="rating" class="{{ $colHide('rating') }}px-4 py-3 text-center">
                                        @if ($rating > 0)
                                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[12px] font-bold text-amber-700 dark:bg-amber-900/25 dark:text-amber-400">
                                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.29 3.97a1 1 0 00.95.69h4.17c.97 0 1.37 1.24.59 1.81l-3.38 2.45a1 1 0 00-.36 1.12l1.29 3.97c.3.92-.76 1.69-1.54 1.12l-3.38-2.45a1 1 0 00-1.18 0l-3.38 2.45c-.78.57-1.84-.2-1.54-1.12l1.29-3.97a1 1 0 00-.36-1.12L1.03 9.4c-.78-.57-.38-1.81.59-1.81H5.8a1 1 0 00.95-.69l1.3-3.97z"/></svg>
                                                {{ number_format($rating, 1) }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                        @endif
                                    </td>

                                    <td data-col="trips" class="{{ $colHide('trips') }}px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-100">{{ $driver->total_trips }}</td>

                                    <td data-col="due" class="{{ $colHide('due') }}px-4 py-3 text-end">
                                        @if ($driver->due_amount > 0)
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-[12px] font-bold text-red-700 dark:bg-red-900/25 dark:text-red-400">{{ number_format($driver->due_amount, 0) }}</span>
                                        @else
                                            <span class="text-[13px] text-gray-400 dark:text-gray-500">0</span>
                                        @endif
                                    </td>

                                    <td data-col="joined" class="{{ $colHide('joined') }}px-4 py-3 text-[13px] text-gray-500 dark:text-gray-400">{{ $driver->created_at->format('d M Y') }}</td>

                                    <td data-col="actions" class="{{ $colHide('actions') }}px-4 py-3 text-end">
                                        <a href="{{ route('admin.drivers.show', $driver->id) }}"
                                           class="group/view inline-flex items-center gap-1 rounded-lg border border-gray-200 px-2.5 py-1.5 text-[11px] font-semibold text-gray-600 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-gray-600 dark:text-gray-300 dark:hover:border-indigo-700 dark:hover:bg-indigo-900/25 dark:hover:text-indigo-300">
                                            {{ __('admin.view') }}
                                            <svg class="h-3 w-3 transition-transform group-hover/view:translate-x-0.5 rtl:rotate-180 rtl:group-hover/view:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($drivers->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $drivers->links() }}</div>
                @endif
            @else
                <x-admin.empty-state :message="__('admin.no_drivers_found')" />
            @endif
        </div>
    </div>
@endsection
