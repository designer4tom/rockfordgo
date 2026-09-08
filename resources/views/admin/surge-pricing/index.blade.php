@extends('layouts.admin')

@section('title', __('admin.surge_pricing'))
@section('page_title', __('admin.surge_pricing'))
@section('page_subtitle', __('admin.surge_pricing_subtitle'))

@php($dayLabels = [6 => __('admin.sat'), 0 => __('admin.sun'), 1 => __('admin.mon'), 2 => __('admin.tue'), 3 => __('admin.wed'), 4 => __('admin.thu'), 5 => __('admin.fri')])

@section('content')
    {{-- Master toggle --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 mb-6 flex items-center justify-between">
        <div>
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.surge_system') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('admin.surge_off_rules_inactive') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm font-medium {{ $surgeEnabled ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $surgeEnabled ? __('admin.enabled') : __('admin.disabled') }}</span>
            @if (adminCan('settings', 'write'))
                <x-admin.toggle :route="route('admin.surge-pricing.toggle-master')" :checked="$surgeEnabled" />
            @endif
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.scheduled_surge_rules') }}</p>
        @if (adminCan('settings', 'write'))
            <a href="{{ route('admin.surge-pricing.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.add_new_rule') }}
            </a>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($rules->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.name') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.zone') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.vehicle') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.days') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.time') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.multiplier') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($rules as $rule)
                            <tr class="rr-row">
                                <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-100">
                                    {{ $rule->name }}
                                    @if ($rule->is_manual)
                                        <span class="ms-1 inline-flex rounded-full bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 px-2 py-0.5 text-[10px] font-medium align-middle">{{ __('admin.manual') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $rule->zone->name ?? __('admin.all_zones') }}</td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $rule->vehicleCategory->name ?? __('admin.all') }}</td>
                                <td class="px-5 py-3">
                                    @if (empty($rule->day_of_week))
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('admin.everyday') }}</span>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($rule->day_of_week as $d)
                                                <span class="inline-flex rounded bg-gray-100 dark:bg-gray-900/40 text-gray-600 dark:text-gray-400 px-1.5 py-0.5 text-[10px]">{{ $dayLabels[(int)$d] ?? $d }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::substr($rule->start_time,0,5) }}–{{ \Illuminate\Support\Str::substr($rule->end_time,0,5) }}</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 px-2.5 py-0.5 text-xs font-semibold">{{ rtrim(rtrim(number_format($rule->multiplier,2),'0'),'.') }}x</span>
                                </td>
                                <td class="px-5 py-3">
                                    @if (adminCan('settings', 'write'))
                                        <x-admin.toggle :route="route('admin.surge-pricing.toggle-status', $rule->id)" :checked="$rule->is_active" />
                                    @else
                                        <span class="text-xs {{ $rule->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $rule->is_active ? __('admin.active') : __('admin.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        @if (adminCan('settings', 'write'))
                                            <a href="{{ route('admin.surge-pricing.edit', $rule->id) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('admin.edit') }}</a>
                                        @endif
                                        @if (adminCan('settings', 'delete'))
                                            <form method="POST" action="{{ route('admin.surge-pricing.destroy', $rule->id) }}" onsubmit="return confirm('{{ __('admin.delete_rule_confirm') }}');">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('admin.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($rules->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $rules->links() }}</div>
            @endif
        @else
            <x-admin.empty-state :message="__('admin.no_surge_rules')"
                :createRoute="adminCan('settings','write') ? route('admin.surge-pricing.create') : null"
                :createLabel="__('admin.new_rule')" />
        @endif
    </div>

    {{-- Emergency surge --}}
    @if (adminCan('settings', 'write'))
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 mt-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ __('admin.emergency_surge') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('admin.emergency_surge_help') }}</p>
            <form method="POST" action="{{ route('admin.surge-pricing.manual-activate') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.zone') }} <span class="text-red-500">*</span></label>
                    <select name="zone_id" required class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                        <option value="">{{ __('admin.select_zone') }}</option>
                        @foreach ($zones as $z)<option value="{{ $z->id }}">{{ $z->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.vehicle_category') }}</label>
                    <select name="vehicle_category_id" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                        <option value="">{{ __('admin.all') }}</option>
                        @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.multiplier') }} <span class="text-red-500">*</span></label>
                    <input name="multiplier" type="number" step="0.1" min="1.1" max="5.0" value="1.5" required
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.ends_at') }} <span class="text-red-500">*</span></label>
                    <input name="ends_at" type="datetime-local" required
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
                <div class="md:col-span-4">
                    <button class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700 transition">{{ __('admin.activate_now') }}</button>
                </div>
            </form>
        </div>
    @endif
@endsection
