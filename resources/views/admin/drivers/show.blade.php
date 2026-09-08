@extends('layouts.admin')

@section('title', $driver->name)
@section('page_title', 'Driver: ' . $driver->name)

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($vehicle = $driver->vehicles->first())
@php($duePct = $dueLimit > 0 ? min(100, round($driver->due_amount / $dueLimit * 100)) : 0)

@section('content')
    <div class="flex items-center justify-between mb-5">
        <a href="{{ route('admin.drivers.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 inline-flex items-center gap-1">
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.all_drivers') }}
        </a>
        @if (adminCan('drivers', 'write'))
            <a href="{{ route('admin.drivers.edit', $driver->id) }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.edit_profile') }}</a>
        @endif
    </div>

    <x-admin.tab-nav :current="$tab" :baseUrl="route('admin.drivers.show', $driver->id)" :tabs="[
        'profile' => __('admin.profile'),
        'documents' => __('admin.documents'),
        'vehicle' => __('admin.vehicle'),
        'trips' => __('admin.trips'),
        'wallet' => __('admin.wallet_and_earnings'),
        'performance' => __('admin.performance'),
        'notifications' => __('admin.notifications'),
    ]" />

    {{-- ========== PROFILE ========== --}}
    @if ($tab === 'profile')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 text-center">
                <div class="w-20 h-20 mx-auto rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl font-bold">{{ strtoupper(substr($driver->name,0,1)) }}</div>
                <h3 class="mt-3 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $driver->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $driver->phone }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $driver->email ?? '—' }}</p>
                <div class="mt-3"><x-admin.driver-status-badge :status="$driver->status" /></div>
                <p class="mt-2 text-xs {{ $driver->is_online ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-400' }}">● {{ $driver->is_online ? __('admin.online') : __('admin.offline') }}</p>
            </div>

            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    <div><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.total_trips') }}</p><p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $driver->total_trips }}</p></div>
                    <div><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.rating') }}</p><p class="text-xl font-bold text-gray-900 dark:text-gray-100">★ {{ number_format($driver->average_rating,1) }}</p></div>
                    <div><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.completion') }}</p><p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($driver->completion_rate,0) }}%</p></div>
                    <div><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.acceptance') }}</p><p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($driver->acceptance_rate,0) }}%</p></div>
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm border-t border-gray-100 dark:border-gray-700 pt-4">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.zone') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $driver->zone->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.vehicle') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ optional(optional($vehicle)->vehicleCategory)->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.joined') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $driver->created_at->format('d M Y') }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.last_online') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $driver->last_location_at?->diffForHumans() ?? '—' }}</dd></div>
                </dl>

                @if (adminCan('drivers', 'write'))
                    <div class="mt-6 border-t border-gray-100 dark:border-gray-700 pt-4" x-data="{ open: false }">
                        <button @click="open = !open" class="rounded-lg bg-gray-800 dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900 dark:hover:bg-gray-600">{{ __('admin.change_status') }}</button>
                        <form method="POST" action="{{ route('admin.drivers.change-status', $driver->id) }}" x-show="open" x-cloak class="mt-4 space-y-3 max-w-md">
                            @csrf
                            <select name="new_status" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                                <option value="approved">{{ __('admin.approve_reactivate') }}</option>
                                <option value="suspended">{{ __('admin.suspend_temporary') }}</option>
                                <option value="blocked">{{ __('admin.block_permanent') }}</option>
                                <option value="rejected">{{ __('admin.reject') }}</option>
                            </select>
                            <input type="number" name="duration_days" min="1" placeholder="{{ __('admin.suspend_duration_placeholder') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                            <textarea name="reason" rows="2" placeholder="{{ __('admin.reason_required_suspend_block') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm"></textarea>
                            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.update_status') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

    {{-- ========== DOCUMENTS ========== --}}
    @elseif ($tab === 'documents')
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            @if ($driver->documents->count())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($driver->documents as $doc)
                        <x-admin.document-card :document="$doc" :driverId="$driver->id" />
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-400 text-center py-8">{{ __('admin.no_documents_submitted') }}</p>
            @endif
        </div>

    {{-- ========== VEHICLE ========== --}}
    @elseif ($tab === 'vehicle')
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            @if ($vehicle)
                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm mb-4">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.make') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $vehicle->make }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.model') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $vehicle->model }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.year') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $vehicle->year }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.color') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $vehicle->color }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.registration_number') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $vehicle->registration_number }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.category') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $vehicle->vehicleCategory->name ?? '—' }}</dd></div>
                </dl>
                <div class="flex gap-3">
                    @if ($vehicle->front_photo)<a href="{{ Storage::url($vehicle->front_photo) }}" data-lightbox="vehicle"><img src="{{ Storage::url($vehicle->front_photo) }}" class="w-32 h-32 rounded-lg object-cover border border-gray-200 dark:border-gray-700"></a>@endif
                    @if ($vehicle->back_photo)<a href="{{ Storage::url($vehicle->back_photo) }}" data-lightbox="vehicle"><img src="{{ Storage::url($vehicle->back_photo) }}" class="w-32 h-32 rounded-lg object-cover border border-gray-200 dark:border-gray-700"></a>@endif
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-400 text-center py-8">{{ __('admin.no_vehicle_registered') }}</p>
            @endif
        </div>

    {{-- ========== TRIPS ========== --}}
    @elseif ($tab === 'trips')
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <form method="GET" class="flex flex-wrap items-center gap-2 p-4 border-b border-gray-100 dark:border-gray-700">
                <input type="hidden" name="tab" value="trips">
                <select name="trip_type" class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                    <option value="">{{ __('admin.all_types') }}</option>
                    <option value="ride" @selected(request('trip_type') === 'ride')>{{ __('admin.ride') }}</option>
                    <option value="parcel" @selected(request('trip_type') === 'parcel')>{{ __('admin.parcel') }}</option>
                </select>
                <select name="trip_status" class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                    <option value="">{{ __('admin.all_statuses') }}</option>
                    @foreach (['pending','accepted','completed','cancelled','rejected'] as $st)
                        <option value="{{ $st }}" @selected(request('trip_status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
                <input type="date" name="trip_from" value="{{ request('trip_from') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                <input type="date" name="trip_to" value="{{ request('trip_to') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.filter') }}</button>
            </form>
            @if ($trips->count())
                <div class="overflow-x-auto">
                    <table class="rr-table min-w-full text-sm">
                        <thead><tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.type') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.customer') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.route') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.amount') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @foreach ($trips as $t)
                                <tr class="rr-row">
                                    <td class="px-6 py-3 font-mono"><a href="{{ route('admin.orders.show', $t->id) }}" class="text-indigo-600 hover:text-indigo-800">{{ $t->order_number }}</a></td>
                                    <td class="px-6 py-3">{{ ucfirst($t->type) }}</td>
                                    <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $t->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-gray-700 dark:text-gray-100">{{ $t->user->name ?? '—' }}</td>
                                    <td class="px-6 py-3 text-gray-500 dark:text-gray-400 max-w-[180px] truncate">{{ \Illuminate\Support\Str::limit($t->pickup_address, 14) }} → {{ \Illuminate\Support\Str::limit($t->drop_address, 14) }}</td>
                                    <td class="px-6 py-3 text-end text-gray-700 dark:text-gray-100">{{ $currency }} {{ number_format($t->total_amount,0) }}</td>
                                    <td class="px-6 py-3"><x-admin.status-badge :status="$t->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $trips->links() }}</div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-400 text-center py-10">{{ __('admin.no_trips_found') }}</p>
            @endif
        </div>

    {{-- ========== WALLET ========== --}}
    @elseif ($tab === 'wallet')
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5"><p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.wallet_balance') }}</p><p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $currency }} {{ number_format($driver->wallet_balance,2) }}</p></div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5"><p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.due_amount') }}</p><p class="mt-1 text-2xl font-bold {{ $driver->due_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">{{ $currency }} {{ number_format($driver->due_amount,2) }}</p></div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.due_limit_usage') }}</p>
                <div class="mt-2 h-2.5 rounded-full bg-gray-100 dark:bg-gray-900/40 overflow-hidden"><div class="h-full bg-{{ $duePct >= 100 ? 'red' : 'amber' }}-500" style="width: {{ $duePct }}%"></div></div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($driver->due_amount,0) }} / {{ number_format($dueLimit,0) }}</p>
            </div>
        </div>

        @if (adminCan('payments', 'write'))
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
                <form method="POST" action="{{ route('admin.drivers.wallet-adjust', $driver->id) }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 space-y-3">
                    @csrf
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.manual_wallet_adjustment') }}</h4>
                    <div class="flex gap-3">
                        <select name="direction" class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm"><option value="add">{{ __('admin.add') }}</option><option value="deduct">{{ __('admin.deduct') }}</option></select>
                        <input name="amount" type="number" step="0.01" min="0.01" placeholder="{{ __('admin.amount') }}" required class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                    </div>
                    <input name="reason" type="text" placeholder="{{ __('admin.reason_required') }}" required class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.apply') }}</button>
                </form>

                <form method="POST" action="{{ route('admin.drivers.clear-due', $driver->id) }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 space-y-3" onsubmit="return confirm('Clear this driver\'s due?');">
                    @csrf
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.clear_due') }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.clear_due_help') }}</p>
                    <input name="reason" type="text" placeholder="{{ __('admin.reason_required') }}" required class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700" {{ $driver->due_amount <= 0 ? 'disabled' : '' }}>{{ __('admin.clear_due') }}</button>
                </form>

                <form method="POST" action="{{ route('admin.drivers.recharge', $driver->id) }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 space-y-3">
                    @csrf
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.recharge') }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.recharge_help') }}</p>
                    <input name="amount" type="number" step="0.01" min="0.01" placeholder="{{ __('admin.amount') }}" required class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                    <input name="note" type="text" placeholder="{{ __('admin.note') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                    <button class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">{{ __('admin.recharge') }}</button>
                </form>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700"><h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.transaction_history') }}</h4></div>
            @if ($transactions->count())
                <div class="overflow-x-auto">
                    <table class="rr-table min-w-full text-sm">
                        <thead><tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th><th class="px-4 py-3.5 text-start">{{ __('admin.type') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.category') }}</th><th class="px-4 py-3.5 text-end">{{ __('admin.amount') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.balance_after') }}</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @foreach ($transactions as $tx)
                                <tr class="rr-row">
                                    <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $tx->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-6 py-3"><span class="{{ $tx->type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ ucfirst($tx->type) }}</span></td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ ucwords(str_replace('_',' ',$tx->category)) }}</td>
                                    <td class="px-6 py-3 text-end {{ $tx->type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $tx->type === 'credit' ? '+' : '-' }}{{ number_format($tx->amount,2) }}</td>
                                    <td class="px-6 py-3 text-end text-gray-700 dark:text-gray-100">{{ number_format($tx->balance_after,2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $transactions->links() }}</div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-400 text-center py-8">{{ __('admin.no_transactions') }}</p>
            @endif
        </div>

        @if ($withdrawals->count())
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mt-6">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700"><h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.withdrawal_history') }}</h4></div>
                <div class="overflow-x-auto">
                    <table class="rr-table min-w-full text-sm">
                        <thead><tr><th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th><th class="px-4 py-3.5 text-start">{{ __('admin.method') }}</th><th class="px-4 py-3.5 text-end">{{ __('admin.amount') }}</th><th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @foreach ($withdrawals as $wd)
                                <tr><td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $wd->created_at->format('d M Y') }}</td><td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ ucfirst($wd->method) }}</td><td class="px-6 py-3 text-end text-gray-700 dark:text-gray-100">{{ number_format($wd->amount,2) }}</td><td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ ucfirst($wd->status) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    {{-- ========== PERFORMANCE ========== --}}
    @elseif ($tab === 'performance')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.rating_breakdown') }}</h4>
                @for ($star = 5; $star >= 1; $star--)
                    @php($cnt = $ratingBreakdown[$star] ?? 0)
                    @php($totalRatings = array_sum($ratingBreakdown) ?: 1)
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-8">{{ $star }}★</span>
                        <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-900/40 overflow-hidden"><div class="h-full bg-amber-400" style="width: {{ round($cnt/$totalRatings*100) }}%"></div></div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-8 text-end">{{ $cnt }}</span>
                    </div>
                @endfor
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 space-y-4">
                <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.rates') }}</h4>
                @foreach (['Acceptance' => $driver->acceptance_rate, 'Completion' => $driver->completion_rate, 'Cancellation' => $driver->cancellation_rate] as $label => $rate)
                    <div>
                        <div class="flex justify-between text-sm mb-1"><span class="text-gray-600 dark:text-gray-400">{{ $label }}</span><span class="font-medium text-gray-800 dark:text-gray-100">{{ number_format($rate,0) }}%</span></div>
                        <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-900/40 overflow-hidden"><div class="h-full {{ $label === 'Cancellation' ? 'bg-red-400' : 'bg-green-500' }}" style="width: {{ min(100,(float)$rate) }}%"></div></div>
                    </div>
                @endforeach
            </div>

            {{-- Online Hours --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.online_hours') }}</h4>
                <div class="grid grid-cols-3 gap-4">
                    @foreach (['Today' => $onlineHours['today'], 'This Week' => $onlineHours['week'], 'This Month' => $onlineHours['month']] as $label => $hrs)
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 p-4 text-center">
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $hrs }}<span class="text-sm font-medium text-gray-400 dark:text-gray-400"> h</span></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700"><h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.shift_history_last_10') }}</h4></div>
                @if ($shifts->count())
                    <table class="rr-table min-w-full text-sm">
                        <thead><tr><th class="px-4 py-3.5 text-start">{{ __('admin.online_at') }}</th><th class="px-4 py-3.5 text-start">{{ __('admin.offline_at') }}</th><th class="px-4 py-3.5 text-end">{{ __('admin.minutes') }}</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @foreach ($shifts as $sh)
                                <tr><td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $sh->went_online_at?->format('d M H:i') }}</td><td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $sh->went_offline_at?->format('d M H:i') ?? '—' }}</td><td class="px-6 py-3 text-end text-gray-700 dark:text-gray-100">{{ $sh->total_minutes ?? '—' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-400 text-center py-8">{{ __('admin.no_shift_history') }}</p>
                @endif
            </div>
        </div>

    {{-- ========== NOTIFICATIONS ========== --}}
    @elseif ($tab === 'notifications')
        @if (adminCan('drivers', 'write'))
            <form method="POST" action="{{ route('admin.drivers.notify', $driver->id) }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 mb-6 space-y-3">
                @csrf
                <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.send_notification') }}</h4>
                <input name="title" type="text" placeholder="{{ __('admin.title') }}" required class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                <textarea name="body" rows="2" placeholder="{{ __('admin.message') }}" required class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm"></textarea>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.send') }}</button>
            </form>
        @endif
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($notifications as $n)
                <div class="px-6 py-3">
                    <div class="flex items-center justify-between"><p class="font-medium text-gray-800 dark:text-gray-100 text-sm">{{ $n->title }}</p><span class="text-xs text-gray-400 dark:text-gray-400">{{ $n->created_at->diffForHumans() }}</span></div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $n->body }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-400 dark:text-gray-400 text-center py-8">{{ __('admin.no_notifications_sent') }}</p>
            @endforelse
        </div>
    @endif
@endsection

@push('scripts')
<link href="https://cdnjs.cloudflare.com/ajax/libs/SimpleLightbox/2.14.2/simple-lightbox.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/SimpleLightbox/2.14.2/simple-lightbox.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.SimpleLightbox) new SimpleLightbox('a[data-lightbox]', {});
    });
</script>
@endpush
