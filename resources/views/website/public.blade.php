{{-- $publicV2Languages, $publicV2CurrentLanguage, $publicV2Locale, $publicV2Dir
     are provided by the View composer in AppServiceProvider (website.public). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $publicV2Locale) }}" dir="{{ $publicV2Dir }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', siteBrand())</title>
    @include('partials.favicon')

    {{-- Init dark mode before Tailwind renders to prevent flash --}}
    <script>
        (function () {
            if (localStorage.getItem('rr-theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#f5f3ff',100:'#ede9fe',200:'#ddd6fe',400:'#a78bfa',500:'#7c3aed',600:'#6d28d9' }
                    },
                    animation: { float: 'float 4s ease-in-out infinite' },
                    keyframes: {
                        float: { '0%,100%':{ transform:'translateY(0)' }, '50%':{ transform:'translateY(-12px)' } }
                    }
                }
            }
        }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; scroll-behavior: smooth; }
        html, body { max-width: 100%; overflow-x: hidden; }
        *, *::before, *::after { box-sizing: border-box; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #7c3aed; border-radius: 3px; }
        .gradient-text { background: linear-gradient(135deg,#4f36ff 0%,#5f39ff 45%,#6f46ff 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        #pub-mobile-menu { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; }
        #pub-mobile-menu.open { max-height: 480px; }

        /* ── Global dark mode (CSS-only, no Tailwind CDN dependency) ── */
        html.dark body                          { background-color: #090914 !important; color: #f1f5f9 !important; }
        html.dark header                        { background-color: #090914 !important; border-color: rgba(255,255,255,.06) !important; }
        html.dark footer                        { background-color: #090914 !important; }
        html.dark .bg-white                     { background-color: #0d0d1f !important; }
        html.dark .bg-gray-50                   { background-color: #111124 !important; }
        html.dark .bg-gray-100                  { background-color: #1a1a30 !important; }
        html.dark .border-gray-100              { border-color: rgba(255,255,255,.08) !important; }
        html.dark .border-gray-200              { border-color: rgba(255,255,255,.12) !important; }
        html.dark .border-gray-300              { border-color: rgba(255,255,255,.15) !important; }
        html.dark .text-gray-900                { color: #f8fafc !important; }
        html.dark .text-gray-800                { color: #f1f5f9 !important; }
        html.dark .text-gray-700                { color: #e2e8f0 !important; }
        html.dark .text-gray-600                { color: #cbd5e1 !important; }
        html.dark .text-gray-500                { color: #94a3b8 !important; }
        html.dark .text-gray-400                { color: #64748b !important; }
        html.dark .shadow-sm                    { box-shadow: 0 1px 3px rgba(0,0,0,.4) !important; }
        html.dark .shadow-lg                    { box-shadow: 0 10px 30px rgba(0,0,0,.5) !important; }
        .pub-lang-wrap { position: relative; }
        .pub-lang-btn { display: inline-flex; align-items: center; gap: 8px; transition: color .2s ease; }
        .pub-lang-btn:hover { color: #563BFF; }
        .pub-lang-flag { width: 18px; height: 18px; border-radius: 999px; object-fit: cover; flex: 0 0 auto; }
        .pub-lang-menu {
            position: absolute;
            left: 50%;
            bottom: calc(100% + 10px);
            z-index: 60;
            min-width: 150px;
            padding: 7px;
            border: 1px solid #e8ebf3;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 16px 36px rgba(17,24,51,.12);
            opacity: 0;
            visibility: hidden;
            transform: translate(-50%, 8px);
            transition: .2s ease;
        }
        .pub-lang-wrap:hover .pub-lang-menu,
        .pub-lang-wrap.is-open .pub-lang-menu {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, 0);
        }
        .pub-lang-menu a {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 10px;
            border-radius: 8px;
            color: #465272;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }
        .pub-lang-menu a:hover,
        .pub-lang-menu a.is-active {
            background: #f5f3ff;
            color: #563BFF;
        }
        html.dark .pub-lang-menu {
            border-color: rgba(255,255,255,.08);
            background: #111124;
            box-shadow: 0 16px 36px rgba(0,0,0,.42);
        }
        html.dark .pub-lang-menu a { color: #cbd5e1; }
        html.dark .pub-lang-menu a:hover,
        html.dark .pub-lang-menu a.is-active {
            background: rgba(86,59,255,.16);
            color: #fff;
        }
    </style>

    @include('partials.design-system')
    @yield('styles')
</head>

<body class="min-h-screen bg-white dark:bg-[#090914] text-gray-900 dark:text-gray-100 @yield('body-class')">

{{-- ══════════════════════════════════════════
     GLOBAL HEADER
══════════════════════════════════════════ --}}
<header class="fixed top-0 left-0 right-0 z-50 bg-white dark:bg-[#090914] border-b border-[#e8ebf3] dark:border-white/[0.06]">

    {{-- ── Main navbar row ── --}}
    <div class="max-w-[1088px] mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-[74px]">

            {{-- Logo --}}
            @php
                $pubBrand = siteBrand();
                $pubLogo = siteImage('shared', 'nav', 'logo', 'images/logo.png');
            @endphp
            <a href="{{ route('landing') }}" class="flex items-center gap-[15px] flex-shrink-0">
                @if ($pubLogo)
                    <img src="{{ $pubLogo }}" alt="{{ $pubBrand }}" class="h-[42px] w-auto">
                @else
                    <div class="w-[42px] h-[42px] bg-[#563BFF] rounded-[10px] flex items-center justify-center shadow-[0_10px_22px_rgba(86,59,255,.22)]">
                        <span class="text-white font-bold text-[30px] leading-none tracking-[-0.08em]">{{ mb_substr($pubBrand, 0, 1) }}</span>
                    </div>
                    <span class="font-extrabold text-[31px] leading-none tracking-[-0.045em] text-[#0b1235] dark:text-white">{{ $pubBrand }}</span>
                @endif
            </a>

            {{-- Desktop nav links (labels editable per language; routes/active fixed) --}}
            @php
                $pubActive   = trim($__env->yieldContent('active')) ?: ($active ?? '');
                $pubNavItems = [
                    [siteText('shared', 'nav', 'label_home', 'Home'),         route('landing'),     'home'],
                    [siteText('shared', 'nav', 'label_features', 'Features'), route('features'), 'features'],
                    [siteText('shared', 'nav', 'label_safety', 'Safety'),     route('safety'),   'safety'],
                    [siteText('shared', 'nav', 'label_help', 'Help'),         route('help'),     'help'],
                    [siteText('shared', 'nav', 'label_about', 'About'),       route('about'),    'about'],
                ];
            @endphp

            <nav class="hidden lg:flex items-center gap-[38px]">
                @foreach($pubNavItems as [$label, $href, $key])
                    @if($pubActive === $key)
                        <a href="{{ $href }}"
                           data-pub-nav="{{ $key }}"
                           class="text-[14px] font-extrabold text-[#563BFF] relative pb-0.5
                                  after:absolute after:bottom-[-27px] after:left-0 after:right-0
                                  after:h-[2px] after:bg-[#563BFF] after:rounded-full">
                            {{ __($label) }}
                        </a>
                    @else
                        <a href="{{ $href }}"
                           data-pub-nav="{{ $key }}"
                           class="text-[14px] font-extrabold text-[#0b1235] dark:text-gray-200
                                  hover:text-[#563BFF] dark:hover:text-white transition-colors">
                            {{ __($label) }}
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- Right: Download + mobile menu --}}
            <div class="flex items-center gap-[17px]">
                <div class="hidden xl:flex items-center gap-[13px] text-[13px] font-extrabold text-[#0b1235] dark:text-gray-200">
                    {{-- <button id="pub-head-light-btn" onclick="rrSetTheme('light')" class="flex items-center gap-[6px] hover:text-[#563BFF] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="5"/>
                            <path stroke-linecap="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                        </svg>
                        {{ __('Light') }}
                    </button> --}}
                    {{-- <button id="pub-head-dark-btn" onclick="rrSetTheme('dark')" class="flex items-center gap-[6px] hover:text-[#563BFF] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                        </svg>
                        {{ __('Dark') }}
                    </button> --}}
                    {{-- <div class="w-px h-4 bg-[#d9deea] dark:bg-white/20"></div> --}}
                    {{-- <button class="flex items-center gap-[6px] hover:text-[#563BFF] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                        </svg>
                        {{ __('English') }}
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button> --}}
                </div>

                {{-- <a href="{{ route('landing') }}#download"
                   class="hidden sm:inline-flex items-center justify-center bg-[#563BFF] hover:bg-[#4f35f6]
                          text-white text-[14px] font-extrabold w-[139px] h-[43px] rounded-[7px]
                          transition-all shadow-[0_10px_22px_rgba(86,59,255,.22)]">
                    {{ __('Download App') }}
                </a> --}}

                {{-- Mobile hamburger --}}
                <button id="pub-menu-btn"
                    class="lg:hidden w-9 h-9 bg-gray-100 dark:bg-white/[0.08] rounded-lg
                           flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-700 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div id="pub-mobile-menu" class="lg:hidden">
            <div class="bg-white dark:bg-[#0f0f24] rounded-2xl mb-3 mt-1
                        border border-gray-100 dark:border-white/[0.08]
                        shadow-lg px-4 py-4 flex flex-col gap-1">
                @foreach($pubNavItems as [$label, $href, $key])
                    @if($pubActive === $key)
                        <a href="{{ $href }}" class="py-2.5 px-3 rounded-lg text-sm font-semibold text-brand-500">{{ __($label) }}</a>
                    @else
                        <a href="{{ $href }}"
                           class="py-2.5 px-3 rounded-lg text-sm font-medium
                                  text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/[0.05]">
                            {{ __($label) }}
                        </a>
                    @endif
                @endforeach
                <div class="mt-3 grid grid-cols-3 gap-2 border-t border-gray-100 dark:border-white/[0.08] pt-3">
                    <button onclick="rrSetTheme('light')" class="py-2 px-2 rounded-lg text-xs font-semibold text-[#0b1235] dark:text-gray-200 bg-gray-50 dark:bg-white/[0.05]">{{ __('Light') }}</button>
                    <button onclick="rrSetTheme('dark')" class="py-2 px-2 rounded-lg text-xs font-semibold text-[#0b1235] dark:text-gray-200 bg-gray-50 dark:bg-white/[0.05]">{{ __('Dark') }}</button>
                    <button class="py-2 px-2 rounded-lg text-xs font-semibold text-[#0b1235] dark:text-gray-200 bg-gray-50 dark:bg-white/[0.05]">{{ __('English') }}</button>
                </div>
                <a href="{{ route('landing') }}#download"
                   class="mt-2 py-3 px-3 rounded-xl text-sm font-semibold text-white bg-brand-500 text-center">
                    {{ __('Download App') }}
                </a>
            </div>
        </div>
    </div>
</header>

{{-- ══════════════════════════════════════════
     PAGE CONTENT
══════════════════════════════════════════ --}}
<div class="h-[74px]"></div>
@yield('content')

{{-- ══════════════════════════════════════════
     GLOBAL FOOTER
══════════════════════════════════════════ --}}
<footer class="bg-white dark:bg-[#090914]">

    {{-- ── Main footer columns ── --}}
    <div class="border-t border-[#e8ebf3] dark:border-white/[0.06]">
        <div class="max-w-[1120px] mx-auto px-4 sm:px-6 py-[34px]">
            <div class="flex flex-col lg:flex-row gap-10 lg:gap-0">

                {{-- Brand + social --}}
                <div class="lg:w-[245px] lg:pr-10 flex-shrink-0">
                    <a href="{{ route('landing') }}" class="flex items-center gap-3 mb-4">
                        @if ($pubLogo)
                            <img src="{{ $pubLogo }}" alt="{{ $pubBrand }}" class="h-9 w-auto">
                        @else
                            <div class="w-9 h-9 bg-[#563BFF] rounded-[9px] flex items-center justify-center shadow-[0_8px_18px_rgba(86,59,255,.2)]">
                                <span class="text-white font-bold text-[25px] leading-none tracking-[-0.08em]">{{ mb_substr($pubBrand, 0, 1) }}</span>
                            </div>
                            <span class="font-extrabold text-[22px] leading-none tracking-[-0.045em] text-[#0b1235] dark:text-white">{{ $pubBrand }}</span>
                        @endif
                    </a>
                    <p class="text-[13px] text-[#465272] dark:text-gray-400 leading-[1.9] mb-6 max-w-[205px] font-medium">
                        {{ siteText('shared', 'footer', 'tagline', 'Your trusted ride sharing partner. Fast, safe and reliable rides anytime, anywhere.') }}
                    </p>
                    <div class="flex gap-2.5">
                        @foreach([
                            ['facebook',  'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'],
                            ['twitter',   'M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z'],
                            ['instagram', 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z'],
                            ['linkedin',  'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'],
                        ] as [$platform, $path])
                        @php $pubSocialUrl = siteText('shared', 'social', $platform, '#'); @endphp
                        <a href="{{ $pubSocialUrl }}" @if($pubSocialUrl !== '#') target="_blank" rel="noopener" @endif class="w-9 h-9 bg-gray-100 dark:bg-white/[0.07] rounded-full flex items-center justify-center
                                          hover:bg-gray-200 dark:hover:bg-white/[0.12] transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="{{ $path }}"/>
                            </svg>
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Link columns --}}
                <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-y-8 sm:gap-y-0 sm:divide-x divide-[#e8ebf3] dark:divide-white/[0.06]
                            lg:border-l border-[#e8ebf3] dark:border-white/[0.06]">
                    @php
                        // Footer columns: heading editable per language; links are a CMS list
                        // ({label,url}) per column. Falls back to sensible defaults pre-seed.
                        $pubFooterCols = [
                            ['col_company_title', 'Company', 'footer_col_company', [['About Us', '#'], ['Careers', '#'], ['Blog', '#'], ['Press', '#'], ['Contact Us', '#']]],
                            ['col_rider_title', 'Rider', 'footer_col_rider', [['How It Works', route('landing') . '#how'], ['Safety', route('safety')], ['Ride Options', '#'], ['FAQ', route('help')], ['Customer Support', route('help')]]],
                            ['col_driver_title', 'Driver', 'footer_col_driver', [['Become a Driver', '#'], ['Driver Guide', '#'], ['Earnings', '#'], ['Safety', route('safety')], ['Driver Support', route('help')]]],
                            ['col_legal_title', 'Legal', 'footer_col_legal', [['Terms & Conditions', '#'], ['Privacy Policy', '#'], ['Cookie Policy', '#'], ['Refund Policy', '#']]],
                        ];
                    @endphp
                    @foreach($pubFooterCols as [$titleKey, $titleDefault, $listSection, $fallback])
                    @php $pubColLinks = siteList('shared', $listSection); @endphp
                    <div class="px-6 sm:px-8">
                        <h4 class="text-[13px] font-extrabold text-[#0b1235] dark:text-white mb-4">{{ siteText('shared', 'footer', $titleKey, $titleDefault) }}</h4>
                        <ul class="space-y-3">
                            @if (count($pubColLinks))
                                @foreach($pubColLinks as $link)
                                <li>
                                    <a href="{{ $link['url'] ?? '#' }}"
                                       class="text-[13px] font-medium text-[#465272] dark:text-gray-400
                                              hover:text-[#563BFF] dark:hover:text-white transition-colors">
                                        {{ $link['label'] ?? '' }}
                                    </a>
                                </li>
                                @endforeach
                            @else
                                @foreach($fallback as [$label, $href])
                                <li>
                                    <a href="{{ $href }}"
                                       class="text-[13px] font-medium text-[#465272] dark:text-gray-400
                                              hover:text-[#563BFF] dark:hover:text-white transition-colors">
                                        {{ __($label) }}
                                    </a>
                                </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── Bottom bar ── --}}
    <div class="border-t border-[#e8ebf3] dark:border-white/[0.06] bg-white dark:bg-white/[0.02]">
        <div class="max-w-[1120px] mx-auto px-4 sm:px-6">
            <div class="py-4 flex flex-col xl:grid xl:grid-cols-[1fr_auto_1fr] xl:items-center
                    gap-3 text-[13px] font-medium text-[#465272] dark:text-gray-400">

                {{-- Left: Copyright --}}
                <p class="text-center xl:text-left">{{ str_replace([':year', ':brand'], [date('Y'), $pubBrand], siteText('shared', 'footer', 'copyright', '© :year :brand. All rights reserved.')) }}</p>

                {{-- Centre: Language | Theme | Secure --}}
                <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-2">
                    <div class="pub-lang-wrap">
                        <button type="button" class="pub-lang-btn" aria-haspopup="true" aria-expanded="false">
                            @if ($publicV2CurrentLanguage)
                                @if ($publicV2CurrentLanguage->languagePicture)<img class="pub-lang-flag" src="{{ asset($publicV2CurrentLanguage->languagePicture) }}" alt="">@endif
                                <span>{{ $publicV2CurrentLanguage->title }}</span>
                            @else
                                <span>{{ __('English') }}</span>
                            @endif
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>

                        @if ($publicV2Languages->count() > 1)
                            <div class="pub-lang-menu">
                                @foreach ($publicV2Languages as $language)
                                    <a href="{{ route('public.change.language', $language->name) }}"
                                        class="{{ $language->name === $publicV2Locale ? 'is-active' : '' }}">
                                        @if ($language->languagePicture)<img class="pub-lang-flag" src="{{ asset($language->languagePicture) }}" alt="">@endif
                                        <span>{{ $language->title }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="hidden sm:block w-px h-4 bg-[#d9deea] dark:bg-white/20 flex-shrink-0"></div>
                    <button id="pub-light-btn" onclick="rrSetTheme('light')" class="flex items-center gap-2 hover:text-[#563BFF] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="5"/>
                            <path stroke-linecap="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                        </svg>
                        {{ __('Light Mode') }}
                    </button>
                    <div class="hidden sm:block w-px h-4 bg-[#d9deea] dark:bg-white/20 flex-shrink-0"></div>
                    <button id="pub-dark-btn" onclick="rrSetTheme('dark')" class="flex items-center gap-2 hover:text-[#563BFF] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                        </svg>
                        {{ __('Dark Mode') }}
                    </button>
                    <div class="hidden sm:block w-px h-4 bg-[#d9deea] dark:bg-white/20 flex-shrink-0"></div>
                    {{-- <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#0b1235] dark:text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Secure &amp; Encrypted
                    </div> --}}
                </div>

                {{-- Right: Payment icons (CMS image list; falls back to default badges) --}}
                @php $pubPayments = siteList('shared', 'footer_payments'); @endphp
                <div class="flex items-center justify-center xl:justify-end gap-2 flex-wrap">
                    @if (count($pubPayments))
                        @foreach ($pubPayments as $pay)
                            @php $payImg = !empty($pay['image']) ? (\Illuminate\Support\Str::startsWith($pay['image'], ['http://','https://']) ? $pay['image'] : \Illuminate\Support\Facades\Storage::url($pay['image'])) : null; @endphp
                            @if ($payImg)
                                <div class="h-7 px-2.5 bg-white border border-gray-200 rounded-md flex items-center shadow-sm">
                                    <img src="{{ $payImg }}" alt="{{ $pay['label'] ?? '' }}" class="h-5 w-auto object-contain">
                                </div>
                            @endif
                        @endforeach
                    @else
                        {{-- VISA --}}
                        <div class="h-7 px-2.5 bg-white border border-gray-200 rounded-md flex items-center shadow-sm">
                            <span class="text-[11px] font-black text-blue-700 tracking-tight italic">VISA</span>
                        </div>
                        {{-- Mastercard --}}
                        <div class="h-7 w-11 bg-white border border-gray-200 rounded-md flex items-center justify-center shadow-sm">
                            <div class="flex">
                                <div class="w-[18px] h-[18px] bg-red-500 rounded-full"></div>
                                <div class="w-[18px] h-[18px] bg-orange-400 rounded-full -ml-2.5"></div>
                            </div>
                        </div>
                        {{-- AMEX --}}
                        <div class="h-7 px-2 bg-[#016FD0] rounded-md flex items-center shadow-sm">
                            <span class="text-[10px] font-bold text-white tracking-tight">AMEX</span>
                        </div>
                        {{-- bKash --}}
                        <div class="h-7 px-2 bg-white border border-gray-200 rounded-md flex items-center gap-1 shadow-sm overflow-hidden">
                            <img src="{{ asset('assets/images/images.png') }}" alt="bKash" class="h-5 w-auto object-contain">
                            <span class="text-[12px] font-extrabold tracking-[-0.03em] leading-none">
                                <span class="text-[#E2136E]">b</span><span class="text-gray-900">Kash</span>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</footer>

{{-- ══════════════════════════════════════════
     SHARED SCRIPTS
══════════════════════════════════════════ --}}
<script>
    function rrSetTheme(theme) {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        localStorage.setItem('rr-theme', theme);
        rrSyncUI(theme);
    }

    function rrSyncUI(theme) {
        const isDark = theme === 'dark';

        document.getElementById('pub-light-btn')?.classList.toggle('text-[#563BFF]', !isDark);
        document.getElementById('pub-light-btn')?.classList.toggle('font-semibold', !isDark);
        document.getElementById('pub-dark-btn')?.classList.toggle('text-[#563BFF]', isDark);
        document.getElementById('pub-dark-btn')?.classList.toggle('font-semibold', isDark);
        document.getElementById('pub-head-light-btn')?.classList.toggle('text-[#563BFF]', !isDark);
        document.getElementById('pub-head-light-btn')?.classList.toggle('font-extrabold', !isDark);
        document.getElementById('pub-head-dark-btn')?.classList.toggle('text-[#563BFF]', isDark);
        document.getElementById('pub-head-dark-btn')?.classList.toggle('font-extrabold', isDark);
    }

    // Init on load
    rrSyncUI(localStorage.getItem('rr-theme') || 'light');

    // Mobile menu
    document.getElementById('pub-menu-btn')?.addEventListener('click', () => {
        document.getElementById('pub-mobile-menu')?.classList.toggle('open');
    });
    document.querySelectorAll('#pub-mobile-menu a').forEach(a => {
        a.addEventListener('click', () => {
            document.getElementById('pub-mobile-menu')?.classList.remove('open');
        });
    });

    document.querySelectorAll('.pub-lang-btn').forEach(button => {
        button.addEventListener('click', event => {
            event.stopPropagation();
            const wrap = button.closest('.pub-lang-wrap');
            wrap?.classList.toggle('is-open');
            button.setAttribute('aria-expanded', wrap?.classList.contains('is-open') ? 'true' : 'false');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.pub-lang-wrap.is-open').forEach(wrap => {
            wrap.classList.remove('is-open');
            wrap.querySelector('.pub-lang-btn')?.setAttribute('aria-expanded', 'false');
        });
    });

    function rrSyncActiveNav() {
        const hash = window.location.hash;
        if (hash !== '#features') return;

        document.querySelectorAll('[data-pub-nav]').forEach(link => {
            const isFeatures = link.dataset.pubNav === 'features';
            link.classList.toggle('text-[#563BFF]', isFeatures);
            link.classList.toggle('text-[#0b1235]', !isFeatures);
            link.classList.toggle('dark:text-gray-200', !isFeatures);
            link.classList.toggle('relative', isFeatures);
            link.classList.toggle('pb-0.5', isFeatures);
            link.classList.toggle('after:absolute', isFeatures);
            link.classList.toggle('after:bottom-[-27px]', isFeatures);
            link.classList.toggle('after:left-0', isFeatures);
            link.classList.toggle('after:right-0', isFeatures);
            link.classList.toggle('after:h-[2px]', isFeatures);
            link.classList.toggle('after:bg-[#563BFF]', isFeatures);
            link.classList.toggle('after:rounded-full', isFeatures);
        });
    }

    rrSyncActiveNav();
    window.addEventListener('hashchange', rrSyncActiveNav);
</script>

@yield('scripts')

</body>
</html>
