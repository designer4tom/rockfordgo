@extends('layouts.public')

@section('title', __('About Us') . ' - ' . siteBrand())
@section('active', 'about')

@section('styles')
<style>
    .about-shell {
        background: #ffffff;
        color: #121936;
        font-family: 'Plus Jakarta Sans', sans-serif;
        overflow: hidden;
    }
    .about-shell,
    .about-shell *,
    .about-shell *::before,
    .about-shell *::after { box-sizing: border-box; }
    .about-wrap {
        max-width: 1120px;
        width: 100%;
        margin: 0 auto;
        padding: 0 20px;
    }
    .about-shell h1,
    .about-shell h2,
    .about-shell h3,
    .about-shell p { letter-spacing: 0; }
    .about-hero { position: relative; overflow: hidden; padding: 34px 0 18px; }
    .about-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 78% 18%, rgba(124,58,237,.10), transparent 32%),
            linear-gradient(180deg, rgba(245,243,255,.72), rgba(255,255,255,0) 72%);
        pointer-events: none;
    }
    .about-hero-visual {
        position: relative;
        width: min(100%, 680px);
        min-height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: url("{{ asset('assets/images/v2/about-feature-hero-bg.png') }}") center bottom / contain no-repeat;
    }
    .about-hero-visual img {
        position: relative;
        z-index: 1;
        display: block;
        width: 100%;
        height: auto;
        filter: drop-shadow(0 24px 40px rgba(86,59,255,.10));
    }
    .about-card {
        background: #fff;
        border: 1px solid rgba(215,220,232,.28);
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(17,24,51,.05), 0 10px 28px rgba(17,24,51,.04);
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }
    .about-card:hover {
        transform: translateY(-4px);
        border-color: rgba(124,58,237,.18);
        box-shadow: 0 4px 16px rgba(124,58,237,.08), 0 18px 44px rgba(124,58,237,.08);
    }
    .icon-bubble {
        width: 50px;
        height: 50px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .value-icon {
        width: 30px;
        height: 30px;
        stroke-width: 1.8;
    }
    .tone-purple { background: #f1ecff; color: #6852ff; }
    .tone-green { background: #eafff2; color: #20c868; }
    .tone-orange { background: #fff1e8; color: #ff7a1a; }
    .tone-blue { background: #eef5ff; color: #2f7dff; }
    .stats-band {
        background: linear-gradient(90deg, rgba(245,243,255,.88), rgba(255,255,255,.82), rgba(245,243,255,.88));
        border: 1px solid rgba(215,210,250,.30);
        box-shadow: 0 1px 3px rgba(17,24,51,.04), 0 8px 24px rgba(17,24,51,.03);
        border-radius: 14px;
    }
    .team-avatar {
        width: 88px;
        height: 88px;
        border-radius: 999px;
        object-fit: cover;
        display: block;
        margin: 0 auto 16px;
        border: 3px solid rgba(124,58,237,.15);
        box-shadow: 0 4px 16px rgba(124,58,237,.12);
    }
    .about-pill {
        background: #f5f3ff;
        border: 1px solid rgba(219,214,254,.58);
        border-radius: 999px;
        color: #6d28d9;
        display: inline-flex;
        font-size: 11px;
        font-weight: 800;
        padding: 5px 13px;
    }
    .about-title {
        color: #0b1235;
        font-size: clamp(34px, 4.6vw, 48px);
        font-weight: 800;
        line-height: 1.16;
    }
    .section-title {
        color: #0b1235;
        font-size: 20px;
        font-weight: 800;
        line-height: 1.25;
    }
    .about-copy {
        color: #465272;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.9;
    }
    .card-title {
        color: #0b1235;
        font-size: 13px;
        font-weight: 800;
    }
    .card-copy {
        color: #4d5877;
        font-size: 11px;
        font-weight: 500;
        line-height: 1.75;
    }

    html.dark .about-shell {
        background:
            radial-gradient(ellipse 78% 44% at 66% 5%, rgba(86,59,255,.12) 0%, transparent 100%),
            radial-gradient(ellipse 52% 52% at 16% 82%, rgba(86,59,255,.07) 0%, transparent 100%),
            #090914;
    }
    html.dark .about-hero::before {
        background: radial-gradient(circle at 78% 18%, rgba(124,58,237,.08), transparent 32%);
    }
    html.dark .about-hero-visual {
        background:
            radial-gradient(circle at 68% 45%, rgba(86,59,255,.18) 0 170px, transparent 172px),
            radial-gradient(circle at 36% 70%, rgba(124,58,237,.10) 0 190px, transparent 192px);
    }
    html.dark .about-hero-visual img { filter: drop-shadow(0 24px 40px rgba(0,0,0,.38)) brightness(.92) saturate(.94); }
    html.dark .about-card {
        background: rgba(255,255,255,.038);
        border-color: rgba(255,255,255,.052);
        box-shadow: 0 1px 3px rgba(0,0,0,.18), 0 10px 28px rgba(0,0,0,.14);
    }
    html.dark .about-card:hover {
        border-color: rgba(167,139,250,.15);
        box-shadow: 0 4px 16px rgba(0,0,0,.22), 0 18px 44px rgba(0,0,0,.18);
    }
    html.dark .stats-band {
        background: rgba(255,255,255,.030);
        border-color: rgba(255,255,255,.05);
        box-shadow: none;
    }
    html.dark .team-avatar {
        border-color: rgba(167,139,250,.22);
        box-shadow: 0 4px 16px rgba(0,0,0,.28);
    }
    html.dark .about-pill {
        background: rgba(124,58,237,.12);
        border-color: rgba(167,139,250,.16);
        color: #c4b5fd;
    }
    html.dark .about-title,
    html.dark .section-title,
    html.dark .card-title { color: #f8fafc; }
    html.dark .about-copy,
    html.dark .card-copy { color: #94a3b8; }
    html.dark .tone-purple { background: rgba(104,82,255,.14); color: #9f91ff; }
    html.dark .tone-green { background: rgba(32,200,104,.13); color: #54e08d; }
    html.dark .tone-orange { background: rgba(255,122,26,.13); color: #ff9b55; }
    html.dark .tone-blue { background: rgba(47,125,255,.14); color: #78a8ff; }

    .about-cta-card {
        position: relative;
        overflow: hidden;
        margin-bottom: 34px;
        padding: 36px 48px;
        border-radius: 20px;
        background: #f3efff;
        border: 1px solid rgba(124,99,255,.14);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 26px;
        box-shadow: 0 2px 8px rgba(86,59,255,.06), 0 16px 40px rgba(86,59,255,.06);
    }
    .about-cta-card::after {
        content: '';
        position: absolute;
        inset: 0;
        opacity: .88;
        background-image: url("{{ asset('assets/images/v2/cta-skyline-generated.png') }}");
        background-repeat: no-repeat;
        background-position: bottom center;
        background-size: cover;
        pointer-events: none;
    }
    .about-cta-card > * { position: relative; z-index: 1; }
    .about-cta-title {
        color: #0b1235;
        font-size: 23px;
        font-weight: 800;
        margin: 0 0 10px;
    }
    .about-store-row { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 0; }
    .about-store-btn {
        display: inline-flex;
        align-items: center;
        gap: 11px;
        width: 166px;
        height: 52px;
        padding: 9px 16px;
        border-radius: 10px;
        background: transparent;
        border: 1.5px solid rgba(11,18,53,.2);
        color: #0b1235;
        text-decoration: none;
        transition: background .15s ease;
        max-width: 100%;
    }
    .about-store-btn:hover { background: rgba(11,18,53,.05); }
    .about-store-btn small { display: block; font-size: 9px; line-height: 1; color: rgba(11,18,53,.6); font-weight: 500; letter-spacing: .03em; text-transform: uppercase; }
    .about-store-btn strong { display: block; font-size: 17px; line-height: 1.1; color: #0b1235; font-weight: 700; letter-spacing: -.02em; margin-top: 2px; }

    html.dark .about-store-btn { border-color: rgba(255,255,255,.2); color: #fff; }
    html.dark .about-store-btn:hover { background: rgba(255,255,255,.06); }
    html.dark .about-store-btn small { color: rgba(255,255,255,.7); }
    html.dark .about-store-btn strong { color: #fff; }
    html.dark .about-cta-card { background: rgba(86,59,255,.10); border-color: rgba(167,139,250,.12); box-shadow: none; }
    html.dark .about-cta-card::after { opacity: .22; filter: brightness(.58) saturate(.72); }
    html.dark .about-cta-title { color: #f8fafc; }

    @media (max-width: 1023px) {
        .about-hero { padding-top: 32px; }
        .about-cta-card { padding: 28px 32px; }
    }
    @media (max-width: 640px) {
        .about-wrap { padding: 0 16px; }
        .about-hero-visual { min-height: 300px; }
        .about-cta-card { padding: 24px; align-items: flex-start; flex-direction: column; }
        .about-store-row { width: 100%; }
        .about-store-btn { flex: 1; min-width: 130px; }
    }
</style>
@endsection

@section('content')
<main class="about-shell">
    <section class="about-hero">
        <div class="about-wrap relative">
            <div class="grid lg:grid-cols-2 gap-10 xl:gap-16 items-center">
                <div>
                    <div class="about-pill mb-5">{{ siteText('about','hero','badge','About Us') }}</div>

                    <h1 class="about-title mb-5">
                        <span class="block text-gray-900">{{ siteText('about','hero','title_line1','Driven by Purpose.') }}</span>
                        <span class="block rr-gradient-text">{{ siteText('about','hero','title_line2','Built for You.') }}</span>
                    </h1>

                    <p class="about-copy max-w-md">
                        {{ siteText('about','hero','subtitle','ReadyRide was founded with a simple mission: to make urban travel safer, smarter and more convenient for everyone.') }}
                    </p>
                </div>

                <div class="flex justify-center lg:justify-end">
                    <div class="about-hero-visual">
                        <img src="{{ siteImage('about','hero','image','assets/images/v2/about-feature-hero-foreground.png') }}"
                             alt="{{ __('ReadyRide app preview with route map and white SUV') }}">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-8 sm:py-10">
        <div class="about-wrap">
            <div class="text-center max-w-xl mx-auto mb-7">
                <h2 class="section-title mb-3">{{ siteText('about','mission','title','Our Mission') }}</h2>
                <p class="about-copy">
                    {{ siteText('about','mission','body','To revolutionize the way people move by providing a reliable, affordable and safe ride-hailing experience powered by technology and care.') }}
                </p>
            </div>

            <div class="text-center mb-6">
                <h2 class="section-title">{{ siteText('about','values_head','title','Our Values') }}</h2>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @forelse(siteList('about','values') as $item)
                    <article class="about-card px-5 py-7 text-center">
                        <div class="icon-bubble {{ $item['tone'] ?? '' }} mx-auto mb-5">
                            <svg class="value-icon" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                @if(($item['icon'] ?? '') === 'shield')
                                    <path d="M12 3.5l7 2.6v5.2c0 4.35-2.82 8.25-7 9.55-4.18-1.3-7-5.2-7-9.55V6.1l7-2.6z"/>
                                    <path d="M9.3 12l1.8 1.8 3.8-4"/>
                                @elseif(($item['icon'] ?? '') === 'users')
                                    <circle cx="12" cy="8" r="3"/>
                                    <path d="M6.8 19a5.2 5.2 0 0110.4 0"/>
                                    <circle cx="18" cy="10" r="2"/>
                                    <path d="M18.8 17.4a3.8 3.8 0 00-2.2-3.15"/>
                                @elseif(($item['icon'] ?? '') === 'bulb')
                                    <path d="M9 18h6"/>
                                    <path d="M10 22h4"/>
                                    <path d="M8.4 14.8a6 6 0 117.2 0c-.75.55-1.1 1.2-1.1 2.2h-5c0-1-.35-1.65-1.1-2.2z"/>
                                    <path d="M12 2v1.5M20 10h1.5M2.5 10H4M18.2 3.8l-1.1 1.1M5.8 3.8l1.1 1.1"/>
                                @else
                                    <path d="M20.5 8.4c0 5.1-8.5 10.1-8.5 10.1S3.5 13.5 3.5 8.4A4.4 4.4 0 0111 5.3l1 1.1 1-1.1a4.4 4.4 0 017.5 3.1z"/>
                                @endif
                            </svg>
                        </div>
                        <h3 class="card-title mb-3">{{ $item['title'] ?? '' }}</h3>
                        <p class="card-copy">{{ $item['text'] ?? '' }}</p>
                    </article>
                @empty
                    @foreach([
                        ['Safety First', 'We prioritize your safety with verified drivers, real-time tracking and 24/7 support.', 'tone-purple', 'shield'],
                        ['Customer Focused', 'We listen, we care and we constantly improve to deliver the best experience for our riders.', 'tone-green', 'users'],
                        ['Innovation', 'We embrace technology and innovation to make every ride smarter and more efficient.', 'tone-orange', 'bulb'],
                        ['Trust & Integrity', 'We believe in transparent communication, fair pricing and building lasting relationships.', 'tone-blue', 'heart'],
                    ] as [$title, $text, $tone, $icon])
                        <article class="about-card px-5 py-7 text-center">
                            <div class="icon-bubble {{ $tone }} mx-auto mb-5">
                                <svg class="value-icon" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    @if($icon === 'shield')
                                        <path d="M12 3.5l7 2.6v5.2c0 4.35-2.82 8.25-7 9.55-4.18-1.3-7-5.2-7-9.55V6.1l7-2.6z"/>
                                        <path d="M9.3 12l1.8 1.8 3.8-4"/>
                                    @elseif($icon === 'users')
                                        <circle cx="12" cy="8" r="3"/>
                                        <path d="M6.8 19a5.2 5.2 0 0110.4 0"/>
                                        <circle cx="18" cy="10" r="2"/>
                                        <path d="M18.8 17.4a3.8 3.8 0 00-2.2-3.15"/>
                                    @elseif($icon === 'bulb')
                                        <path d="M9 18h6"/>
                                        <path d="M10 22h4"/>
                                        <path d="M8.4 14.8a6 6 0 117.2 0c-.75.55-1.1 1.2-1.1 2.2h-5c0-1-.35-1.65-1.1-2.2z"/>
                                        <path d="M12 2v1.5M20 10h1.5M2.5 10H4M18.2 3.8l-1.1 1.1M5.8 3.8l1.1 1.1"/>
                                    @else
                                        <path d="M20.5 8.4c0 5.1-8.5 10.1-8.5 10.1S3.5 13.5 3.5 8.4A4.4 4.4 0 0111 5.3l1 1.1 1-1.1a4.4 4.4 0 017.5 3.1z"/>
                                    @endif
                                </svg>
                            </div>
                            <h3 class="card-title mb-3">{{ __($title) }}</h3>
                            <p class="card-copy">{{ __($text) }}</p>
                        </article>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

    <section class="py-4 sm:py-6">
        <div class="about-wrap">
            <div class="stats-band rounded-[8px] px-5 sm:px-8 py-6">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    @forelse(siteList('about','stats') as $item)
                        <div class="flex items-center justify-center gap-3 lg:border-r lg:last:border-r-0 border-gray-200 dark:border-white/[0.08]">
                            <div class="w-11 h-11 rounded-full {{ $item['tone'] ?? '' }} flex items-center justify-center">
                                <svg class="w-5 h-5" fill="{{ ($item['icon'] ?? '') === 'star' ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    @if(($item['icon'] ?? '') === 'users')
                                        <circle cx="12" cy="8" r="3"/>
                                        <path d="M6.8 19a5.2 5.2 0 0110.4 0"/>
                                    @elseif(($item['icon'] ?? '') === 'car')
                                        <path d="M5 16h14l-1.45-5.1A2.6 2.6 0 0015.05 9h-6.1a2.6 2.6 0 00-2.5 1.9L5 16z"/>
                                        <path d="M7 16v2M17 16v2M8 18h8"/>
                                        <circle cx="7.5" cy="16.8" r=".8" fill="currentColor" stroke="none"/>
                                        <circle cx="16.5" cy="16.8" r=".8" fill="currentColor" stroke="none"/>
                                    @elseif(($item['icon'] ?? '') === 'pin')
                                        <path d="M12 21s6-5.2 6-11a6 6 0 10-12 0c0 5.8 6 11 6 11z"/>
                                        <circle cx="12" cy="10" r="2.2"/>
                                    @else
                                        <path stroke="none" d="M12 2.8l2.75 5.57 6.15.9-4.45 4.34 1.05 6.12L12 16.84l-5.5 2.89 1.05-6.12L3.1 9.27l6.15-.9L12 2.8z"/>
                                    @endif
                                </svg>
                            </div>
                            <div>
                                <div class="text-xl sm:text-2xl font-black text-gray-900 leading-tight">{{ $item['value'] ?? '' }}</div>
                                <p class="text-[11px] sm:text-xs font-semibold text-gray-500">{{ $item['label'] ?? '' }}</p>
                            </div>
                        </div>
                    @empty
                        @foreach([
                            ['1M+', 'Happy Riders', 'tone-purple', 'users'],
                            ['10M+', 'Rides Completed', 'tone-green', 'car'],
                            ['50+', 'Cities', 'tone-blue', 'pin'],
                            ['4.8', 'User Rating', 'tone-orange', 'star'],
                        ] as [$value, $label, $tone, $icon])
                            <div class="flex items-center justify-center gap-3 lg:border-r lg:last:border-r-0 border-gray-200 dark:border-white/[0.08]">
                                <div class="w-11 h-11 rounded-full {{ $tone }} flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="{{ $icon === 'star' ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        @if($icon === 'users')
                                            <circle cx="12" cy="8" r="3"/>
                                            <path d="M6.8 19a5.2 5.2 0 0110.4 0"/>
                                        @elseif($icon === 'car')
                                            <path d="M5 16h14l-1.45-5.1A2.6 2.6 0 0015.05 9h-6.1a2.6 2.6 0 00-2.5 1.9L5 16z"/>
                                            <path d="M7 16v2M17 16v2M8 18h8"/>
                                            <circle cx="7.5" cy="16.8" r=".8" fill="currentColor" stroke="none"/>
                                            <circle cx="16.5" cy="16.8" r=".8" fill="currentColor" stroke="none"/>
                                        @elseif($icon === 'pin')
                                            <path d="M12 21s6-5.2 6-11a6 6 0 10-12 0c0 5.8 6 11 6 11z"/>
                                            <circle cx="12" cy="10" r="2.2"/>
                                        @else
                                            <path stroke="none" d="M12 2.8l2.75 5.57 6.15.9-4.45 4.34 1.05 6.12L12 16.84l-5.5 2.89 1.05-6.12L3.1 9.27l6.15-.9L12 2.8z"/>
                                        @endif
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xl sm:text-2xl font-black text-gray-900 leading-tight">{{ $value }}</div>
                                    <p class="text-[11px] sm:text-xs font-semibold text-gray-500">{{ __($label) }}</p>
                                </div>
                            </div>
                        @endforeach
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="py-9 sm:py-12">
        <div class="about-wrap">
            <div class="text-center mb-8">
                <h2 class="section-title mb-2">{{ siteText('about','team_head','title','The People Behind ReadyRide') }}</h2>
                <p class="card-copy">{{ siteText('about','team_head','subtitle','A passionate team working every day to move you forward.') }}</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @forelse(siteList('about','team') as $item)
                    <article class="about-card p-5 text-center">
                        <img src="{{ \Illuminate\Support\Str::contains($item['img'] ?? '', '/') ? (\Illuminate\Support\Str::startsWith($item['img'], ['http://','https://']) ? $item['img'] : \Illuminate\Support\Facades\Storage::url($item['img'])) : asset('assets/images/v2/'.($item['img'] ?? 'man_image.png')) }}" alt="{{ $item['name'] ?? '' }}" class="team-avatar">
                        <h3 class="card-title mb-1">{{ $item['name'] ?? '' }}</h3>
                        <p class="card-copy mb-4">{{ $item['role'] ?? '' }}</p>
                        <a href="#" aria-label="{{ __(':name on LinkedIn', ['name' => $item['name'] ?? '']) }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-full
                                  bg-[#e8f3fb] text-[#0077B5] hover:bg-[#0077B5] hover:text-white
                                  transition-colors dark:bg-white/[0.07] dark:text-[#5bb5e8] dark:hover:bg-[#0077B5] dark:hover:text-white">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    </article>
                @empty
                    @foreach([
                        ['man_image.png', 'Rifat Hossain', 'Founder & CEO'],
                        ['woman.png', 'Nusrat Jahan', 'Head of Operations'],
                        ['man_image.png', 'Arifur Rahman', 'CTO'],
                        ['woman.png', 'Tanzila Islam', 'Head of Customer Experience'],
                    ] as [$img, $name, $role])
                        <article class="about-card p-5 text-center">
                            <img src="{{ asset('assets/images/v2/' . $img) }}" alt="{{ $name }}" class="team-avatar">
                            <h3 class="card-title mb-1">{{ $name }}</h3>
                            <p class="card-copy mb-4">{{ __($role) }}</p>
                            <a href="#" aria-label="{{ __(':name on LinkedIn', ['name' => $name]) }}"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-full
                                      bg-[#e8f3fb] text-[#0077B5] hover:bg-[#0077B5] hover:text-white
                                      transition-colors dark:bg-white/[0.07] dark:text-[#5bb5e8] dark:hover:bg-[#0077B5] dark:hover:text-white">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>
                        </article>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

    <section class="pb-10">
        <div class="about-wrap">
            <div class="about-cta-card">
                <div>
                    <h2 class="about-cta-title">{{ siteText('about','cta','title','Join Millions of Happy Riders') }}</h2>
                    <p class="about-copy" style="max-width:420px;margin:0;">{{ siteText('about','cta','subtitle','Download the ReadyRide app and enjoy a smarter, safer way to travel - anytime, anywhere.') }}</p>
                </div>
                <div class="about-store-row">
                    <a href="{{ siteText('shared','app_download','play_url','https://play.google.com/store/apps/details?id=com.readyrider.apps&client_id=1751005720.1778910176&session_id=1779684173') }}" class="about-store-btn" aria-label="{{ __('Get it on Google Play') }}">
                        <svg width="22" height="24" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
                            <path d="M1.5 0.6C1.18 0.78 1 1.18 1 1.72V24.28C1 24.82 1.18 25.22 1.5 25.4L1.6 25.49 13.87 13.22V12.78L1.6 0.51 1.5 0.6Z" fill="url(#ab_gp_a)"/>
                            <path d="M17.96 17.31L13.87 13.22V12.78L17.97 8.69L18.09 8.76L22.93 11.52C24.29 12.29 24.29 13.71 22.93 14.49L18.09 17.24 17.96 17.31Z" fill="url(#ab_gp_b)"/>
                            <path d="M18.09 17.24L13.87 13L1.5 25.4C1.96 25.88 2.7 25.94 3.53 25.47L18.09 17.24Z" fill="url(#ab_gp_c)"/>
                            <path d="M18.09 8.76L3.53 0.53C2.7 0.06 1.96 0.12 1.5 0.6L13.87 13 18.09 8.76Z" fill="url(#ab_gp_d)"/>
                            <defs>
                                <linearGradient id="ab_gp_a" x1="12.81" y1="1.7" x2="-4.84" y2="13" gradientUnits="userSpaceOnUse"><stop stop-color="#00A0FF"/><stop offset="1" stop-color="#00D2FF" stop-opacity=".01"/></linearGradient>
                                <linearGradient id="ab_gp_b" x1="25.18" y1="13" x2="0.63" y2="13" gradientUnits="userSpaceOnUse"><stop stop-color="#FFD500"/><stop offset="1" stop-color="#FFBC00"/></linearGradient>
                                <linearGradient id="ab_gp_c" x1="15.43" y1="15.57" x2="-5.63" y2="37.98" gradientUnits="userSpaceOnUse"><stop stop-color="#FF3A44"/><stop offset="1" stop-color="#C31162"/></linearGradient>
                                <linearGradient id="ab_gp_d" x1="-1.42" y1="-8.38" x2="8.72" y2="2.49" gradientUnits="userSpaceOnUse"><stop stop-color="#32A071"/><stop offset="1" stop-color="#2DA771" stop-opacity=".01"/></linearGradient>
                            </defs>
                        </svg>
                        <span>
                            <small>{{ __('Get it on') }}</small>
                            <strong>{{ __('Google Play') }}</strong>
                        </span>
                    </a>
                    <a href="{{ siteText('shared','app_download','app_url','https://testflight.apple.com/join/hRRvUR98?client_id=1751005720.1778910176&session_id=1779684173') }}" class="about-store-btn" aria-label="{{ __('Download on the App Store') }}">
                        <svg width="20" height="24" viewBox="0 0 24 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
                            <path d="M20.04 21.6c-.92 1.37-1.88 2.72-3.38 2.74-1.48.03-1.96-.87-3.65-.87-1.69 0-2.21.85-3.62.91-1.45.05-2.55-1.46-3.48-2.81C3.7 18.86 2.2 13.7 4.22 10.24c.97-1.69 2.7-2.75 4.57-2.78 1.42-.03 2.77.96 3.64.96.87 0 2.5-1.19 4.22-1.01.72.03 2.74.29 4.03 2.2-.1.07-2.41 1.42-2.38 4.23.03 3.35 2.94 4.47 2.97 4.48-.03.08-.47 1.6-1.23 3.28zM14.5 3.88c.81-.93 2.15-1.62 3.27-1.67.14 1.3-.38 2.61-1.15 3.54-.77.94-2.03 1.68-3.27 1.57-.17-1.28.46-2.61 1.15-3.44z" fill="currentColor"/>
                        </svg>
                        <span>
                            <small>{{ __('Download on the') }}</small>
                            <strong>{{ __('App Store') }}</strong>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
