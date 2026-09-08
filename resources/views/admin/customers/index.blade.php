@extends('layouts.admin')

@section('title', 'Customers')
@section('page_title', 'Customer Management')
@section('page_subtitle', __('admin.customers_subtitle'))

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    @php($fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/12 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100')
    @php($labelClass = 'mb-1.5 block text-[13px] font-semibold text-gray-700 dark:text-gray-300')

    @php($customerCols = [
        'id' => __('admin.id'),
        'customer' => __('admin.customer'),
        'phone' => __('admin.phone'),
        'email' => __('admin.email'),
        'wallet' => __('admin.wallet'),
        'trips' => __('admin.trips'),
        'parcels' => __('admin.parcels'),
        'referral' => __('admin.referral'),
        'status' => __('admin.status'),
        'joined' => __('admin.joined'),
        'actions' => __('admin.actions'),
    ])
    @php($hiddenCols = adminUser()?->hiddenColumns('customers') ?? [])
    @php($colHide = fn (string $k) => in_array($k, $hiddenCols, true) ? 'hidden ' : '')

    <div class="flex items-center justify-between mb-3 gap-3 flex-wrap">
        <div class="flex items-center gap-3">
            <x-admin.search-box :action="route('admin.customers.index')"
                                :value="$filters['search'] ?? ''"
                                :preserve="$filters"
                                placeholder="{{ __('admin.name_or_phone') }}" />
        </div>
        <div class="flex items-center gap-2">
            <x-admin.export-menu
                export-route="admin.customers.export"
                :import-route="adminCan('users', 'write') ? 'admin.customers.import' : null"
                template-route="admin.customers.import.template"
                :query="request()->query()" />

            <x-admin.column-picker table="customers" :columns="$customerCols" :hidden="$hiddenCols" />

            <x-admin.filter-drawer :action="route('admin.customers.index')" :reset-url="route('admin.customers.index')" :applied="$filters">
                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.search') }}</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('admin.name_or_phone') }}" class="{{ $fieldClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.status') }}</label>
                    <select name="status" class="{{ $fieldClass }}">
                        <option value="">{{ __('admin.all_statuses') }}</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('admin.active') }}</option>
                        <option value="blocked" @selected(($filters['status'] ?? '') === 'blocked')>{{ __('admin.blocked') }}</option>
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}">{{ __('admin.wallet') }}</label>
                    <select name="has_balance" class="{{ $fieldClass }}">
                        <option value="">{{ __('admin.any_wallet_balance') }}</option>
                        <option value="yes" @selected(($filters['has_balance'] ?? '') === 'yes')>{{ __('admin.has_balance') }}</option>
                        <option value="no" @selected(($filters['has_balance'] ?? '') === 'no')>{{ __('admin.no_balance') }}</option>
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

            @if (adminCan('users', 'write'))
                <a href="{{ route('admin.customers.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('admin.add_customer') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Stats row — each card filters the table below it. --}}
    @php($activeStat = match (true) {
        request()->input('status') === 'active' => 'active',
        request()->input('status') === 'blocked' => 'blocked',
        request()->input('has_balance') === 'yes' => 'wallet',
        ! request()->hasAny(['status', 'has_balance', 'search', 'from', 'to']) => 'total',
        default => null,
    })

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        @foreach ([
            ['key' => 'total',   'label' => __('admin.total'),        'value' => number_format($stats['total']),                                   'color' => 'text-gray-900 dark:text-gray-100',     'ring' => 'ring-gray-300 dark:ring-gray-500', 'params' => []],
            ['key' => 'active',  'label' => __('admin.active'),       'value' => number_format($stats['active']),                                  'color' => 'text-green-600 dark:text-green-400',   'ring' => 'ring-green-400',                   'params' => ['status' => 'active']],
            ['key' => 'blocked', 'label' => __('admin.blocked'),      'value' => number_format($stats['blocked']),                                 'color' => 'text-red-600 dark:text-red-400',      'ring' => 'ring-red-400',                     'params' => ['status' => 'blocked']],
            ['key' => 'wallet',  'label' => __('admin.total_wallet'), 'value' => number_format($stats['wallet_total'], 0) . ' ' . $currency,       'color' => 'text-indigo-600 dark:text-indigo-400','ring' => 'ring-indigo-400',                  'params' => ['has_balance' => 'yes']],
        ] as $card)
            @php($isActive = $activeStat === $card['key'])
            <a href="{{ route('admin.customers.index', $card['params']) }}"
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
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($customers->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th data-col="id" class="{{ $colHide('id') }}px-4 py-3.5 text-start">{{ __('admin.id') }}</th>
                            <th data-col="customer" class="{{ $colHide('customer') }}px-4 py-3.5 text-start">{{ __('admin.customer') }}</th>
                            <th data-col="phone" class="{{ $colHide('phone') }}px-4 py-3.5 text-start">{{ __('admin.phone') }}</th>
                            <th data-col="email" class="{{ $colHide('email') }}px-4 py-3.5 text-start">{{ __('admin.email') }}</th>
                            <th data-col="wallet" class="{{ $colHide('wallet') }}px-4 py-3.5 text-end">{{ __('admin.wallet') }}</th>
                            <th data-col="trips" class="{{ $colHide('trips') }}px-4 py-3.5 text-center">{{ __('admin.trips') }}</th>
                            <th data-col="parcels" class="{{ $colHide('parcels') }}px-4 py-3.5 text-center">{{ __('admin.parcels') }}</th>
                            <th data-col="referral" class="{{ $colHide('referral') }}px-4 py-3.5 text-start">{{ __('admin.referral') }}</th>
                            <th data-col="status" class="{{ $colHide('status') }}px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th data-col="joined" class="{{ $colHide('joined') }}px-4 py-3.5 text-start">{{ __('admin.joined') }}</th>
                            <th data-col="actions" class="{{ $colHide('actions') }}px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($customers as $customer)
                            <tr class="rr-row">
                                <td data-col="id" class="{{ $colHide('id') }}px-4 py-3">
                                    <span class="rounded-md bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-gray-500 dark:bg-gray-700/60 dark:text-gray-400">#{{ $customer->id }}</span>
                                </td>

                                <td data-col="customer" class="{{ $colHide('customer') }}px-4 py-3">
                                    <a href="{{ route('admin.customers.show', $customer->id) }}" class="group/name flex items-center gap-2.5">
                                        <span class="relative shrink-0">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 text-xs font-bold text-white shadow-sm">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                            <span class="absolute -bottom-0.5 -end-0.5 h-3 w-3 rounded-full border-2 border-white dark:border-gray-800 {{ $customer->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate font-semibold text-gray-800 transition-colors group-hover/name:text-indigo-600 dark:text-gray-100 dark:group-hover/name:text-indigo-400">{{ $customer->name }}</span>
                                            <span class="block text-[11px] {{ $customer->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ $customer->is_active ? __('admin.active') : __('admin.blocked') }}
                                            </span>
                                        </span>
                                    </a>
                                </td>

                                <td data-col="phone" class="{{ $colHide('phone') }}px-4 py-3 font-mono text-[13px] text-gray-600 dark:text-gray-400">{{ maskPhone($customer->phone) }}</td>

                                <td data-col="email" class="{{ $colHide('email') }}px-4 py-3 text-[13px] text-gray-600 dark:text-gray-400">
                                    @if ($customer->email)
                                        <span class="block max-w-[190px] truncate">{{ $customer->email }}</span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endif
                                </td>

                                <td data-col="wallet" class="{{ $colHide('wallet') }}px-4 py-3 text-end">
                                    @if ($customer->wallet_balance > 0)
                                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[12px] font-bold text-emerald-700 dark:bg-emerald-900/25 dark:text-emerald-400">{{ number_format($customer->wallet_balance, 2) }}</span>
                                    @else
                                        <span class="text-[13px] text-gray-400 dark:text-gray-500">0.00</span>
                                    @endif
                                </td>

                                <td data-col="trips" class="{{ $colHide('trips') }}px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-100">{{ $customer->total_trips }}</td>

                                <td data-col="parcels" class="{{ $colHide('parcels') }}px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-100">{{ $customer->total_parcels }}</td>

                                <td data-col="referral" class="{{ $colHide('referral') }}px-4 py-3">
                                    @if ($customer->referral_code)
                                        <span class="rounded-md bg-violet-50 px-2 py-0.5 font-mono text-[11px] font-semibold text-violet-700 dark:bg-violet-900/25 dark:text-violet-300">{{ $customer->referral_code }}</span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endif
                                </td>

                                <td data-col="status" class="{{ $colHide('status') }}px-4 py-3">
                                    @if ($customer->is_active)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-900/25 dark:text-emerald-300 dark:ring-emerald-900/50">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ __('admin.active') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 ring-1 ring-red-100 dark:bg-red-900/25 dark:text-red-300 dark:ring-red-900/50">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>{{ __('admin.blocked') }}
                                        </span>
                                    @endif
                                </td>

                                <td data-col="joined" class="{{ $colHide('joined') }}px-4 py-3 text-[13px] text-gray-500 dark:text-gray-400">{{ $customer->created_at->format('d M Y') }}</td>

                                <td data-col="actions" class="{{ $colHide('actions') }}px-4 py-3 text-end">
                                    <a href="{{ route('admin.customers.show', $customer->id) }}"
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
            @if ($customers->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $customers->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_data') }}" />
        @endif
    </div>
@endsection
