@php($admin = auth('admin')->user())
@php($locale = app()->getLocale())
@php($tm = app(\App\Services\TranslationManager::class))
@php($adminLocales = $tm->locales())
@php($adminTheme = request()->cookie('admin_theme', 'light'))

{{-- Top navigation bar --}}
<header class="sticky top-0 z-20 h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3 px-4 sm:px-6">
    {{-- Mobile sidebar toggle --}}
    <button type="button" @click="sidebarOpen = true" class="lg:hidden text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <div class="min-w-0 flex-1">
        <h1 class="truncate text-lg font-semibold leading-tight text-gray-800 dark:text-gray-100">@yield('page_title', __('admin.dashboard'))</h1>
        @hasSection('page_subtitle')
            {{-- dropped on small screens so the title keeps its width --}}
            <p class="hidden truncate text-xs leading-tight text-gray-500 sm:block dark:text-gray-400">@yield('page_subtitle')</p>
        @endif
    </div>

    {{-- Omnisearch --}}
    <x-admin.global-search />

    {{-- Setup Guide — only while integrations are still pending; it disappears
         once everything is configured (the sidebar keeps a permanent entry). --}}
    @php($setupStatus = function_exists('integrationStatus') ? integrationStatus() : [])
    @php($setupTotal = count($setupStatus))
    @php($setupDone = count(array_filter($setupStatus)))
    @php($setupPendingPct = $setupTotal ? (int) round(($setupTotal - $setupDone) / $setupTotal * 100) : 0)

    @if ($setupPendingPct > 0)
        <a href="{{ route('admin.setup-guide') }}"
           title="{{ __('admin.setup_guide') }} — {{ $setupPendingPct }}% {{ __('admin.pending') }}"
           class="flex shrink-0 items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-sm text-amber-700 transition hover:bg-amber-100 dark:border-amber-800/60 dark:bg-amber-900/25 dark:text-amber-300 dark:hover:bg-amber-900/40">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M12 17h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="hidden lg:inline">{{ __('admin.setup_guide') }}</span>
            {{-- on phones the icon alone carries the meaning --}}
            <span class="hidden rounded-md bg-amber-200/70 px-1.5 py-0.5 text-[11px] font-bold leading-none sm:inline dark:bg-amber-800/50">
                {{ $setupPendingPct }}% {{ __('admin.pending') }}
            </span>
        </a>
    @endif

    {{-- Notification bell --}}
    <x-admin.notification-bell />

    {{-- Admin dropdown: profile, theme, language, logout --}}
    <div class="relative" x-data="{ open: false, confirmLogout: false }">
        <button type="button" @click="open = !open" class="flex items-center gap-3 rounded-lg px-2 py-1.5 transition hover:bg-gray-100 dark:hover:bg-gray-700">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                {{ strtoupper(substr($admin->name ?? 'A', 0, 1)) }}
            </div>
            <div class="hidden text-start sm:block">
                <p class="text-sm font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    {{ $admin->name ?? 'Admin' }}
                    <span class="font-normal text-gray-400">({{ ucwords(str_replace('_', ' ', $admin->role ?? '')) }})</span>
                </p>
                <p class="text-xs leading-tight text-gray-500 dark:text-gray-400">{{ $admin->email ?? '' }}</p>
            </div>
            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.end
             class="absolute end-0 z-30 mt-2 w-60 overflow-hidden rounded-xl border border-gray-100 bg-white py-1.5 shadow-xl dark:border-gray-700 dark:bg-gray-800">

            <a href="{{ route('admin.profile') }}"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                {{ __('admin.view_profile') }}
            </a>

            {{-- Theme --}}
            <p class="mt-1 border-t border-gray-100 px-4 pb-1 pt-2.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:border-gray-700 dark:text-gray-500">{{ __('admin.theme') }}</p>
            @foreach ([
                ['key' => 'light',  'label' => __('admin.light'),  'icon' => 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z'],
                ['key' => 'dark',   'label' => __('admin.dark'),   'icon' => 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z'],
                ['key' => 'system', 'label' => __('admin.system'), 'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ] as $opt)
                <a href="{{ route('admin.set-theme', $opt['key']) }}"
                   class="flex items-center gap-3 px-4 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-700
                          {{ $adminTheme === $opt['key'] ? 'font-semibold text-gray-900 dark:text-gray-50' : 'text-gray-500 dark:text-gray-400' }}">
                    <svg class="h-4 w-4 {{ $adminTheme === $opt['key'] ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="{{ $opt['icon'] }}"/>
                    </svg>
                    <span class="flex-1">{{ $opt['label'] }}</span>
                    @if ($adminTheme === $opt['key'])
                        <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M5 13l4 4L19 7"/></svg>
                    @endif
                </a>
            @endforeach

            {{-- Language --}}
            <p class="mt-1 border-t border-gray-100 px-4 pb-1 pt-2.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:border-gray-700 dark:text-gray-500">{{ __('admin.language') }}</p>
            <div class="max-h-40 overflow-y-auto">
                @foreach ($adminLocales as $code => $info)
                    <a href="{{ route('admin.set-locale', $code) }}"
                       class="flex items-center gap-3 px-4 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-700
                              {{ $locale === $code ? 'font-semibold text-gray-900 dark:text-gray-50' : 'text-gray-500 dark:text-gray-400' }}">
                        <svg class="h-4 w-4 {{ $locale === $code ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-9c2.5 2.5 3.5 5.8 3.5 9s-1 6.5-3.5 9c-2.5-2.5-3.5-5.8-3.5-9S9.5 5.5 12 3zM3.6 9h16.8M3.6 15h16.8"/></svg>
                        <span class="flex-1">{{ $info['name'] ?? strtoupper($code) }}</span>
                        @if ($locale === $code)
                            <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </a>
                @endforeach
            </div>
            @if (adminCan('settings', 'write'))
                <a href="{{ route('admin.languages.index') }}" class="block px-4 py-1.5 text-xs text-gray-400 transition hover:bg-gray-50 dark:hover:bg-gray-700">⚙ {{ __('admin.manage_languages') }}</a>
            @endif

            {{-- Logout --}}
            <button type="button" @click="open = false; confirmLogout = true"
                class="mt-1 flex w-full items-center gap-3 border-t border-gray-100 px-4 py-2.5 text-start text-sm font-semibold text-red-600 transition hover:bg-red-50 dark:border-gray-700 dark:hover:bg-red-900/20">
                <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                {{ __('admin.logout') }}
            </button>
        </div>

        {{-- Logout confirmation --}}
        <div x-show="confirmLogout" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div x-show="confirmLogout" x-transition.opacity class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="confirmLogout = false"></div>
            <div x-show="confirmLogout" x-transition.scale.origin.center
                 class="relative w-full max-w-sm rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-2xl dark:border-gray-700 dark:bg-gray-800"
                 @keydown.escape.window="confirmLogout = false">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                    <svg class="h-6 w-6 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-50">{{ __('admin.logout_confirm_title') }}</h3>
                <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.logout_confirm_body') }}</p>
                <div class="mt-6 flex gap-3">
                    <button type="button" @click="confirmLogout = false"
                            class="flex-1 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        {{ __('admin.cancel') }}
                    </button>
                    <form method="POST" action="{{ route('admin.logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                            {{ __('admin.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

@once
    @push('scripts')
    <script>
        // Keep 'system' in step with the OS while the tab is open.
        if (window.rrTheme === 'system' && window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)')
                .addEventListener('change', function (e) {
                    document.documentElement.classList.toggle('dark', e.matches);
                });
        }
    </script>
    @endpush
@endonce
