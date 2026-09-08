@extends('layouts.public')

@section('title', __('ReadyRide - Get a Ride Anytime, Anywhere'))
@section('active', 'home')

@section('styles')
<style>
    .lp-page {
        background: #fff;
        color: #0b1235;
        font-family: 'Plus Jakarta Sans', sans-serif;
        overflow: hidden;
    }
    .lp-page,
    .lp-page *,
    .lp-page *::before,
    .lp-page *::after { box-sizing: border-box; }
    .lp-wrap { max-width: 1120px; width: 100%; margin: 0 auto; padding: 0 20px; }
    .lp-gradient-text {
        background: linear-gradient(135deg, #4f36ff 0%, #5f39ff 45%, #6f46ff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .lp-hero {
        position: relative;
        padding: 34px 0 46px;
        background:
            radial-gradient(circle at 70% 42%, rgba(86,59,255,.07) 0 128px, transparent 130px),
            linear-gradient(180deg, #fff 0%, #fff 68%, #fbfaff 100%);
    }
    .lp-hero::after {
        content: '';
        position: absolute;
        right: -38px;
        top: 140px;
        width: 270px;
        height: 270px;
        opacity: .45;
        background-image: radial-gradient(circle, #6f46ff 1.2px, transparent 1.2px);
        background-size: 12px 12px;
        pointer-events: none;
    }
    .lp-hero-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: .92fr 1.08fr;
        gap: 44px;
        align-items: center;
    }
    .lp-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 15px;
        border-radius: 999px;
        background: #f2edff;
        color: #563bff;
        font-size: 13px;
        font-weight: 800;
    }
    .lp-title {
        margin: 26px 0 18px;
        color: #0b1235;
        font-size: clamp(42px, 5.4vw, 54px);
        font-weight: 800;
        line-height: 1.12;
        letter-spacing: 0;
    }
    .lp-copy {
        max-width: 390px;
        color: #465272;
        font-size: 16px;
        font-weight: 500;
        line-height: 1.85;
        margin: 0;
    }
    .store-row { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 31px; }
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
    .trust-row {
        display: flex;
        flex-wrap: wrap;
        gap: 32px;
        margin-top: 74px;
    }
    .trust-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #0b1235;
        font-size: 14px;
        font-weight: 800;
    }
    .trust-item img { width: 18px; height: 18px; }
    .phone-stage {
        position: relative;
        display: flex;
        justify-content: center;
        min-height: 600px;
    }
    .phone-stage::before {
        content: '';
        position: absolute;
        width: 410px;
        max-width: calc(100vw - 32px);
        height: 410px;
        top: 90px;
        left: 50%;
        transform: translateX(-50%);
        border-radius: 999px;
        background: rgba(86,59,255,.055);
    }
    .hero-phone {
        position: relative;
        z-index: 1;
        width: min(100%, 352px);
        height: auto;
        filter: drop-shadow(0 28px 34px rgba(17,24,51,.16));
        transform: translateX(16px);
    }
    .hero-phone-dark { display: none; }
    html.dark .hero-phone-light { display: none; }
    html.dark .hero-phone-dark { display: block; }
    .section-head { text-align: center; margin-bottom: 28px; }
    .section-title {
        color: #0b1235;
        font-size: 29px;
        font-weight: 800;
        line-height: 1.2;
        margin: 0 0 9px;
    }
    .section-sub {
        color: #4c5877;
        font-size: 14px;
        font-weight: 500;
        margin: 0;
    }
    .features { padding: 12px 0 28px; }
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }
    .lp-card {
        border: 1px solid rgba(215,220,232,.28);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(17,24,51,.05), 0 10px 28px rgba(17,24,51,.04);
    }
    .feature-card {
        min-height: 178px;
        padding: 28px 20px 22px;
        text-align: center;
    }
    .feature-card img { width: 54px; height: 54px; margin: 0 auto 20px; }
    .card-title {
        color: #0b1235;
        font-size: 15px;
        font-weight: 800;
        margin: 0 0 9px;
    }
    .card-copy {
        color: #4c5877;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.65;
        margin: 0;
    }
    .stats-band {
        margin-top: 22px;
        padding: 23px 18px;
        border-radius: 14px;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        background: linear-gradient(90deg, #faf7ff 0%, #fff 48%, #faf7ff 100%);
        border-color: rgba(215,210,250,.30);
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
    .stat img { width: 45px; height: 45px; }
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
    .how { padding: 28px 0 38px; }
    .steps {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        position: relative;
    }
    .step {
        position: relative;
        text-align: center;
    }
    .step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 40px;
        right: -42px;
        width: 66px;
        height: 1px;
        background-image: linear-gradient(90deg, #6b7280 42%, transparent 0);
        background-size: 8px 1px;
        opacity: .8;
    }
    .step:not(:last-child)::before {
        content: '';
        position: absolute;
        top: 36px;
        right: -43px;
        width: 7px;
        height: 7px;
        border-top: 1px solid #6b7280;
        border-right: 1px solid #6b7280;
        transform: rotate(45deg);
        opacity: .8;
    }
    .step-icon { width: 74px; height: 74px; margin: 0 auto 12px; }
    .step-num {
        width: 24px;
        height: 24px;
        margin: 0 auto 14px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #563bff;
        color: #fff;
        font-size: 11px;
        font-weight: 800;
    }
    .cta-card {
        position: relative;
        overflow: hidden;
        margin-bottom: 34px;
        padding: 28px 42px;
        border-radius: 20px;
        background: #f3efff;
        border: 1px solid rgba(124,99,255,.14);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 26px;
        box-shadow: 0 2px 8px rgba(86,59,255,.06), 0 16px 40px rgba(86,59,255,.06);
    }
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
    .cta-card > * { position: relative; z-index: 1; }
    .cta-title {
        color: #0b1235;
        font-size: 23px;
        font-weight: 800;
        margin: 0 0 10px;
    }

    html.dark .lp-page {
        background:
            radial-gradient(ellipse 80% 48% at 70% 4%, rgba(86,59,255,.12) 0%, transparent 100%),
            radial-gradient(ellipse 52% 52% at 14% 84%, rgba(86,59,255,.07) 0%, transparent 100%),
            #090914;
        color: #f8fafc;
    }
    html.dark .lp-hero {
        background: radial-gradient(circle at 70% 42%, rgba(86,59,255,.13) 0 128px, transparent 130px);
    }
    html.dark .lp-hero::after { opacity: 0; }
    html.dark .step:not(:last-child)::after {
        background-image: linear-gradient(90deg, rgba(255,255,255,.22) 42%, transparent 0);
    }
    html.dark .step:not(:last-child)::before {
        border-color: rgba(255,255,255,.22);
    }
    html.dark .lp-pill { background: rgba(86,59,255,.18); color: #a78bfa; }
    html.dark .phone-stage {
        border-radius: 0;
        background: radial-gradient(circle at 50% 56%, rgba(69,45,255,.20) 0 178px, transparent 180px);
    }
    html.dark .phone-stage::before {
        width: 460px;
        height: 460px;
        top: 110px;
        background: rgba(86, 59, 255, .14);
        filter: blur(.2px);
    }
    html.dark .hero-phone {
        filter: drop-shadow(0 32px 42px rgba(0, 0, 0, .58));
    }
    html.dark .lp-title,
    html.dark .section-title,
    html.dark .card-title,
    html.dark .stat strong,
    html.dark .cta-title { color: #f8fafc; }
    html.dark .lp-copy,
    html.dark .section-sub,
    html.dark .card-copy,
    html.dark .stat span { color: #94a3b8; }
    html.dark .lp-card { background: rgba(255,255,255,.038); border-color: rgba(255,255,255,.052); box-shadow: 0 1px 3px rgba(0,0,0,.18), 0 10px 28px rgba(0,0,0,.14); }
    html.dark .stats-band { background: rgba(255,255,255,.030); border-color: rgba(255,255,255,.05); box-shadow: none; }
    html.dark .stat { border-color: rgba(255,255,255,.055); }
    html.dark .cta-card { background: rgba(86,59,255,.10); border-color: rgba(167,139,250,.12); box-shadow: none; }
    html.dark .cta-card::after {
        opacity: .22;
        filter: brightness(.58) saturate(.72);
    }
    html.dark .store-btn { border-color: rgba(255,255,255,.2); color: #fff; }
    html.dark .store-btn:hover { background: rgba(255,255,255,.06); }
    html.dark .store-btn small { color: rgba(255,255,255,.7); }
    html.dark .store-btn strong { color: #fff; }
    html.dark .trust-item { color: #e2e8f0; }

    @media (max-width: 1024px) {
        .lp-hero-grid { grid-template-columns: 1fr; gap: 26px; }
        .phone-stage { min-height: 420px; }
        .hero-phone { width: min(72vw, 300px); transform: none; }
        .trust-row { margin-top: 34px; }
        .feature-grid,
        .stats-band,
        .steps { grid-template-columns: repeat(2, 1fr); }
        .stat { border-right: 0; }
        .step::before,
        .step::after { display: none; }
    }
    @media (max-width: 640px) {
        .lp-wrap { padding: 0 16px; }
        .lp-hero { padding-top: 28px; }
        .lp-title { font-size: 36px; }
        .lp-title .whitespace-nowrap { white-space: normal; }
        .store-row { width: 100%; }
        .store-btn { flex: 1; min-width: 130px; }
        .feature-grid,
        .steps { grid-template-columns: 1fr; }
        .cta-card { padding: 24px; align-items: flex-start; flex-direction: column; }
        .phone-stage { min-height: 320px; }
        .hero-phone { width: min(72vw, 260px); }
    }
</style>
@endsection

@section('content')
<main class="lp-page">
    <section class="lp-hero">
        <div class="lp-wrap">
            <div class="lp-hero-grid">
                <div>
                    <div class="lp-pill">
                        <img src="{{ asset('assets/images/v2/landing-icons/sparkle-mini.svg') }}" alt="" width="18" height="18">
                        {{ __('Your Ride, Your Way') }}
                    </div>

                    <h1 class="lp-title">
                        <span class="block">{{ __('Get a Ride') }}</span>
                        <span class="lp-gradient-text block whitespace-nowrap">{{ __('Anytime, Anywhere') }}</span>
                    </h1>

                    <p class="lp-copy">{{ __('Book comfortable and affordable rides with trusted drivers in just a few taps.') }}</p>

                    <div class="store-row">
                        <a href="https://play.google.com/store/apps/details?id=com.readyrider.apps&client_id=1751005720.1778910176&session_id=1779684173" class="store-btn" aria-label="{{ __('Get it on Google Play') }}">
                            <svg width="22" height="24" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
                                <path d="M1.5 0.6C1.18 0.78 1 1.18 1 1.72V24.28C1 24.82 1.18 25.22 1.5 25.4L1.6 25.49 13.87 13.22V12.78L1.6 0.51 1.5 0.6Z" fill="url(#gp_a)"/>
                                <path d="M17.96 17.31L13.87 13.22V12.78L17.97 8.69L18.09 8.76L22.93 11.52C24.29 12.29 24.29 13.71 22.93 14.49L18.09 17.24 17.96 17.31Z" fill="url(#gp_b)"/>
                                <path d="M18.09 17.24L13.87 13L1.5 25.4C1.96 25.88 2.7 25.94 3.53 25.47L18.09 17.24Z" fill="url(#gp_c)"/>
                                <path d="M18.09 8.76L3.53 0.53C2.7 0.06 1.96 0.12 1.5 0.6L13.87 13 18.09 8.76Z" fill="url(#gp_d)"/>
                                <defs>
                                    <linearGradient id="gp_a" x1="12.81" y1="1.7" x2="-4.84" y2="13" gradientUnits="userSpaceOnUse"><stop stop-color="#00A0FF"/><stop offset="1" stop-color="#00D2FF" stop-opacity=".01"/></linearGradient>
                                    <linearGradient id="gp_b" x1="25.18" y1="13" x2="0.63" y2="13" gradientUnits="userSpaceOnUse"><stop stop-color="#FFD500"/><stop offset="1" stop-color="#FFBC00"/></linearGradient>
                                    <linearGradient id="gp_c" x1="15.43" y1="15.57" x2="-5.63" y2="37.98" gradientUnits="userSpaceOnUse"><stop stop-color="#FF3A44"/><stop offset="1" stop-color="#C31162"/></linearGradient>
                                    <linearGradient id="gp_d" x1="-1.42" y1="-8.38" x2="8.72" y2="2.49" gradientUnits="userSpaceOnUse"><stop stop-color="#32A071"/><stop offset="1" stop-color="#2DA771" stop-opacity=".01"/></linearGradient>
                                </defs>
                            </svg>
                            <span>
                                <small>{{ __('Get it on') }}</small>
                                <strong>{{ __('Google Play') }}</strong>
                            </span>
                        </a>
                        <a href="https://testflight.apple.com/join/hRRvUR98?client_id=1751005720.1778910176&session_id=1779684173" class="store-btn" aria-label="{{ __('Download on the App Store') }}">
                            <svg width="20" height="24" viewBox="0 0 24 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
                                <path d="M20.04 21.6c-.92 1.37-1.88 2.72-3.38 2.74-1.48.03-1.96-.87-3.65-.87-1.69 0-2.21.85-3.62.91-1.45.05-2.55-1.46-3.48-2.81C3.7 18.86 2.2 13.7 4.22 10.24c.97-1.69 2.7-2.75 4.57-2.78 1.42-.03 2.77.96 3.64.96.87 0 2.5-1.19 4.22-1.01.72.03 2.74.29 4.03 2.2-.1.07-2.41 1.42-2.38 4.23.03 3.35 2.94 4.47 2.97 4.48-.03.08-.47 1.6-1.23 3.28zM14.5 3.88c.81-.93 2.15-1.62 3.27-1.67.14 1.3-.38 2.61-1.15 3.54-.77.94-2.03 1.68-3.27 1.57-.17-1.28.46-2.61 1.15-3.44z" fill="currentColor"/>
                            </svg>
                            <span>
                                <small>{{ __('Download on the') }}</small>
                                <strong>{{ __('App Store') }}</strong>
                            </span>
                        </a>
                    </div>

                    <div class="trust-row">
                        <span class="trust-item"><img src="{{ asset('assets/images/v2/landing-icons/shield-mini.svg') }}" alt="">{{ __('Safe & Secure') }}</span>
                        <span class="trust-item"><img src="{{ asset('assets/images/v2/landing-icons/bolt-mini.svg') }}" alt="">{{ __('Quick Booking') }}</span>
                        <span class="trust-item"><img src="{{ asset('assets/images/v2/landing-icons/headset-mini.svg') }}" alt="">{{ __('24/7 Support') }}</span>
                    </div>
                </div>

                <div class="phone-stage">
                    <img class="hero-phone hero-phone-light" src="{{ asset('assets/images/v2/home_hero_phone_cutout.png') }}" alt="{{ __('ReadyRide app booking screen') }}">
                    <img class="hero-phone hero-phone-dark" src="{{ asset('assets/images/dark-mode-mobile.png') }}" alt="{{ __('ReadyRide app booking screen') }}">
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="features">
        <div class="lp-wrap">
            <div class="section-head">
                <h2 class="section-title">{{ __('Why Choose ReadyRide?') }}</h2>
                <p class="section-sub">{{ __('Enjoy the best ride experience with features designed for you.') }}</p>
            </div>

            <div class="feature-grid">
                @foreach([
                    ['fare.svg', 'Affordable Fares', 'Best prices for every ride. No hidden charges.'],
                    ['shield.svg', 'Safe & Trusted', 'Verified drivers and real-time trip tracking for your safety.'],
                    ['clock.svg', 'Quick & Easy', 'Book in seconds and reach your destination faster.'],
                    ['support.svg', '24/7 Support', "We're here to help you anytime, anywhere."],
                ] as [$icon, $title, $copy])
                    <article class="lp-card feature-card">
                        <img src="{{ asset('assets/images/v2/landing-icons/'.$icon) }}" alt="">
                        <h3 class="card-title">{{ __($title) }}</h3>
                        <p class="card-copy">{{ __($copy) }}</p>
                    </article>
                @endforeach
            </div>

            <div class="lp-card stats-band">
                @foreach([
                    ['users.svg', '1M+', 'Happy Riders'],
                    ['car.svg', '10M+', 'Rides Completed'],
                    ['pin.svg', '50+', 'Cities'],
                    ['star.svg', '4.8', 'User Rating'],
                ] as [$icon, $value, $label])
                    <div class="stat">
                        <img src="{{ asset('assets/images/v2/landing-icons/'.$icon) }}" alt="">
                        <div><strong>{{ $value }}</strong><span>{{ __($label) }}</span></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="how">
        <div class="lp-wrap">
            <div class="section-head">
                <h2 class="section-title">{{ __('How It Works') }}</h2>
                <p class="section-sub">{{ __('Getting your ride is simple and quick.') }}</p>
            </div>

            <div class="steps">
                @foreach([
                    ['pin.svg', 'Enter Location', 'Enter your pickup and drop-off location.'],
                    ['car.svg', 'Choose a Ride', 'Select the ride that suits you best.'],
                    ['card.svg', 'Confirm & Pay', 'Confirm your ride and choose a payment method.'],
                    ['flag.svg', 'Enjoy Your Ride', 'Track your driver and enjoy a comfortable ride.'],
                ] as $index => [$icon, $title, $copy])
                    <div class="step">
                        <img class="step-icon" src="{{ asset('assets/images/v2/landing-icons/'.$icon) }}" alt="">
                        <div class="step-num">{{ $index + 1 }}</div>
                        <h3 class="card-title">{{ __($title) }}</h3>
                        <p class="card-copy">{{ __($copy) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="download">
        <div class="lp-wrap">
            <div class="cta-card">
                <div>
                    <h2 class="cta-title">{{ __('Ready to Get Moving?') }}</h2>
                    <p class="lp-copy">{{ __('Download the ReadyRide app and enjoy a smarter way to travel.') }}</p>
                </div>
                <div class="store-row">
                    <a href="https://play.google.com/store/apps/details?id=com.readyrider.apps&client_id=1751005720.1778910176&session_id=1779684173" class="store-btn" aria-label="{{ __('Get it on Google Play') }}">
                        <svg width="22" height="24" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
                            <path d="M1.5 0.6C1.18 0.78 1 1.18 1 1.72V24.28C1 24.82 1.18 25.22 1.5 25.4L1.6 25.49 13.87 13.22V12.78L1.6 0.51 1.5 0.6Z" fill="url(#gp_a)"/>
                            <path d="M17.96 17.31L13.87 13.22V12.78L17.97 8.69L18.09 8.76L22.93 11.52C24.29 12.29 24.29 13.71 22.93 14.49L18.09 17.24 17.96 17.31Z" fill="url(#gp_b)"/>
                            <path d="M18.09 17.24L13.87 13L1.5 25.4C1.96 25.88 2.7 25.94 3.53 25.47L18.09 17.24Z" fill="url(#gp_c)"/>
                            <path d="M18.09 8.76L3.53 0.53C2.7 0.06 1.96 0.12 1.5 0.6L13.87 13 18.09 8.76Z" fill="url(#gp_d)"/>
                        </svg>
                        <span>
                            <small>{{ __('Get it on') }}</small>
                            <strong>{{ __('Google Play') }}</strong>
                        </span>
                    </a>
                    <a href="https://testflight.apple.com/join/hRRvUR98?client_id=1751005720.1778910176&session_id=1779684173" class="store-btn" aria-label="{{ __('Download on the App Store') }}">
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
