@extends('layouts.admin')

@section('title', __('admin.general_settings'))
@section('page_title', __('admin.general_settings'))

@php($w = adminCan('settings', 'write'))

@section('content')
    <x-admin.settings-tabs />

    <form method="POST" action="{{ route('admin.settings.general.update') }}" enctype="multipart/form-data" class="space-y-6 max-w-4xl"
          x-data="{ maintenance: {{ $settings['maintenance_mode'] ? 'true' : 'false' }} }">
        @csrf

        {{-- Branding --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.branding') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.app_logo') }}</label>
                    @if ($settings['app_logo'])<img src="{{ Storage::url($settings['app_logo']) }}" class="h-10 mb-2">@endif
                    <input type="file" name="app_logo" accept="image/*" {{ $w ? '' : 'disabled' }} class="block w-full text-sm text-gray-500 dark:text-gray-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.favicon') }}</label>
                    @if ($settings['app_favicon'])<img src="{{ Storage::url($settings['app_favicon']) }}" class="h-8 mb-2">@endif
                    <input type="file" name="app_favicon" accept="image/*" {{ $w ? '' : 'disabled' }} class="block w-full text-sm text-gray-500 dark:text-gray-400">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.admin_panel_logo') }}</label>
                    @if ($settings['admin_logo'])
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center rounded-md bg-gray-900 px-2 py-1"><img src="{{ Storage::url($settings['admin_logo']) }}" class="h-8"></span>
                            @if ($w)
                                <label class="inline-flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400 cursor-pointer">
                                    <input type="checkbox" name="remove_admin_logo" value="1" class="rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500"> {{ __('admin.remove_logo') }}
                                </label>
                            @endif
                        </div>
                    @endif
                    <input type="file" name="admin_logo" accept="image/*" {{ $w ? '' : 'disabled' }} class="block w-full text-sm text-gray-500 dark:text-gray-400">
                    <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">{{ __('admin.admin_logo_hint') }}</p>
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-400 dark:text-gray-400">{{ __('admin.business_moved_hint') }}</p>
        </section>

        {{-- Contact & Social --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.contact_social') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @php($ci = 'block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600')
                @php($cl = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1')
                <div><label class="{{ $cl }}">{{ __('admin.support_email') }}</label><input name="support_email" type="email" value="{{ $settings['support_email'] }}" {{ $w ? '' : 'disabled' }} class="{{ $ci }}"></div>
                <div><label class="{{ $cl }}">{{ __('admin.support_phone') }}</label><input name="support_phone" value="{{ $settings['support_phone'] }}" {{ $w ? '' : 'disabled' }} class="{{ $ci }}"></div>
                <div><label class="{{ $cl }}">{{ __('admin.support_whatsapp') }}</label><input name="support_whatsapp" value="{{ $settings['support_whatsapp'] }}" {{ $w ? '' : 'disabled' }} placeholder="+8801..." class="{{ $ci }}"></div>
                <div><label class="{{ $cl }}">{{ __('admin.support_hours') }}</label><input name="support_hours" value="{{ $settings['support_hours'] }}" {{ $w ? '' : 'disabled' }} placeholder="9 AM - 9 PM" class="{{ $ci }}"></div>
                <div class="sm:col-span-2"><label class="{{ $cl }}">{{ __('admin.office_address') }}</label><input name="office_address" value="{{ $settings['office_address'] }}" {{ $w ? '' : 'disabled' }} class="{{ $ci }}"></div>
                <div><label class="{{ $cl }}">Facebook</label><input name="social_facebook" type="url" value="{{ $settings['social_facebook'] }}" {{ $w ? '' : 'disabled' }} placeholder="https://…" class="{{ $ci }}"></div>
                <div><label class="{{ $cl }}">Instagram</label><input name="social_instagram" type="url" value="{{ $settings['social_instagram'] }}" {{ $w ? '' : 'disabled' }} placeholder="https://…" class="{{ $ci }}"></div>
                <div><label class="{{ $cl }}">YouTube</label><input name="social_youtube" type="url" value="{{ $settings['social_youtube'] }}" {{ $w ? '' : 'disabled' }} placeholder="https://…" class="{{ $ci }}"></div>
                <div><label class="{{ $cl }}">{{ __('admin.website') }}</label><input name="social_website" type="url" value="{{ $settings['social_website'] }}" {{ $w ? '' : 'disabled' }} placeholder="https://…" class="{{ $ci }}"></div>
            </div>
        </section>

        {{-- App Store Links --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.app_links') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ([
                    'customer_play_store' => __('admin.customer_play_store'), 'customer_app_store' => __('admin.customer_app_store'),
                    'driver_play_store' => __('admin.driver_play_store'), 'driver_app_store' => __('admin.driver_app_store'),
                ] as $key => $label)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $label }}</label>
                        <input name="{{ $key }}" value="{{ $settings[$key] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Maintenance --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.maintenance_mode') }}</h3>
            <label class="inline-flex items-center gap-2 mb-3">
                <input type="hidden" name="maintenance_mode" value="0">
                <input type="checkbox" name="maintenance_mode" value="1" x-model="maintenance" @checked($settings['maintenance_mode']) {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.enable_maintenance_mode') }}</span>
            </label>
            <textarea name="maintenance_message" rows="2" x-show="maintenance" x-cloak placeholder="{{ __('admin.maintenance_message') }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">{{ $settings['maintenance_message'] }}</textarea>
        </section>

        @if ($w)
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save_settings') }}</button>
        @endif
    </form>
@endsection
