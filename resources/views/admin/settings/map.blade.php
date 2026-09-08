@extends('layouts.admin')

@section('title', __('admin.map'))
@section('page_title', __('admin.map'))

@php($w = adminCan('settings', 'write'))

@section('content')
    <x-admin.settings-tabs />

    <form method="POST" action="{{ route('admin.settings.map.update') }}" class="space-y-6 max-w-4xl"
          x-data="{
            keyShown: false, testing: false, result: null,
            async testKey() {
                this.testing = true; this.result = null;
                try {
                    const res = await fetch('{{ route('admin.settings.map.test-key') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
                    const data = await res.json();
                    this.result = { ok: res.ok, message: data.message };
                } catch (e) { this.result = { ok: false, message: 'Request failed.' }; }
                finally { this.testing = false; }
            }
          }">
        @csrf

        {{-- Google Maps --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.google_maps') }}</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.api_key') }}</label>
                    <div class="relative">
                        <input name="google_maps_key" :type="keyShown ? 'text' : 'password'" value="{{ $settings['google_maps_key'] }}" {{ $w ? '' : 'disabled' }}
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 pe-16 text-sm font-mono">
                        <button type="button" @click="keyShown = !keyShown" class="absolute end-2 top-1/2 -translate-y-1/2 text-xs text-gray-500 dark:text-gray-400" x-text="keyShown ? '{{ __('admin.hide') }}' : '{{ __('admin.show') }}'"></button>
                    </div>
                </div>
                @if ($w)
                    <div class="flex items-center gap-3">
                        <button type="button" @click="testKey()" :disabled="testing" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"><span x-text="testing ? '{{ __('admin.testing') }}' : '{{ __('admin.test_key') }}'"></span></button>
                        <p x-show="result" x-cloak class="text-sm" :class="result?.ok ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="result?.message"></p>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-400">{{ __('admin.save_key_first') }}</p>
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.center_lat') }}</label><input name="map_center_lat" value="{{ $settings['map_center_lat'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.center_lng') }}</label><input name="map_center_lng" value="{{ $settings['map_center_lng'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.zoom') }}</label><input name="map_zoom" type="number" min="8" max="18" value="{{ $settings['map_zoom'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.map_type') }}</label>
                        <select name="map_type" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
                            @foreach (['roadmap','satellite','hybrid'] as $t)<option value="{{ $t }}" @selected($settings['map_type'] === $t)>{{ __('admin.' . $t) }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>
        </section>

        {{-- Driver Tracking --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.driver_tracking') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.location_update_interval') }}</label>
                    <div class="flex gap-4 text-sm pt-1">
                        @foreach (['3','5','10'] as $i)
                            <label class="inline-flex items-center gap-1"><input type="radio" name="location_update_interval" value="{{ $i }}" @checked($settings['location_update_interval'] === $i) {{ $w ? '' : 'disabled' }} class="text-indigo-600"> {{ $i }}s</label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.track_driver_when') }}</label>
                    <div class="flex gap-4 text-sm pt-1">
                        <label class="inline-flex items-center gap-1"><input type="radio" name="track_driver_when" value="online" @checked($settings['track_driver_when'] === 'online') {{ $w ? '' : 'disabled' }} class="text-indigo-600"> {{ __('admin.always_online') }}</label>
                        <label class="inline-flex items-center gap-1"><input type="radio" name="track_driver_when" value="trip" @checked($settings['track_driver_when'] === 'trip') {{ $w ? '' : 'disabled' }} class="text-indigo-600"> {{ __('admin.only_during_trip') }}</label>
                    </div>
                </div>
            </div>
        </section>

        {{-- Order Matching --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.order_matching') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.initial_radius_km') }}</label><input name="search_radius_km" type="number" min="1" max="20" value="{{ $settings['search_radius_km'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
                <div class="flex items-end"><label class="inline-flex items-center gap-2 text-sm pb-2"><input type="hidden" name="auto_expand_radius" value="0"><input type="checkbox" name="auto_expand_radius" value="1" @checked($settings['auto_expand_radius']) {{ $w ? '' : 'disabled' }} class="rounded text-indigo-600"> {{ __('admin.auto_expand') }}</label></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.expand_by_km_sec') }}</label><div class="flex gap-1"><input name="radius_expand_km" type="number" min="1" value="{{ $settings['radius_expand_km'] }}" {{ $w ? '' : 'disabled' }} class="w-1/2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-2 py-2.5 text-sm"><input name="radius_expand_seconds" type="number" min="5" value="{{ $settings['radius_expand_seconds'] }}" {{ $w ? '' : 'disabled' }} class="w-1/2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-2 py-2.5 text-sm"></div></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.max_radius_km') }}</label><input name="max_radius_km" type="number" min="1" max="50" value="{{ $settings['max_radius_km'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.max_dispatch_attempts') }}</label><input name="max_dispatch_attempts" type="number" min="1" max="50" value="{{ $settings['max_dispatch_attempts'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm"></div>
            </div>
        </section>

        {{-- Zone Mode + Test/Demo (.env) --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.zone_mode') }}</h3>

            @if (config('readyride.test_mode'))
                <div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
                    <span>⚠️</span>
                    <span>{{ __('admin.test_mode_env_note') }}</span>
                </div>
            @endif

            <div>
                <div class="flex flex-col gap-2 text-sm pt-1">
                    <label class="inline-flex items-start gap-2">
                        <input type="radio" name="zone_mode" value="flexible" @checked($settings['zone_mode'] === 'flexible') {{ $w ? '' : 'disabled' }} class="mt-0.5 text-indigo-600">
                        <span><span class="font-medium text-gray-700 dark:text-gray-300">{{ __('admin.zone_mode_flexible') }}</span> <span class="text-gray-400 dark:text-gray-400">— {{ __('admin.zone_mode_flexible_help') }}</span></span>
                    </label>
                    <label class="inline-flex items-start gap-2">
                        <input type="radio" name="zone_mode" value="strict" @checked($settings['zone_mode'] === 'strict') {{ $w ? '' : 'disabled' }} class="mt-0.5 text-indigo-600">
                        <span><span class="font-medium text-gray-700 dark:text-gray-300">{{ __('admin.zone_mode_strict') }}</span> <span class="text-gray-400 dark:text-gray-400">— {{ __('admin.zone_mode_strict_help') }}</span></span>
                    </label>
                </div>
            </div>
        </section>

        @if ($w)<button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save_settings') }}</button>@endif
    </form>
@endsection
