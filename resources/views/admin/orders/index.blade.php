@extends('layouts.admin')

@section('title', 'Orders')
@section('page_title', 'Order Management')
@section('page_subtitle', __('admin.orders_subtitle'))

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    @php($orderCols = [
        'order' => __('admin.order_number'),
        'type' => __('admin.type'),
        'customer' => __('admin.customer'),
        'driver' => __('admin.driver'),
        'route' => __('admin.route'),
        'amount' => __('admin.amount'),
        'payment' => __('admin.payment'),
        'status' => __('admin.status'),
        'created' => __('admin.created'),
        'actions' => __('admin.actions'),
    ])
    @php($hiddenCols = adminUser()?->hiddenColumns('orders') ?? [])
    @php($colHide = fn (string $k) => in_array($k, $hiddenCols, true) ? 'hidden ' : '')

    @php($fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/12 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100')
    @php($labelClass = 'mb-1.5 block text-[13px] font-semibold text-gray-700 dark:text-gray-300')

    <div class="flex items-center justify-between mb-3 gap-3 flex-wrap">
        <div class="flex items-center gap-3">
            <x-admin.search-box :action="route('admin.orders.index')"
                                :value="$filters['search'] ?? ''"
                                :preserve="$filters"
                                placeholder="{{ __('admin.order_search_placeholder') }}" />
        </div>
        <div class="flex items-center gap-2">
            <x-admin.export-menu
                export-route="admin.orders.export"
                :query="request()->query()" />

            <x-admin.column-picker table="orders" :columns="$orderCols" :hidden="$hiddenCols" />

            <x-admin.filter-drawer :action="route('admin.orders.index')" :reset-url="route('admin.orders.index')" :applied="$filters">
                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.search') }}</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('admin.order_search_placeholder') }}" class="{{ $fieldClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.type') }}</label>
                    <select name="type" class="{{ $fieldClass }}">
                        <option value="">{{ __('admin.all_types') }}</option>
                        <option value="ride" @selected(($filters['type'] ?? '') === 'ride')>{{ __('admin.ride') }}</option>
                        <option value="parcel" @selected(($filters['type'] ?? '') === 'parcel')>{{ __('admin.parcel') }}</option>
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.status') }}</label>
                    <select name="status" class="{{ $fieldClass }}">
                        <option value="">{{ __('admin.all_statuses') }}</option>
                        @foreach (['pending','accepted','ongoing','completed','cancelled','rejected','scheduled'] as $st)
                            <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('admin.payment') }}</label>
                        <select name="payment_method" class="{{ $fieldClass }}">
                            <option value="">{{ __('admin.all_payments') }}</option>
                            @foreach (['cash','online','wallet','cod'] as $pm)
                                <option value="{{ $pm }}" @selected(($filters['payment_method'] ?? '') === $pm)>{{ ucfirst($pm) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('admin.payment_status') }}</label>
                        <select name="payment_status" class="{{ $fieldClass }}">
                            <option value="">{{ __('admin.all_pay_status') }}</option>
                            @foreach (['paid','pending','failed'] as $ps)
                                <option value="{{ $ps }}" @selected(($filters['payment_status'] ?? '') === $ps)>{{ ucfirst($ps) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.zones') }}</label>
                    <select name="zone_id" class="{{ $fieldClass }}">
                        <option value="">{{ __('admin.all_zones') }}</option>
                        @foreach ($zones as $z)<option value="{{ $z->id }}" @selected(($filters['zone_id'] ?? '') == $z->id)>{{ $z->name }}</option>@endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">COD</label>
                        <select name="is_cod" class="{{ $fieldClass }}">
                            <option value="">{{ __('admin.cod_any') }}</option>
                            <option value="yes" @selected(($filters['is_cod'] ?? '') === 'yes')>{{ __('admin.cod_only') }}</option>
                            <option value="no" @selected(($filters['is_cod'] ?? '') === 'no')>{{ __('admin.non_cod') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('admin.scheduled') }}</label>
                        <select name="is_scheduled" class="{{ $fieldClass }}">
                            <option value="">{{ __('admin.scheduled_any') }}</option>
                            <option value="yes" @selected(($filters['is_scheduled'] ?? '') === 'yes')>{{ __('admin.scheduled') }}</option>
                            <option value="no" @selected(($filters['is_scheduled'] ?? '') === 'no')>{{ __('admin.instant') }}</option>
                        </select>
                    </div>
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
        </div>
    </div>

    {{-- Stats row — each card filters the table below it. --}}
    @php($activeStat = match (true) {
        request()->input('status') === 'completed' => 'completed',
        request()->input('status') === 'cancelled' => 'cancelled',
        request()->input('payment_status') === 'paid' => 'revenue',
        ! request()->hasAny(['status', 'payment_status', 'search', 'type', 'payment_method', 'zone_id', 'is_cod', 'is_scheduled', 'from', 'to']) => 'today',
        default => null,
    })

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        @foreach ([
            ['key' => 'today',     'label' => __('admin.today'),     'value' => number_format($stats['today']),                                'color' => 'text-gray-900 dark:text-gray-100',      'ring' => 'ring-gray-300 dark:ring-gray-500', 'params' => []],
            ['key' => 'completed', 'label' => __('admin.completed'), 'value' => number_format($stats['completed']),                            'color' => 'text-green-600 dark:text-green-400',    'ring' => 'ring-green-400',                   'params' => ['status' => 'completed']],
            ['key' => 'cancelled', 'label' => __('admin.cancelled'), 'value' => number_format($stats['cancelled']),                            'color' => 'text-red-600 dark:text-red-400',       'ring' => 'ring-red-400',                     'params' => ['status' => 'cancelled']],
            ['key' => 'revenue',   'label' => __('admin.revenue'),   'value' => number_format($stats['revenue'], 0) . ' ' . $currency,         'color' => 'text-indigo-600 dark:text-indigo-400', 'ring' => 'ring-indigo-400',                  'params' => ['payment_status' => 'paid']],
        ] as $card)
            @php($isActive = $activeStat === $card['key'])
            <a href="{{ route('admin.orders.index', $card['params']) }}"
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

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden"
         x-data="{ cancelOpen: false, cancelId: null, cancelNum: '' }">
        @if ($orders->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th data-col="order" class="{{ $colHide('order') }}px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                            <th data-col="type" class="{{ $colHide('type') }}px-4 py-3.5 text-start">{{ __('admin.type') }}</th>
                            <th data-col="customer" class="{{ $colHide('customer') }}px-4 py-3.5 text-start">{{ __('admin.customer') }}</th>
                            <th data-col="driver" class="{{ $colHide('driver') }}px-4 py-3.5 text-start">{{ __('admin.driver') }}</th>
                            <th data-col="route" class="{{ $colHide('route') }}px-4 py-3.5 text-start">{{ __('admin.route') }}</th>
                            <th data-col="amount" class="{{ $colHide('amount') }}px-4 py-3.5 text-end">{{ __('admin.amount') }}</th>
                            <th data-col="payment" class="{{ $colHide('payment') }}px-4 py-3.5 text-start">{{ __('admin.payment') }}</th>
                            <th data-col="status" class="{{ $colHide('status') }}px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th data-col="created" class="{{ $colHide('created') }}px-4 py-3.5 text-start">{{ __('admin.created') }}</th>
                            <th data-col="actions" class="{{ $colHide('actions') }}px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($orders as $order)
                            @php($isParcel = $order->type === 'parcel')
                            <tr class="rr-row">
                                <td data-col="order" class="{{ $colHide('order') }}px-4 py-3">
                                    <a href="{{ route('admin.orders.show', $order->id) }}"
                                       class="inline-block whitespace-nowrap rounded-md bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-gray-600 transition hover:bg-indigo-50 hover:text-indigo-700 dark:bg-gray-700/60 dark:text-gray-300 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-300">
                                        {{ $order->order_number }}
                                    </a>
                                </td>

                                <td data-col="type" class="{{ $colHide('type') }}px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1
                                        {{ $isParcel
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-900/25 dark:text-emerald-300 dark:ring-emerald-900/50'
                                            : 'bg-sky-50 text-sky-700 ring-sky-100 dark:bg-sky-900/25 dark:text-sky-300 dark:ring-sky-900/50' }}">
                                        @if ($isParcel)
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        @else
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1h-4m4 0V8a1 1 0 011-1h2.6a1 1 0 01.7.3l3.4 3.4a1 1 0 01.3.7V16a1 1 0 01-1 1h-1"/></svg>
                                        @endif
                                        {{ ucfirst($order->type) }}
                                    </span>
                                    @if ($order->is_cod)
                                        <span class="ms-1 inline-flex rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/25 dark:text-amber-400">COD</span>
                                    @endif
                                </td>

                                <td data-col="customer" class="{{ $colHide('customer') }}px-4 py-3">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $order->user->name ?? '—' }}</p>
                                    <p class="font-mono text-[11px] text-gray-400 dark:text-gray-500">{{ maskPhone($order->user->phone ?? null) }}</p>
                                </td>

                                <td data-col="driver" class="{{ $colHide('driver') }}px-4 py-3">
                                    @if ($order->driver)
                                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $order->driver->name }}</p>
                                        <p class="font-mono text-[11px] text-gray-400 dark:text-gray-500">{{ maskPhone($order->driver->phone) }}</p>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/25 dark:text-amber-400">
                                            <span class="relative flex h-1.5 w-1.5">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            </span>
                                            {{ __('admin.searching') }}
                                        </span>
                                    @endif
                                </td>

                                <td data-col="route" class="{{ $colHide('route') }}px-4 py-3">
                                    <div class="flex max-w-[210px] items-center gap-1.5 text-[12px] text-gray-600 dark:text-gray-400">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                                        <span class="truncate">{{ \Illuminate\Support\Str::limit($order->pickup_address, 16) }}</span>
                                        <svg class="h-3 w-3 shrink-0 text-gray-300 rtl:rotate-180 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7"/></svg>
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500"></span>
                                        <span class="truncate">{{ \Illuminate\Support\Str::limit($order->drop_address, 16) }}</span>
                                    </div>
                                </td>

                                <td data-col="amount" class="{{ $colHide('amount') }}px-4 py-3 text-end font-bold text-gray-800 dark:text-gray-100">{{ number_format($order->total_amount, 2) }}</td>

                                <td data-col="payment" class="{{ $colHide('payment') }}px-4 py-3">
                                    <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-semibold capitalize text-gray-600 dark:bg-gray-700/60 dark:text-gray-300">{{ $order->payment_method }}</span>
                                    <span class="mt-1 block text-[10px] font-semibold {{ $order->payment_status === 'paid' ? 'text-emerald-600 dark:text-emerald-400' : ($order->payment_status === 'failed' ? 'text-red-600 dark:text-red-400' : 'text-gray-400 dark:text-gray-500') }}">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                </td>

                                <td data-col="status" class="{{ $colHide('status') }}px-4 py-3"><x-admin.order-status-badge :status="$order->status" /></td>

                                <td data-col="created" class="{{ $colHide('created') }}px-4 py-3 text-[13px] text-gray-500 dark:text-gray-400">{{ $order->created_at->format('d M, H:i') }}</td>

                                <td data-col="actions" class="{{ $colHide('actions') }}px-4 py-3 text-end">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.orders.show', $order->id) }}"
                                           class="group/view inline-flex items-center gap-1 rounded-lg border border-gray-200 px-2.5 py-1.5 text-[11px] font-semibold text-gray-600 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-gray-600 dark:text-gray-300 dark:hover:border-indigo-700 dark:hover:bg-indigo-900/25 dark:hover:text-indigo-300">
                                            {{ __('admin.view') }}
                                            <svg class="h-3 w-3 transition-transform group-hover/view:translate-x-0.5 rtl:rotate-180 rtl:group-hover/view:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                        @if (adminCan('orders', 'write') && $order->isCancellable())
                                            <button type="button" @click="cancelId = {{ $order->id }}; cancelNum = '{{ $order->order_number }}'; cancelOpen = true"
                                                    class="rounded-lg border border-transparent px-2 py-1.5 text-[11px] font-semibold text-red-600 transition hover:border-red-200 hover:bg-red-50 dark:text-red-400 dark:hover:border-red-800 dark:hover:bg-red-900/25">
                                                {{ __('admin.cancel') }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Shared inline-cancel modal (action URL built from the selected order id) --}}
            @if (adminCan('orders', 'write'))
                <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-md p-6" @click.outside="cancelOpen = false">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.cancel_order') }} <span x-text="cancelNum"></span></h3>
                        <form method="POST" :action="`{{ url('admin/orders') }}/${cancelId}/cancel`" class="mt-4 space-y-3">
                            @csrf
                            <textarea name="reason" rows="3" required placeholder="{{ __('admin.cancellation_reason') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm"></textarea>
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-400"><input type="checkbox" name="refund" value="1" class="rounded text-indigo-600"> {{ __('admin.refund_customer_if_paid') }}</label>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="cancelOpen = false" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.close') }}</button>
                                <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('admin.confirm_cancel') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
            @if ($orders->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $orders->links() }}</div>
            @endif
        @else
            <x-admin.empty-state :message="__('admin.no_data')" />
        @endif
    </div>
@endsection
