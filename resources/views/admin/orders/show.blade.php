@extends('layouts.admin')

@section('title', $order->order_number)
@section('page_title', 'Order ' . $order->order_number)

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($canWrite = adminCan('orders', 'write'))
@php($isSuper = adminUser()?->isSuperAdmin())
@php($vehicle = $order->driver?->vehicles?->firstWhere('is_active', true) ?? $order->driver?->vehicles?->first())
@php($custRating = $order->ratings->firstWhere('ratee_type', 'driver'))
@php($drvRating = $order->ratings->firstWhere('ratee_type', 'user'))

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 inline-flex items-center gap-1">
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.back') }}
        </a>
    </div>

    {{-- Section 1: Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 mb-6"
         x-data="{ modal: null }">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $order->order_number }}</h2>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-0.5 text-xs">{{ $order->type === 'parcel' ? '📦' : '🚗' }} {{ ucfirst($order->type) }}</span>
                    <x-admin.order-status-badge :status="$order->status" />
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.created') }}: {{ $order->created_at->format('d M Y, H:i') }} · {{ __('admin.updated') }}: {{ $order->updated_at->format('d M Y, H:i') }}</p>
            </div>
            @if ($canWrite)
                <div class="flex flex-wrap gap-2">
                    @if ($order->isCancellable())
                        <button @click="modal = 'cancel'" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('admin.cancel_order') }}</button>
                    @endif
                    @if ($order->isActive() || $order->status === 'no_driver_found')
                        <button @click="modal = 'reassign'" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700">{{ $order->driver_id ? __('admin.reassign_driver') : __('admin.assign_driver') }}</button>
                    @endif
                    @if ($isSuper && ! in_array($order->status, ['completed','cancelled','rejected']))
                        <button @click="modal = 'force'" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-100">{{ __('admin.force_complete') }}</button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Cancel modal --}}
        <div x-show="modal === 'cancel'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-md p-6" @click.outside="modal = null">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.cancel_order') }}</h3>
                <form method="POST" action="{{ route('admin.orders.cancel', $order->id) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="reason" rows="3" required placeholder="{{ __('admin.cancellation_reason') }}" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm"></textarea>
                    @if (in_array($order->payment_method, ['online','wallet']) && $order->payment_status === 'paid')
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-100"><input type="checkbox" name="refund" value="1" class="rounded text-indigo-600"> {{ __('admin.refund_customer') }}</label>
                        <input type="number" step="0.01" min="0" name="refund_amount" value="{{ $order->total_amount }}" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm" placeholder="{{ __('admin.refund_amount') }}">
                    @endif
                    @if ($order->is_cod)
                        <p class="text-xs text-amber-600 dark:text-amber-400">{{ __('admin.cod_reconcile_notice') }}</p>
                    @endif
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="modal = null" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.close') }}</button>
                        <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('admin.confirm_cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Reassign modal --}}
        <div x-show="modal === 'reassign'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-md p-6" @click.outside="modal = null">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.reassign_driver') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.current') }}: {{ $order->driver?->name ?? __('admin.none') }}</p>
                <form method="POST" action="{{ route('admin.orders.reassign', $order->id) }}" class="mt-4 space-y-3">
                    @csrf
                    <input list="driver-candidates" name="driver_id_label" placeholder="{{ __('admin.search_online_driver') }}" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm"
                           x-data x-on:input="$refs.did.value = ($el.value.match(/#(\d+)/)||[])[1] || ''">
                    <datalist id="driver-candidates">
                        @foreach ($candidates as $c)
                            <option value="{{ $c->name }} ({{ $c->phone }}) #{{ $c->id }}">{{ $c->is_online ? '● '.__('admin.online') : '○ '.__('admin.offline') }}</option>
                        @endforeach
                    </datalist>
                    <input type="hidden" name="driver_id" x-ref="did">
                    <textarea name="reason" rows="2" placeholder="{{ __('admin.reason_optional') }}" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm"></textarea>
                    @if ($candidates->isEmpty())
                        <p class="text-xs text-amber-600 dark:text-amber-400">{{ __('admin.no_online_drivers') }}{{ $order->driver?->zone_id ? ' ' . __('admin.in_this_zone') : '' }}.</p>
                    @endif
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="modal = null" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.close') }}</button>
                        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.reassign') }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Force complete modal --}}
        <div x-show="modal === 'force'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-md p-6" @click.outside="modal = null">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.force_complete') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.force_complete_desc') }}</p>
                <form method="POST" action="{{ route('admin.orders.force-complete', $order->id) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="reason" rows="3" required placeholder="{{ __('admin.reason') }}" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="modal = null" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.close') }}</button>
                        <button class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">{{ __('admin.force_complete') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Section 2: People --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase">{{ __('admin.customer') }}</p>
                    @if ($order->user)
                        <div class="mt-3 flex items-center gap-3">
                            <span class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-semibold">{{ strtoupper(substr($order->user->name,0,1)) }}</span>
                            <div>
                                <a href="{{ route('admin.customers.show', $order->user->id) }}" class="font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600">{{ $order->user->name }}</a>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->user->phone }}</p>
                            </div>
                        </div>
                    @else <p class="mt-3 text-sm text-gray-400 dark:text-gray-500">—</p> @endif
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase">{{ __('admin.driver') }}</p>
                    @if ($order->driver)
                        <div class="mt-3 flex items-center gap-3">
                            <span class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-semibold">{{ strtoupper(substr($order->driver->name,0,1)) }}</span>
                            <div>
                                <a href="{{ route('admin.drivers.show', $order->driver->id) }}" class="font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600">{{ $order->driver->name }}</a>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->driver->phone }}</p>
                                @if ($vehicle)<p class="text-xs text-gray-400 dark:text-gray-500">{{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->registration_number }})</p>@endif
                            </div>
                        </div>
                    @else <p class="mt-3 text-sm text-yellow-600 dark:text-yellow-400">{{ __('admin.searching_for_driver') }}</p> @endif
                </div>
            </div>

            {{-- Section 2b: Dispatch Log — who the order was offered to & what happened --}}
            @php($offeredCount = $order->dispatchLogs->where('event', 'offered')->count())
            @php($rejectedCount = $order->dispatchLogs->where('event', 'rejected')->count())
            @php($timeoutCount = $order->dispatchLogs->where('event', 'timeout')->count())
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ __('admin.dispatch_log') }}</h3>
                    @if ($order->dispatchLogs->isNotEmpty())
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $offeredCount }} {{ __('admin.offered') }} · {{ $rejectedCount }} {{ __('admin.rejected') }} · {{ $timeoutCount }} {{ __('admin.no_response') }}
                        </p>
                    @endif
                </div>

                @if ($dispatchState)
                    <div class="mb-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 px-4 py-2.5 text-sm text-blue-800 dark:text-blue-200">
                        <span class="font-medium">{{ __('admin.dispatch_in_progress') }}:</span>
                        {{ __('admin.currently_offering_to') }} driver #{{ $dispatchState['current'] ?? '—' }}
                        ({{ __('admin.attempt') }} {{ $dispatchState['attempt'] ?? 0 }}, {{ $dispatchState['radius'] ?? '—' }} km)
                    </div>
                @endif

                @if ($order->dispatchLogs->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-dashed border-gray-200 dark:border-gray-700 p-5 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('admin.no_dispatch_yet') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-gray-400 dark:text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="py-2 pe-4 text-start font-medium">{{ __('admin.time') }}</th>
                                    <th class="py-2 pe-4 text-center font-medium">#</th>
                                    <th class="py-2 pe-4 text-start font-medium">{{ __('admin.event') }}</th>
                                    <th class="py-2 pe-4 text-start font-medium">{{ __('admin.driver') }}</th>
                                    <th class="py-2 text-end font-medium">{{ __('admin.radius') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                                @foreach ($order->dispatchLogs as $log)
                                    <tr>
                                        <td class="py-2 pe-4 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $log->created_at?->format('d M, H:i:s') }}</td>
                                        <td class="py-2 pe-4 text-center text-gray-500 dark:text-gray-400">{{ $log->attempt ?: '—' }}</td>
                                        <td class="py-2 pe-4">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $log->badgeClass() }}">
                                                {{ __('admin.dispatch_' . $log->event) }}
                                            </span>
                                        </td>
                                        <td class="py-2 pe-4 text-gray-700 dark:text-gray-200">
                                            @if ($log->driver)
                                                <a href="{{ route('admin.drivers.show', $log->driver_id) }}" class="hover:text-indigo-600">{{ $log->driver->name }}</a>
                                                <span class="text-xs text-gray-400 dark:text-gray-500">#{{ $log->driver_id }}</span>
                                            @else — @endif
                                        </td>
                                        <td class="py-2 text-end text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $log->radius_km ? $log->radius_km . ' km' : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Section 3: Locations & Map --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">{{ __('admin.locations') }}</h3>
                @if ($mapsKey)
                    {{-- JS map with colored markers: pickup (green), drop (red), driver (blue), stops --}}
                    <div id="orderMap" class="w-full rounded-lg border border-gray-100 dark:border-gray-700" style="height:400px"></div>
                    <div class="mt-2 flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> {{ __('admin.pickup') }}</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> {{ __('admin.drop') }}</span>
                        @if (in_array($order->status, \App\Models\Order::ONGOING_STATUSES) && $order->driver?->current_lat)
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> {{ __('admin.driver_live') }}</span>
                        @endif
                    </div>
                @else
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-dashed border-gray-200 dark:border-gray-700 p-6 text-center text-sm text-gray-400 dark:text-gray-500">
                        {{ __('admin.maps_key_not_configured') }}
                    </div>
                @endif
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex gap-2"><dt class="w-16 shrink-0 text-green-600 dark:text-green-400 font-medium">{{ __('admin.pickup') }}</dt><dd class="text-gray-700 dark:text-gray-100">{{ $order->pickup_address }}</dd></div>
                    <div class="flex gap-2"><dt class="w-16 shrink-0 text-red-600 dark:text-red-400 font-medium">{{ __('admin.drop') }}</dt><dd class="text-gray-700 dark:text-gray-100">{{ $order->drop_address }}</dd></div>
                    <div class="flex gap-4 pt-2 text-gray-500 dark:text-gray-400">
                        <span>{{ __('admin.distance') }}: <strong class="text-gray-700 dark:text-gray-100">{{ $order->distance_km ? number_format($order->distance_km, 1) . ' km' : '—' }}</strong></span>
                        <span>{{ __('admin.duration') }}: <strong class="text-gray-700 dark:text-gray-100">{{ $order->duration_minutes ? $order->duration_minutes . ' min' : '—' }}</strong></span>
                    </div>
                </dl>
            </div>

            {{-- Section 4: Parcel info --}}
            @if ($order->type === 'parcel')
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">{{ __('admin.parcel_info') }}</h3>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.sender') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $order->sender_name ?? '—' }} <span class="text-gray-400 dark:text-gray-500">{{ $order->sender_phone }}</span></dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.receiver') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $order->receiver_name ?? '—' }} <span class="text-gray-400 dark:text-gray-500">{{ $order->receiver_phone }}</span></dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.type') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100 capitalize">{{ $order->parcel_type ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.weight_size') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $order->parcel_weight ? $order->parcel_weight . ' kg' : '—' }} / {{ ucfirst($order->parcel_size ?? '—') }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.cod') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $order->is_cod ? __('admin.yes') . ' — ' . number_format($order->cod_amount, 2) . ' ' . $currency : __('admin.no') }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.payment_timing') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100 capitalize">{{ $order->payment_timing ?? '—' }}</dd></div>
                        <div class="col-span-2"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.note') }}</dt><dd class="text-gray-700 dark:text-gray-100">{{ $order->parcel_note ?? '—' }}</dd></div>
                        <div class="col-span-2"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.proof_of_delivery') }}</dt><dd class="text-gray-700 dark:text-gray-100">{{ $order->proof_collected_at ? ucfirst($order->proof_type) . ' ' . __('admin.collected') . ' ' . $order->proof_collected_at->format('d M, H:i') : __('admin.not_collected') }}</dd></div>
                    </dl>
                    @if ($order->parcel_photo)
                        <a href="{{ Storage::url($order->parcel_photo) }}" data-lightbox="parcel" class="mt-4 inline-block">
                            <img src="{{ Storage::url($order->parcel_photo) }}" class="w-28 h-28 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                        </a>
                    @endif
                </div>
            @endif

            {{-- Section 5: Fare breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ __('admin.fare_breakdown') }}</h3>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span class="capitalize">{{ $order->payment_method }}</span> ·
                        <span class="{{ $order->payment_status === 'paid' ? 'text-green-600 dark:text-green-400' : ($order->payment_status === 'failed' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400') }} font-medium capitalize">{{ $order->payment_status }}</span>
                    </div>
                </div>
                <x-admin.fare-breakdown :order="$order" :currency="$currency" />
                @if ($order->payment_intent_id)
                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">{{ __('admin.payment_intent') }}: {{ $order->payment_intent_id }}</p>
                @endif
            </div>

            {{-- Section 7: Ratings --}}
            @if ($custRating || $drvRating)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">{{ __('admin.ratings_reviews') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">{{ __('admin.customer') }} → {{ __('admin.driver') }}</p>
                            @if ($custRating)
                                <p class="text-amber-500 dark:text-amber-400">{{ str_repeat('★', $custRating->rating) }}{{ str_repeat('☆', 5 - $custRating->rating) }}</p>
                                <p class="text-gray-600 dark:text-gray-400">{{ $custRating->comment ?? '—' }}</p>
                            @else <p class="text-gray-400 dark:text-gray-500">{{ __('admin.no_rating') }}</p> @endif
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">{{ __('admin.driver') }} → {{ __('admin.customer') }}</p>
                            @if ($drvRating)
                                <p class="text-amber-500">{{ str_repeat('★', $drvRating->rating) }}{{ str_repeat('☆', 5 - $drvRating->rating) }}</p>
                                <p class="text-gray-600 dark:text-gray-400">{{ $drvRating->comment ?? '—' }}</p>
                            @else <p class="text-gray-400 dark:text-gray-500">{{ __('admin.no_rating') }}</p> @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Section 8: Dispute --}}
            @if ($order->dispute)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-red-100 dark:border-gray-700 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-red-700 dark:text-red-400">{{ __('admin.dispute') }}</h3>
                        @if (adminCan('disputes', 'read'))
                            <a href="{{ route('admin.disputes.show', $order->dispute->id) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('admin.view_full_dispute') }} →</a>
                        @endif
                    </div>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.status') }}</dt><dd><x-admin.status-badge :status="$order->dispute->status" /></dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.category') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ ucwords(str_replace('_',' ',$order->dispute->category)) }}</dd></div>
                        <div class="col-span-2"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.description') }}</dt><dd class="text-gray-700 dark:text-gray-100">{{ $order->dispute->description }}</dd></div>
                        @if ($order->dispute->admin_note)<div class="col-span-2"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.admin_note') }}</dt><dd class="text-gray-700 dark:text-gray-100">{{ $order->dispute->admin_note }}</dd></div>@endif
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.refund_issued') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $order->dispute->refund_issued ? __('admin.yes') . ' — ' . number_format($order->dispute->refund_amount, 2) : __('admin.no') }}</dd></div>
                    </dl>
                </div>
            @endif
        </div>

        {{-- Section 6: Timeline --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 h-fit">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-5">{{ __('admin.order_timeline') }}</h3>
            <x-admin.order-timeline :order="$order" />
        </div>
    </div>
@endsection

@push('scripts')
<link href="https://cdnjs.cloudflare.com/ajax/libs/SimpleLightbox/2.14.2/simple-lightbox.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/SimpleLightbox/2.14.2/simple-lightbox.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.SimpleLightbox) new SimpleLightbox('a[data-lightbox]', {});
    });
</script>

@if ($mapsKey)
<script>
    function initOrderMap() {
        const pickup = { lat: {{ (float) $order->pickup_lat }}, lng: {{ (float) $order->pickup_lng }} };
        const drop = { lat: {{ (float) $order->drop_lat }}, lng: {{ (float) $order->drop_lng }} };
        const map = new google.maps.Map(document.getElementById('orderMap'), { zoom: 12, center: pickup, mapTypeControl: false });
        const bounds = new google.maps.LatLngBounds();
        const pin = (pos, color, title) => { new google.maps.Marker({ position: pos, map, title, icon: 'http://maps.google.com/mapfiles/ms/icons/' + color + '-dot.png' }); bounds.extend(pos); };

        pin(pickup, 'green', 'Pickup');
        pin(drop, 'red', 'Drop');

        // Multi-stops (if any).
        @if (is_array($order->stops))
            @foreach ($order->stops as $i => $stop)
                @if (isset($stop['lat'], $stop['lng']))
                    pin({ lat: {{ (float) $stop['lat'] }}, lng: {{ (float) $stop['lng'] }} }, 'yellow', 'Stop {{ $i + 1 }}');
                @endif
            @endforeach
        @endif

        // Driver live location for ongoing orders.
        @if (in_array($order->status, \App\Models\Order::ONGOING_STATUSES) && $order->driver?->current_lat)
            pin({ lat: {{ (float) $order->driver->current_lat }}, lng: {{ (float) $order->driver->current_lng }} }, 'blue', 'Driver');
        @endif

        // Draw the pickup → drop route.
        new google.maps.DirectionsService().route(
            { origin: pickup, destination: drop, travelMode: 'DRIVING' },
            (res, status) => { if (status === 'OK') new google.maps.DirectionsRenderer({ map, suppressMarkers: true, polylineOptions: { strokeColor: '#6366f1' } }).setDirections(res); }
        );

        map.fitBounds(bounds);
    }
</script>
<script async src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initOrderMap"></script>
@endif
@endpush
