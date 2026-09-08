@extends('layouts.auth')

@section('title', __('admin.admin_login'))
@section('shell-width', 'max-w-[430px]')

@php($adminLogo = \App\Models\SystemSetting::get('admin_logo'))
@php($logoUrl = $adminLogo ? \Illuminate\Support\Facades\Storage::url($adminLogo) : asset('images/logo.png'))
@php($tm = app(\App\Services\TranslationManager::class))
@php($authLocales = $tm->locales())
@php($currentLocale = app()->getLocale())
@php($isDark = request()->cookie('admin_theme', 'light') === 'dark')

{{-- Credentials are pre-filled ONLY when DEMO_MODE is on, so a production
     install (DEMO_MODE=false) still ships an empty, un-guessable form. --}}
@php($demoEmail = config('app.demo_admin_email'))
@php($demoPassword = config('app.demo_admin_password'))
@php($prefill = config('app.demo_mode') && $demoEmail && $demoPassword)

@push('styles')
<style>
    /* ---------- full-bleed animated backdrop ----------
       Colours the SVG reads are exposed as custom properties so the map can
       follow the theme without duplicating the markup. */
    .auth-bg {
        --route: rgba(109,77,255,.42);
        --dash: rgba(255,255,255,.9);
        --car-body: #4c2af8;
        --car-glass: rgba(255,255,255,.82);
        --wheel: #2a1a6e;

        position: fixed;
        inset: 0;
        z-index: 0;
        overflow: hidden;
        background:
            radial-gradient(ellipse 64% 56% at 14% 4%, rgba(167,139,250,.30) 0%, transparent 64%),
            radial-gradient(ellipse 58% 54% at 88% 92%, rgba(244,114,182,.20) 0%, transparent 66%),
            linear-gradient(158deg, #f7f5ff 0%, #eef1fe 46%, #fdf3fb 100%);
    }
    html.dark .auth-bg {
        --route: rgba(167,139,250,.5);
        --dash: rgba(255,255,255,.55);
        --car-body: #f8fafc;
        --car-glass: rgba(76,42,248,.82);
        --wheel: #0b0820;

        background:
            radial-gradient(ellipse 62% 55% at 16% 6%, rgba(124,58,237,.32) 0%, transparent 62%),
            radial-gradient(ellipse 58% 52% at 86% 88%, rgba(236,72,153,.17) 0%, transparent 64%),
            linear-gradient(158deg, #14103a 0%, #0c0925 48%, #05040f 100%);
    }
    /* soft halo behind the card so its edge stays defined on both themes */
    .auth-bg::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 44% 50% at 50% 50%, rgba(255,255,255,.55) 0%, transparent 70%);
    }
    html.dark .auth-bg::after {
        background: radial-gradient(ellipse 46% 52% at 50% 50%, rgba(6,4,26,.42) 0%, transparent 72%);
    }

    .auth-blob { position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none; }
    .auth-blob-1 {
        width: 40vw; height: 40vw; max-width: 560px; max-height: 560px; top: -12vh; right: -8vw;
        background: radial-gradient(circle at 40% 40%, rgba(129,140,248,.34), transparent 68%);
        animation: auth-drift-a 26s ease-in-out infinite;
    }
    .auth-blob-2 {
        width: 36vw; height: 36vw; max-width: 500px; max-height: 500px; bottom: -14vh; left: -8vw;
        background: radial-gradient(circle at 55% 45%, rgba(45,212,191,.24), transparent 68%);
        animation: auth-drift-b 32s ease-in-out infinite;
    }
    html.dark .auth-blob-1 { background: radial-gradient(circle at 40% 40%, rgba(99,102,241,.6), transparent 68%); }
    html.dark .auth-blob-2 { background: radial-gradient(circle at 55% 45%, rgba(6,182,212,.34), transparent 68%); }
    @keyframes auth-drift-a {
        0%, 100% { transform: translate3d(0,0,0) scale(1); }
        50% { transform: translate3d(-4vw, 6vh, 0) scale(1.14); }
    }
    @keyframes auth-drift-b {
        0%, 100% { transform: translate3d(0,0,0) scale(1); }
        50% { transform: translate3d(5vw, -5vh, 0) scale(1.12); }
    }

    /* ---------- road network + service fleet ---------- */
    .auth-map { position: absolute; inset: 0; opacity: .5; }
    .auth-route-dash { stroke-dasharray: 5 14; animation: auth-dash 1.9s linear infinite; }
    @keyframes auth-dash { to { stroke-dashoffset: -19; } }
    .auth-pin-pulse { transform-box: fill-box; transform-origin: center; animation: auth-pin 2.8s ease-out infinite; }
    @keyframes auth-pin {
        0% { transform: scale(.5); opacity: .8; }
        80%, 100% { transform: scale(2.4); opacity: 0; }
    }

    /* Vehicles live inside the SVG so offset-path resolves in the same viewBox
       user space as the roads they follow. Each one repeats the road's own `d`. */
    .auth-veh {
        offset-rotate: auto;
        animation-name: auth-drive;
        animation-timing-function: cubic-bezier(.44,.06,.5,.94);
        animation-iteration-count: infinite;
        filter: drop-shadow(0 4px 7px rgba(40,26,110,.28));
    }
    html.dark .auth-veh { filter: drop-shadow(0 4px 8px rgba(0,0,0,.6)); }

    .auth-veh-ride {
        offset-path: path('M-80 250 C 240 90, 470 300, 790 195 S 1160 95, 1380 265');
        animation-duration: 19s;
    }
    .auth-veh-food {
        offset-path: path('M-80 740 C 240 860, 440 615, 790 725 S 1160 865, 1380 700');
        animation-duration: 23s;
        animation-delay: -7s;
    }
    .auth-veh-parcel {
        offset-path: path('M185 -80 C 70 230, 300 430, 165 650 S 45 890, 240 1120');
        animation-duration: 26s;
        animation-delay: -13s;
    }
    .auth-veh-courier {
        offset-path: path('M1105 -80 C 1240 240, 1030 430, 1180 660 S 1265 890, 1080 1120');
        animation-duration: 21s;
        animation-delay: -4s;
    }

    @keyframes auth-drive {
        0% { offset-distance: 0%; opacity: 0; }
        5% { opacity: 1; }
        90% { offset-distance: 100%; opacity: 1; }
        97%, 100% { offset-distance: 100%; opacity: 0; }
    }

    /* ---------- card + controls ---------- */
    .auth-card {
        background: rgba(255,255,255,.86);
        border: 1px solid rgba(255,255,255,.9);
        box-shadow:
            0 1px 2px rgba(58,42,138,.06),
            0 18px 50px -14px rgba(58,42,138,.22),
            inset 0 1px 0 rgba(255,255,255,1);
    }
    html.dark .auth-card {
        background: rgba(21,22,38,.94);
        border-color: rgba(255,255,255,.09);
        box-shadow:
            0 1px 2px rgba(0,0,0,.4),
            0 26px 64px -18px rgba(0,0,0,.7),
            inset 0 1px 0 rgba(255,255,255,.06);
    }

    /* segmented locale + theme bar under the card */
    .auth-bar {
        background: rgba(255,255,255,.62);
        border: 1px solid rgba(93,68,214,.13);
        box-shadow: 0 1px 2px rgba(58,42,138,.05), 0 8px 22px -12px rgba(58,42,138,.24);
    }
    html.dark .auth-bar {
        background: rgba(255,255,255,.07);
        border-color: rgba(255,255,255,.1);
        box-shadow: none;
    }
    .auth-bar-sep { background: rgba(93,68,214,.15); }
    html.dark .auth-bar-sep { background: rgba(255,255,255,.14); }

    .auth-seg {
        color: #64748b;
        transition: background-color .2s ease, color .2s ease;
    }
    .auth-seg:hover { background: rgba(93,68,214,.09); color: #4c2af8; }
    .auth-seg-on { background: #fff; color: #4c2af8; box-shadow: 0 1px 2px rgba(58,42,138,.16); }
    html.dark .auth-seg { color: rgba(255,255,255,.6); }
    html.dark .auth-seg:hover { background: rgba(255,255,255,.1); color: #fff; }
    html.dark .auth-seg-on { background: rgba(255,255,255,.16); color: #fff; box-shadow: none; }

    .auth-submit { background-image: linear-gradient(135deg, #7c5cff, #4c2af8); }
    .auth-submit:hover { background-image: linear-gradient(135deg, #6d4dff, #3f22e6); }
    .auth-submit::after {
        content: '';
        position: absolute; top: 0; bottom: 0; left: 0; width: 42%;
        transform: translateX(-190%) skewX(-18deg);
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.34), transparent);
        animation: auth-sheen 5.2s ease-in-out infinite;
    }
    @keyframes auth-sheen {
        0%, 64% { transform: translateX(-190%) skewX(-18deg); }
        92%, 100% { transform: translateX(360%) skewX(-18deg); }
    }
    .auth-submit[data-busy="1"]::after { animation: none; }

    @media (prefers-reduced-motion: reduce) {
        .auth-blob-1, .auth-blob-2, .auth-route-dash, .auth-pin-pulse,
        .auth-veh, .auth-submit::after { animation: none; }
        .auth-veh { offset-distance: 52%; opacity: 1; }
    }
</style>
@endpush

{{-- ------------------------------------------------------------------ --}}
@section('backdrop')
<div class="auth-bg" aria-hidden="true">
    <span class="auth-blob auth-blob-1"></span>
    <span class="auth-blob auth-blob-2"></span>

    <div class="auth-map">
        <svg viewBox="0 0 1280 1000" preserveAspectRatio="xMidYMid slice" class="absolute inset-0 h-full w-full">
            @php($roads = [
                'M-80 250 C 240 90, 470 300, 790 195 S 1160 95, 1380 265',
                'M-80 740 C 240 860, 440 615, 790 725 S 1160 865, 1380 700',
                'M185 -80 C 70 230, 300 430, 165 650 S 45 890, 240 1120',
                'M1105 -80 C 1240 240, 1030 430, 1180 660 S 1265 890, 1080 1120',
            ])

            {{-- roads: solid asphalt stroke + animated centre line --}}
            @foreach ($roads as $d)
                <path d="{{ $d }}" stroke="var(--route)" stroke-width="22" stroke-linecap="round" fill="none" opacity=".2"/>
                <path d="{{ $d }}" stroke="var(--route)" stroke-width="9" stroke-linecap="round" fill="none"/>
                <path class="auth-route-dash" d="{{ $d }}" stroke="var(--dash)" stroke-width="2.4" stroke-linecap="round" fill="none"/>
            @endforeach

            {{-- pickup / drop pins on the main road --}}
            <circle cx="-80" cy="250" r="10" fill="#34d399" stroke="rgba(255,255,255,.85)" stroke-width="3.6"/>
            <circle class="auth-pin-pulse" cx="790" cy="725" r="17" fill="rgba(244,114,182,.36)"/>
            <circle cx="790" cy="725" r="10" fill="#f472b6" stroke="rgba(255,255,255,.85)" stroke-width="3.6"/>

            {{-- fleet: one per service line. Each shape is drawn around the
                 origin so offset-path seats it on its road. --}}

            {{-- ride --}}
            <g class="auth-veh auth-veh-ride">
                <circle cx="-14" cy="11" r="4.6" fill="var(--wheel)"/>
                <circle cx="15" cy="11" r="4.6" fill="var(--wheel)"/>
                <rect x="-26" y="-10" width="52" height="20" rx="9.4" fill="var(--car-body)"/>
                <rect x="-14.5" y="-7.4" width="11.5" height="14.8" rx="3.6" fill="var(--car-glass)"/>
                <rect x="1.5" y="-7.4" width="9.5" height="14.8" rx="3.6" fill="var(--car-glass)"/>
                <rect x="17.5" y="-4.8" width="5" height="9.6" rx="2.4" fill="#fbbf24"/>
            </g>

            {{-- food --}}
            <g class="auth-veh auth-veh-food">
                <circle cx="-12" cy="10" r="4.2" fill="var(--wheel)"/>
                <circle cx="13" cy="10" r="4.2" fill="var(--wheel)"/>
                <rect x="-21" y="-17" width="15" height="12" rx="3.2" fill="#fb923c"/>
                <rect x="-17" y="-14.6" width="7" height="2.4" rx="1.2" fill="rgba(255,255,255,.85)"/>
                <rect x="-22" y="-8" width="44" height="17" rx="8" fill="var(--car-body)"/>
                <rect x="-11" y="-5.8" width="9" height="12.6" rx="3.2" fill="var(--car-glass)"/>
                <rect x="2" y="-5.8" width="8" height="12.6" rx="3.2" fill="var(--car-glass)"/>
                <rect x="14.5" y="-4" width="4.4" height="8" rx="2.2" fill="#fbbf24"/>
            </g>

            {{-- parcel --}}
            <g class="auth-veh auth-veh-parcel">
                <circle cx="-15" cy="12" r="4.8" fill="var(--wheel)"/>
                <circle cx="16" cy="12" r="4.8" fill="var(--wheel)"/>
                <rect x="-24" y="-19" width="17" height="13" rx="3" fill="#34d399"/>
                <path d="M-15.5 -19 V-6" stroke="rgba(255,255,255,.9)" stroke-width="2"/>
                <path d="M-24 -12.5 H-7" stroke="rgba(255,255,255,.9)" stroke-width="2"/>
                <rect x="-27" y="-9" width="54" height="20" rx="6.4" fill="var(--car-body)"/>
                <rect x="-15" y="-6.6" width="10.5" height="14" rx="3" fill="var(--car-glass)"/>
                <rect x="1" y="-6.6" width="9" height="14" rx="3" fill="var(--car-glass)"/>
                <rect x="18.5" y="-4.6" width="5" height="9.2" rx="2.3" fill="#fbbf24"/>
            </g>

            {{-- courier --}}
            <g class="auth-veh auth-veh-courier">
                <circle cx="-11" cy="9.5" r="4" fill="var(--wheel)"/>
                <circle cx="12" cy="9.5" r="4" fill="var(--wheel)"/>
                <rect x="-19" y="-16" width="13" height="11" rx="3" fill="#38bdf8"/>
                <rect x="-15.6" y="-13.8" width="6.2" height="2.2" rx="1.1" fill="rgba(255,255,255,.85)"/>
                <rect x="-20" y="-7.6" width="40" height="16" rx="7.6" fill="var(--car-body)"/>
                <rect x="-10" y="-5.4" width="8.4" height="11.8" rx="3" fill="var(--car-glass)"/>
                <rect x="2" y="-5.4" width="7.4" height="11.8" rx="3" fill="var(--car-glass)"/>
                <rect x="13" y="-3.8" width="4.2" height="7.6" rx="2.1" fill="#fbbf24"/>
            </g>
        </svg>
    </div>
</div>
@endsection

{{-- ------------------------------------------------------------------ --}}
@section('content')
    {{-- brand --}}
    <div class="mb-6 text-center">
        <img src="{{ $logoUrl }}" alt="{{ appName() }}" class="mx-auto h-12 max-w-[200px] object-contain">
        <p class="mt-3.5 inline-flex items-center gap-2 rounded-full bg-violet-600/8 px-3 py-1 text-[11px] font-bold text-violet-700 ring-1 ring-violet-600/15 dark:bg-white/10 dark:text-white/85 dark:ring-white/15">
            <span class="relative flex h-1.5 w-1.5">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
            </span>
            {{ __('admin.login_hero_title') }}
        </p>
    </div>

    {{-- card --}}
    <div class="auth-card rounded-2xl p-8 backdrop-blur-xl">
        <div class="mb-6 text-center">
            <h1 class="text-[22px] font-extrabold tracking-tight text-gray-900 dark:text-gray-50">{{ __('admin.admin_login') }}</h1>
            <p class="mt-1 text-[13px] text-gray-500 dark:text-gray-400">{{ __('admin.admin_panel_sign_in') }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"/></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- the fields are already filled from the same config, so this is just
             a heads-up that the install is running in demo mode --}}
        @if ($prefill)
            <p class="mb-5 flex items-start gap-2 rounded-xl border border-indigo-200 bg-indigo-50/70 px-3.5 py-2.5 text-[11px] leading-relaxed text-indigo-700 dark:border-indigo-800/60 dark:bg-indigo-900/20 dark:text-indigo-300">
                <svg class="mt-px h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ __('admin.demo_credentials_prefilled') }}</span>
            </p>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-4"
              x-data="{ show: false, caps: false, busy: false }" @submit="busy = true">
            @csrf

            <div>
                <label for="email" class="mb-1.5 block text-[13px] font-semibold text-gray-700 dark:text-gray-300">{{ __('admin.email_address') }}</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                    <input id="email" name="email" type="email" value="{{ old('email', $prefill ? $demoEmail : '') }}" required autofocus autocomplete="username"
                        class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 ps-10 text-sm text-gray-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/12 dark:border-gray-600 dark:bg-gray-900/60 dark:text-gray-100 dark:focus:ring-indigo-500/20"
                        placeholder="admin@readyride.com">
                </div>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-[13px] font-semibold text-gray-700 dark:text-gray-300">{{ __('admin.password') }}</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </span>
                    <input id="password" name="password" required autocomplete="current-password"
                        @if ($prefill) value="{{ $demoPassword }}" @endif
                        :type="show ? 'text' : 'password'"
                        @keyup="caps = $event.getModifierState && $event.getModifierState('CapsLock')"
                        @keydown="caps = $event.getModifierState && $event.getModifierState('CapsLock')"
                        class="block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 pe-11 ps-10 text-sm text-gray-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/12 dark:border-gray-600 dark:bg-gray-900/60 dark:text-gray-100 dark:focus:ring-indigo-500/20"
                        placeholder="••••••••">
                    <button type="button" @click="show = !show" tabindex="-1"
                        :title="show ? '{{ __('admin.hide_password') }}' : '{{ __('admin.show_password') }}'"
                        :aria-label="show ? '{{ __('admin.hide_password') }}' : '{{ __('admin.show_password') }}'"
                        class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200">
                        <svg x-show="!show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                <p x-show="caps" x-cloak class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-amber-600 dark:text-amber-400">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"/></svg>
                    {{ __('admin.caps_lock_on') }}
                </p>
            </div>

            <label class="flex items-center gap-2 pt-0.5 text-[13px] text-gray-600 dark:text-gray-400">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600">
                {{ __('admin.remember_me') }}
            </label>

            <div class="flex justify-center pt-2">
                <button type="submit" x-bind:data-busy="busy ? '1' : '0'" x-bind:disabled="busy"
                    class="auth-submit relative overflow-hidden rounded-full px-14 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/30 transition hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900">
                    {{-- fixed min-width so the spinner can't resize the button mid-submit --}}
                    <span class="relative z-10 flex min-w-[7.5rem] items-center justify-center gap-2">
                        <svg x-show="busy" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        {{ __('admin.sign_in') }}
                    </span>
                </button>
            </div>
        </form>
    </div>

    {{-- locale + theme: both routes sit outside the auth group on purpose --}}
    <div class="mt-7 flex justify-center">
        <div class="auth-bar inline-flex items-center gap-1 rounded-full p-1 backdrop-blur">
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" @click.outside="open = false"
                    class="auth-seg inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[11px] font-semibold">
                    <svg class="h-3.5 w-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-9c2.5 2.5 3.5 5.8 3.5 9s-1 6.5-3.5 9c-2.5-2.5-3.5-5.8-3.5-9S9.5 5.5 12 3zM3.6 9h16.8M3.6 15h16.8"/>
                    </svg>
                    {{ $authLocales[$currentLocale]['name'] ?? strtoupper($currentLocale) }}
                    <svg class="h-3 w-3 opacity-50 transition" :class="open && '-rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                {{-- opens upward: the bar sits at the bottom of the page --}}
                <div x-show="open" x-cloak x-transition
                    class="absolute bottom-full start-1/2 z-30 mb-2 max-h-64 w-40 -translate-x-1/2 overflow-y-auto rounded-xl border border-gray-200 bg-white py-1 shadow-2xl rtl:translate-x-1/2 dark:border-gray-700 dark:bg-gray-800">
                    @foreach ($authLocales as $code => $meta)
                        <a href="{{ route('admin.set-locale', $code) }}"
                           class="flex items-center justify-between gap-2 px-3 py-2 text-xs {{ $code === $currentLocale ? 'font-semibold text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300' }} hover:bg-gray-50 dark:hover:bg-gray-700">
                            {{ $meta['name'] ?? strtoupper($code) }}
                            @if ($code === $currentLocale)
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <span class="auth-bar-sep h-4 w-px"></span>

            <a href="{{ route('admin.set-theme', 'light') }}" title="{{ __('admin.light_mode') }}" aria-label="{{ __('admin.light_mode') }}"
               class="auth-seg {{ $isDark ? '' : 'auth-seg-on' }} inline-flex h-7 w-7 items-center justify-center rounded-full">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </a>
            <a href="{{ route('admin.set-theme', 'dark') }}" title="{{ __('admin.dark_mode') }}" aria-label="{{ __('admin.dark_mode') }}"
               class="auth-seg {{ $isDark ? 'auth-seg-on' : '' }} inline-flex h-7 w-7 items-center justify-center rounded-full">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </a>
        </div>
    </div>

    <p class="mt-4 text-center text-[11px] text-slate-400 dark:text-white/40">
        &copy; {{ date('Y') }} {{ appName() }}. {{ __('admin.all_rights_reserved') }}
    </p>
@endsection
