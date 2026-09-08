{{-- Shared tab bar across every Settings screen. Each tab is its own route,
     so this is navigation only — no controller or form logic changes. --}}
@php
    $isSuper = adminUser()?->isSuperAdmin() ?? false;

    $tabs = [
        [
            'route' => 'admin.settings.business',
            'match' => ['admin.settings.business'],
            'label' => __('admin.business_settings'),
            'icon'  => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            'show'  => adminCan('settings', 'read'),
        ],
        [
            'route' => 'admin.settings.general',
            'match' => ['admin.settings.general'],
            'label' => __('admin.general'),
            'icon'  => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            'show'  => adminCan('settings', 'read'),
        ],
        [
            'route' => 'admin.settings.pricing',
            'match' => ['admin.settings.pricing'],
            'label' => __('admin.pricing'),
            'icon'  => 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z',
            'show'  => adminCan('settings', 'read'),
        ],
        [
            'route' => 'admin.settings.map',
            'match' => ['admin.settings.map'],
            'label' => __('admin.map'),
            'icon'  => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
            'show'  => adminCan('settings', 'read'),
        ],
        [
            'route' => 'admin.settings.payment',
            'match' => ['admin.settings.payment'],
            'label' => __('admin.payment_gateway'),
            'icon'  => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'show'  => adminCan('settings', 'read'),
        ],
        [
            'route' => 'admin.settings.notifications',
            'match' => ['admin.settings.notifications'],
            'label' => __('admin.notifications'),
            'icon'  => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
            'show'  => adminCan('settings', 'read'),
        ],
        [
            'route' => 'admin.languages.index',
            'match' => ['admin.languages.*'],
            'label' => __('admin.languages'),
            'icon'  => 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129',
            'show'  => adminCan('settings', 'read'),
        ],
        [
            'route' => 'admin.sub-admins.index',
            'match' => ['admin.sub-admins.*'],
            'label' => __('admin.sub_admins'),
            'icon'  => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z',
            'show'  => adminCan('settings', 'read'),
        ],
        [
            'route' => 'admin.settings.advanced',
            'match' => ['admin.settings.advanced'],
            'label' => __('admin.advanced'),
            'icon'  => 'M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z',
            // the whole Advanced controller is super-admin only
            'show'  => $isSuper,
        ],
    ];

    $tabs = array_values(array_filter($tabs, fn ($t) => $t['show']));
@endphp

@if (count($tabs) > 1)
    {{-- wraps rather than scrolls: a hidden settings tab is a tab nobody finds --}}
    <div class="settings-tabs mb-6">
        <nav class="flex flex-wrap gap-1 rounded-xl border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @foreach ($tabs as $tab)
                @php($active = request()->routeIs(...$tab['match']))
                <a href="{{ route($tab['route']) }}"
                   class="settings-tab inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-[13px] font-semibold whitespace-nowrap
                          {{ $active
                              ? 'bg-indigo-600 text-white shadow-sm'
                              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0 {{ $active ? '' : 'opacity-70' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="{{ $tab['icon'] }}"/>
                    </svg>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
@endif
