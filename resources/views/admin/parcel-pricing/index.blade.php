@extends('layouts.admin')

@section('title', __('admin.parcel_pricing'))
@section('page_title', __('admin.parcel_pricing'))
@section('page_subtitle', __('admin.parcel_pricing_subtitle'))

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    <div class="flex items-center justify-between mb-5">
        @if (adminCan('settings', 'write'))
            <a href="{{ route('admin.parcel-pricing.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.new_pricing_rule') }}
            </a>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($pricings->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.name') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.type') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.weight_range') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.base_charge') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.per_km') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($pricings as $p)
                            <tr class="rr-row">
                                <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $p->name }}</td>
                                <td class="px-6 py-3">
                                    @php($colors = ['normal' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', 'fragile' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300', 'document' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'])
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colors[$p->parcel_type] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($p->parcel_type) }}</span>
                                </td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ rtrim(rtrim(number_format($p->min_weight,2),'0'),'.') }} – {{ rtrim(rtrim(number_format($p->max_weight,2),'0'),'.') }} kg</td>
                                <td class="px-6 py-3 text-end text-gray-700 dark:text-gray-300">{{ $currency }} {{ number_format($p->base_charge, 0) }}</td>
                                <td class="px-6 py-3 text-end text-gray-700 dark:text-gray-300">{{ $currency }} {{ number_format($p->per_km_charge, 0) }}</td>
                                <td class="px-6 py-3">
                                    @if (adminCan('settings', 'write'))
                                        <x-admin.toggle :route="route('admin.parcel-pricing.toggle-status', $p->id)" :checked="$p->is_active" />
                                    @else
                                        <span class="text-xs {{ $p->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $p->is_active ? __('admin.active') : __('admin.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        @if (adminCan('settings', 'write'))
                                            <a href="{{ route('admin.parcel-pricing.edit', $p->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs font-medium">{{ __('admin.edit') }}</a>
                                        @endif
                                        @if (adminCan('settings', 'delete'))
                                            <form method="POST" action="{{ route('admin.parcel-pricing.destroy', $p->id) }}" onsubmit="return confirm('{{ __('admin.delete_pricing_rule_confirm') }}');">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">{{ __('admin.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($pricings->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $pricings->links() }}</div>
            @endif
        @else
            <x-admin.empty-state :message="__('admin.no_parcel_pricing_rules')"
                :createRoute="adminCan('settings','write') ? route('admin.parcel-pricing.create') : null"
                :createLabel="__('admin.new_pricing_rule')" />
        @endif
    </div>

    {{-- Commission moved to Business Settings --}}
    <div class="mt-6 text-sm text-gray-500 dark:text-gray-400">
        {{ __('admin.commission_moved_hint') }}
        <a href="{{ route('admin.settings.business') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('admin.business_settings') }}</a>.
    </div>
@endsection
