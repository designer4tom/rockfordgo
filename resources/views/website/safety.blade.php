@extends('layouts.public')

@section('title', __('Safety') . ' - ' . siteBrand())
@section('active', 'safety')

@section('styles')
<style>
    .safety-page {
        background: #fff;
        color: #0b1235;
        font-family: 'Plus Jakarta Sans', sans-serif;
        overflow: hidden;
    }
    .safety-page,
    .safety-page *,
    .safety-page *::before,
    .safety-page *::after { box-sizing: border-box; }
    .safety-wrap { max-width: 1120px; width: 100%; margin: 0 auto; padding: 0 20px; }
    .safety-gradient {
        background: linear-gradient(135deg, #4f36ff 0%, #5f39ff 45%, #6f46ff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .safety-hero {
        position: relative;
        padding: 34px 0 24px;
        background: linear-gradient(180deg, #fff 0%, #fff 75%, #fbfaff 100%);
    }
    .safety-hero::after {
        content: '';
        position: absolute;
        right: -38px;
        top: 138px;
        width: 270px;
        height: 270px;
        opacity: .42;
        background-image: radial-gradient(circle, #6f46ff 1.2px, transparent 1.2px);
        background-size: 12px 12px;
        pointer-events: none;
    }
    .hero-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: .86fr 1.14fr;
        gap: 38px;
        align-items: center;
    }
    .safety-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 15px;
        border-radius: 999px;
        background: #f2edff;
        color: #563bff;
        font-size: 13px;
        font-weight: 800;
    }
    .hero-title {
        margin: 26px 0 19px;
        color: #0b1235;
        font-size: clamp(42px, 5.2vw, 56px);
        font-weight: 800;
        line-height: 1.12;
        letter-spacing: 0;
    }
    .hero-copy {
        max-width: 410px;
        color: #465272;
        font-size: 16px;
        font-weight: 500;
        line-height: 1.9;
        margin: 0;
    }
    .hero-art {
        display: block;
        width: min(100%, 640px);
        height: auto;
        margin: 0 auto;
        filter: drop-shadow(0 24px 38px rgba(17,24,51,.12));
    }
    .section-head { text-align: center; margin-bottom: 26px; }
    .section-title {
        color: #0b1235;
        font-size: 29px;
        font-weight: 800;
        line-height: 1.2;
        margin: 0 0 10px;
    }
    .section-sub {
        color: #4c5877;
        font-size: 15px;
        font-weight: 500;
        margin: 0;
    }
    .safety-features { padding: 8px 0 28px; }
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 26px;
    }
    .soft-card {
        border: 1px solid rgba(215,220,232,.28);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(17,24,51,.05), 0 10px 28px rgba(17,24,51,.04);
    }
    .feature-card {
        min-height: 214px;
        padding: 26px 22px 24px;
        text-align: center;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .feature-card:hover {
        transform: translateY(-3px);
        border-color: rgba(86,59,255,.18);
        box-shadow: 0 4px 16px rgba(86,59,255,.07), 0 18px 34px rgba(86,59,255,.07);
    }
    .icon-disc {
        width: 64px;
        height: 64px;
        margin: 0 auto 18px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .icon-disc svg { width: 34px; height: 34px; stroke-width: 1.9; }
    .tone-purple { background: #f2edff; color: #563bff; }
    .tone-blue { background: #eef5ff; color: #157bff; }
    .tone-pink { background: #ffeaf3; color: #f42f78; }
    .tone-green { background: #eafbf2; color: #12c878; }
    .tone-orange { background: #fff1e8; color: #ff7816; }
    .tone-teal { background: #e9fbfb; color: #18aeb8; }
    .card-title {
        color: #0b1235;
        font-size: 15px;
        font-weight: 800;
        margin: 0 0 10px;
    }
    .card-copy {
        color: #4d5877;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.7;
        margin: 0;
    }
    .stats-band {
        margin: 32px auto 28px;
        padding: 23px 18px;
        border-radius: 14px;
        background: linear-gradient(90deg, #faf7ff 0%, #fff 48%, #faf7ff 100%);
        border: 1px solid rgba(215,210,250,.30);
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        box-shadow: 0 1px 3px rgba(17,24,51,.04), 0 8px 24px rgba(17,24,51,.03);
    }
    .stat {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 13px;
        border-right: 1px solid rgba(215,220,232,.30);
    }
    .stat:last-child { border-right: 0; }
    .stat .icon-disc { width: 56px; height: 56px; margin: 0; }
    .stat .icon-disc svg { width: 28px; height: 28px; }
    .stat strong {
        display: block;
        color: #0b1235;
        font-size: 24px;
        font-weight: 800;
        line-height: 1;
    }
    .stat span {
        display: block;
        margin-top: 5px;
        color: #465272;
        font-size: 12px;
        font-weight: 700;
    }
    .tips-card,
    .cta-card {
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        background: #f3efff;
        border: 1px solid rgba(124,99,255,.14);
        box-shadow: 0 2px 8px rgba(86,59,255,.06), 0 16px 40px rgba(86,59,255,.06);
    }
    .tips-card {
        margin-bottom: 24px;
        padding: 31px 36px 24px;
    }
    .tips-card::after,
    .cta-card::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        top: 0;
        height: auto;
        opacity: .88;
        background-image: url("{{ asset('assets/images/v2/cta-skyline-generated.png') }}");
        background-repeat: no-repeat;
        background-position: bottom center;
        background-size: cover;
        pointer-events: none;
    }
    .tips-top,
    .tips-list,
    .cta-content { position: relative; z-index: 1; }
    .tips-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 22px;
    }
    .tips-visual {
        width: min(38%, 300px);
        height: 118px;
        background-image: url("{{ asset('assets/images/v2/safety_hero.png') }}");
        background-size: contain;
        background-repeat: no-repeat;
        background-position: right bottom;
    }
    .tips-list {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 14px;
    }
    .tip {
        min-height: 48px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #0b1235;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.35;
        border-right: 1px solid rgba(86,59,255,.18);
    }
    .tip:last-child { border-right: 0; }
    .tip svg { flex: 0 0 auto; width: 28px; height: 28px; color: #563bff; stroke-width: 1.9; }
    .cta-card {
        margin-bottom: 34px;
        padding: 28px 42px;
    }
    .cta-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 26px;
    }
    .cta-title {
        color: #0b1235;
        font-size: 25px;
        font-weight: 800;
        margin: 0 0 9px;
    }
    .store-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .store-btn {
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
    .store-btn:hover { background: rgba(11,18,53,.05); }
    .store-btn small { display: block; font-size: 9px; line-height: 1; color: rgba(11,18,53,.6); font-weight: 500; letter-spacing: .03em; text-transform: uppercase; }
    .store-btn strong { display: block; font-size: 17px; line-height: 1.1; color: #0b1235; font-weight: 700; letter-spacing: -.02em; margin-top: 2px; }

    html.dark .safety-page {
        background:
            radial-gradient(ellipse 80% 48% at 70% 4%, rgba(86,59,255,.12) 0%, transparent 100%),
            radial-gradient(ellipse 52% 52% at 14% 84%, rgba(86,59,255,.07) 0%, transparent 100%),
            #090914;
        color: #f8fafc;
    }
    html.dark .safety-hero {
        background: radial-gradient(circle at 76% 38%, rgba(86,59,255,.13) 0 128px, transparent 130px);
    }
    html.dark .safety-hero::after { opacity: 0; }
    html.dark .safety-pill { background: rgba(86,59,255,.18); color: #a78bfa; }
    html.dark .hero-title,
    html.dark .section-title,
    html.dark .card-title,
    html.dark .stat strong,
    html.dark .cta-title { color: #f8fafc; }
    html.dark .hero-copy,
    html.dark .section-sub,
    html.dark .card-copy,
    html.dark .stat span { color: #94a3b8; }
    html.dark .soft-card { background: rgba(255,255,255,.038); border-color: rgba(255,255,255,.052); box-shadow: 0 1px 3px rgba(0,0,0,.18), 0 10px 28px rgba(0,0,0,.14); }
    html.dark .stats-band { background: rgba(255,255,255,.030); border-color: rgba(255,255,255,.05); box-shadow: none; }
    html.dark .stat,
    html.dark .tip { border-color: rgba(255,255,255,.055); }
    html.dark .store-btn { border-color: rgba(255,255,255,.2); color: #fff; }
    html.dark .store-btn:hover { background: rgba(255,255,255,.06); }
    html.dark .store-btn small { color: rgba(255,255,255,.7); }
    html.dark .store-btn strong { color: #fff; }
    html.dark .tips-card,
    html.dark .cta-card { background: rgba(86,59,255,.10); border-color: rgba(167,139,250,.12); box-shadow: none; }
    html.dark .tips-card::after,
    html.dark .cta-card::after {
        opacity: .22;
        filter: brightness(.58) saturate(.72);
    }
    html.dark .tip { color: #f8fafc; }
    html.dark .tone-purple { background: rgba(86,59,255,.18); color: #a78bfa; }
    html.dark .tone-blue   { background: rgba(21,123,255,.18); color: #73b4ff; }
    html.dark .tone-pink   { background: rgba(244,47,120,.18); color: #f87aab; }
    html.dark .tone-green  { background: rgba(18,200,120,.18); color: #52e0a4; }
    html.dark .tone-orange { background: rgba(255,120,22,.18); color: #ff9d5c; }
    html.dark .tone-teal   { background: rgba(24,174,184,.18); color: #5dd6de; }

    @media (max-width: 1024px) {
        .hero-grid { grid-template-columns: 1fr; gap: 20px; }
        .hero-art { width: min(100%, 560px); }
        .feature-grid,
        .stats-band { grid-template-columns: repeat(2, 1fr); }
        .stat { border-right: 0; }
        .tips-list { grid-template-columns: repeat(2, 1fr); }
        .tip { border-right: 0; }
    }
    @media (max-width: 640px) {
        .safety-wrap { padding: 0 16px; }
        .hero-title { font-size: 38px; }
        .feature-grid,
        .tips-list { grid-template-columns: 1fr; }
        .tips-card,
        .cta-card { padding: 24px; }
        .tips-top,
        .cta-content { align-items: flex-start; flex-direction: column; }
        .store-row { width: 100%; }
        .store-btn { flex: 1; min-width: 130px; }
        .tips-visual { width: 100%; background-position: left bottom; }
    }
</style>
@endsection

@section('content')
<main class="safety-page">
    <section class="safety-hero">
        <div class="safety-wrap">
            <div class="hero-grid">
                <div>
                    <div class="safety-pill">
                        <img src="{{ asset('assets/images/v2/landing-icons/shield-mini.svg') }}" alt="" width="18" height="18">
                        {{ siteText('safety','hero','badge','Safety First, Always') }}
                    </div>
                    <h1 class="hero-title">
                        <span class="block">{{ siteText('safety','hero','title_line1','Your Safety,') }}</span>
                        <span class="safety-gradient block">{{ siteText('safety','hero','title_line2','Our Priority') }}</span>
                    </h1>
                    <p class="hero-copy">
                        {{ siteText('safety','hero','subtitle','ReadyRide is committed to providing a safe, secure and reliable ride experience for every rider, every time.') }}
                    </p>
                </div>
                <div>
                    <img src="{{ siteImage('safety','hero','image','assets/images/v2/safety_hero.png') }}"
                         alt="{{ __('ReadyRide safety shield, car and app tracking preview') }}"
                         class="hero-art">
                </div>
            </div>
        </div>
    </section>

    <section id="safety-features" class="safety-features">
        <div class="safety-wrap">
            <div class="section-head">
                <h2 class="section-title">{{ siteText('safety','grid_head','title','How We Keep You Safe') }}</h2>
                <p class="section-sub">{{ siteText('safety','grid_head','subtitle','Advanced safety features and 24/7 support to give you peace of mind.') }}</p>
            </div>

            <div class="feature-grid">
                @forelse(siteList('safety','features') as $item)
                    <article class="soft-card feature-card">
                        <div class="icon-disc {{ $item['tone'] }}">
                            @include('partials.safety-icon', ['icon' => $item['icon']])
                        </div>
                        <h3 class="card-title">{{ $item['title'] }}</h3>
                        <p class="card-copy">{{ $item['copy'] }}</p>
                    </article>
                @empty
                    @foreach([
                        ['Verified Drivers', 'All drivers go through a strict verification process including ID, background and vehicle checks.', 'tone-purple', 'shield'],
                        ['Real-time Tracking', 'Share your trip in real-time with family and friends so they can track your journey.', 'tone-blue', 'pin'],
                        ['SOS Emergency', 'Tap the SOS button during your ride to instantly alert our 24/7 support team.', 'tone-pink', 'bell'],
                        ['Ride Check', 'We monitor your ride for unexpected stops or delays and take action if needed.', 'tone-green', 'user'],
                        ['In-app Calling', 'Call your driver or support team directly from the app for your convenience.', 'tone-orange', 'phone'],
                        ['Driver Ratings', 'Rate your driver and help us maintain a safe and friendly community.', 'tone-purple', 'star'],
                        ['Data Privacy', 'Your personal data is encrypted and never shared without your permission.', 'tone-teal', 'lock'],
                        ['Insurance Coverage', 'Every ride is covered with insurance for your safety and protection.', 'tone-blue', 'shield'],
                    ] as [$title, $copy, $tone, $icon])
                        <article class="soft-card feature-card">
                            <div class="icon-disc {{ $tone }}">
                                @include('partials.safety-icon', ['icon' => $icon])
                            </div>
                            <h3 class="card-title">{{ __($title) }}</h3>
                            <p class="card-copy">{{ __($copy) }}</p>
                        </article>
                    @endforeach
                @endforelse
            </div>

            <div class="stats-band">
                @forelse(siteList('safety','stats') as $item)
                    <div class="stat">
                        <div class="icon-disc {{ $item['tone'] }}">
                            @include('partials.safety-icon', ['icon' => $item['icon']])
                        </div>
                        <div><strong>{{ $item['value'] }}</strong><span>{{ $item['label'] }}</span></div>
                    </div>
                @empty
                    @foreach([
                        ['1M+', 'Happy Riders', 'tone-purple', 'users'],
                        ['10M+', 'Rides Completed', 'tone-green', 'car'],
                        ['50+', 'Cities', 'tone-blue', 'pin'],
                        ['4.8', 'User Rating', 'tone-orange', 'star'],
                    ] as [$value, $label, $tone, $icon])
                        <div class="stat">
                            <div class="icon-disc {{ $tone }}">
                                @include('partials.safety-icon', ['icon' => $icon])
                            </div>
                            <div><strong>{{ $value }}</strong><span>{{ __($label) }}</span></div>
                        </div>
                    @endforeach
                @endforelse
            </div>

            <section class="tips-card">
                <div class="tips-top">
                    <div>
                        <h2 class="cta-title">{{ siteText('safety','tips','title','Safety Tips for a Better Ride') }}</h2>
                        <p class="hero-copy">{{ siteText('safety','tips','subtitle','Follow these simple tips to have a safe and comfortable journey.') }}</p>
                    </div>
                    <div class="tips-visual" aria-hidden="true"></div>
                </div>

                <div class="tips-list">
                    @forelse(siteList('safety','tips') as $item)
                        <div class="tip">
                            @include('partials.safety-icon', ['icon' => $item['icon']])
                            <span>{{ $item['tip'] }}</span>
                        </div>
                    @empty
                        @foreach([
                            ['Share your trip with loved ones', 'shield'],
                            ['Verify your driver and vehicle details', 'users'],
                            ['Sit in the back seat', 'seat'],
                            ['Buckle up for safety', 'user'],
                            ['Report any issues through the app', 'phone'],
                        ] as [$tip, $icon])
                            <div class="tip">
                                @include('partials.safety-icon', ['icon' => $icon])
                                <span>{{ __($tip) }}</span>
                            </div>
                        @endforeach
                    @endforelse
                </div>
            </section>

            <section class="cta-card">
                <div class="cta-content">
                    <div>
                        <h2 class="cta-title">{{ siteText('safety','cta','title','Ready to Ride with Confidence?') }}</h2>
                        <p class="hero-copy">{{ siteText('safety','cta','subtitle','Download the ReadyRide app and enjoy a safe and secure ride experience.') }}</p>
                    </div>
                    <div class="store-row">
                        <a href="{{ siteText('shared','app_download','play_url','https://play.google.com/store/apps/details?id=com.readyrider.apps&client_id=1751005720.1778910176&session_id=1779684173') }}" class="store-btn" aria-label="{{ __('Get it on Google Play') }}">
                            <svg width="22" height="24" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
                                <path d="M1.5 0.6C1.18 0.78 1 1.18 1 1.72V24.28C1 24.82 1.18 25.22 1.5 25.4L1.6 25.49 13.87 13.22V12.78L1.6 0.51 1.5 0.6Z" fill="url(#sf_gp_a)"/>
                                <path d="M17.96 17.31L13.87 13.22V12.78L17.97 8.69L18.09 8.76L22.93 11.52C24.29 12.29 24.29 13.71 22.93 14.49L18.09 17.24 17.96 17.31Z" fill="url(#sf_gp_b)"/>
                                <path d="M18.09 17.24L13.87 13L1.5 25.4C1.96 25.88 2.7 25.94 3.53 25.47L18.09 17.24Z" fill="url(#sf_gp_c)"/>
                                <path d="M18.09 8.76L3.53 0.53C2.7 0.06 1.96 0.12 1.5 0.6L13.87 13 18.09 8.76Z" fill="url(#sf_gp_d)"/>
                                <defs>
                                    <linearGradient id="sf_gp_a" x1="12.81" y1="1.7" x2="-4.84" y2="13" gradientUnits="userSpaceOnUse"><stop stop-color="#00A0FF"/><stop offset="1" stop-color="#00D2FF" stop-opacity=".01"/></linearGradient>
                                    <linearGradient id="sf_gp_b" x1="25.18" y1="13" x2="0.63" y2="13" gradientUnits="userSpaceOnUse"><stop stop-color="#FFD500"/><stop offset="1" stop-color="#FFBC00"/></linearGradient>
                                    <linearGradient id="sf_gp_c" x1="15.43" y1="15.57" x2="-5.63" y2="37.98" gradientUnits="userSpaceOnUse"><stop stop-color="#FF3A44"/><stop offset="1" stop-color="#C31162"/></linearGradient>
                                    <linearGradient id="sf_gp_d" x1="-1.42" y1="-8.38" x2="8.72" y2="2.49" gradientUnits="userSpaceOnUse"><stop stop-color="#32A071"/><stop offset="1" stop-color="#2DA771" stop-opacity=".01"/></linearGradient>
                                </defs>
                            </svg>
                            <span>
                                <small>{{ __('Get it on') }}</small>
                                <strong>{{ __('Google Play') }}</strong>
                            </span>
                        </a>
                        <a href="{{ siteText('shared','app_download','app_url','https://testflight.apple.com/join/hRRvUR98?client_id=1751005720.1778910176&session_id=1779684173') }}" class="store-btn" aria-label="{{ __('Download on the App Store') }}">
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
            </section>
        </div>
    </section>
</main>
@endsection
