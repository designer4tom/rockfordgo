@extends('layouts.public')

@section('title', __('Features') . ' - ' . siteBrand())
@section('active', 'features')

@section('styles')
<style>
    .features-hero {
        position: relative;
        padding: 34px 0 24px;
        background: linear-gradient(180deg, #fff 0%, #fff 74%, #fbfaff 100%);
    }

    .features-hero::after {
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

    .features-hero-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: .86fr 1.14fr;
        gap: 38px;
        align-items: center;
    }

    .features-hero-visual {
        position: relative;
        width: min(100%, 600px);
        margin: 0 auto;
        min-height: 380px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: url("{{ asset('assets/images/v2/about-feature-hero-bg.png') }}") center bottom / contain no-repeat;
    }

    .features-hero-art {
        position: relative;
        z-index: 1;
        display: block;
        width: 100%;
        height: auto;
        filter: drop-shadow(0 24px 40px rgba(86, 59, 255, .12));
    }

    .features-section { padding: 12px 0 32px; }

    .features-grid-8 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .features-grid-8 .rr-card-center {
        min-height: 248px;
        padding: 34px 24px 28px;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .features-grid-8 .rr-card-center:hover {
        transform: translateY(-4px);
        border-color: rgba(86, 59, 255, .18);
        box-shadow: 0 18px 34px rgba(86, 59, 255, .08);
    }

    .features-grid-8 .rr-icon-disc {
        width: 82px;
        height: 82px;
        margin-bottom: 20px;
    }

    .features-grid-8 .rr-icon-disc svg,
    .features-grid-8 .rr-icon-disc img {
        width: 40px;
        height: 40px;
    }

    .features-grid-8 .rr-card-title { font-size: 16px; margin-bottom: 10px; }
    .features-grid-8 .rr-card-copy  { font-size: 13px; line-height: 1.7; }

    .features-stats { margin: 32px auto 28px; }

    html.dark .features-hero {
        background:
            radial-gradient(circle at 76% 38%, rgba(86, 59, 255, .10) 0 128px, transparent 130px),
            linear-gradient(180deg, #090914 0%, #101024 100%);
    }

    html.dark .features-hero::after { opacity: .12; }
    html.dark .features-hero-visual {
        background:
            radial-gradient(circle at 68% 45%, rgba(86,59,255,.18) 0 170px, transparent 172px),
            radial-gradient(circle at 36% 70%, rgba(124,58,237,.10) 0 190px, transparent 192px);
    }
    html.dark .features-hero-art {
        filter: drop-shadow(0 24px 40px rgba(0, 0, 0, .38)) brightness(.92) saturate(.94);
    }
    html.dark .features-grid-8 .rr-card-center:hover {
        border-color: rgba(167, 139, 250, .18);
        box-shadow: 0 18px 34px rgba(0, 0, 0, .22);
    }

    @media (max-width: 1024px) {
        .features-hero-grid { grid-template-columns: 1fr; gap: 20px; }
        .features-hero-visual { width: min(100%, 560px); }
        .features-grid-8 { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 640px) {
        .features-grid-8 { grid-template-columns: 1fr; }
        .features-hero-visual { min-height: 260px; }
    }
</style>
@endsection

@section('content')
<main class="rr-page">
    <section class="features-hero">
        <div class="rr-wrap">
            <div class="features-hero-grid">
                <div>
                    <div class="rr-pill">{{ siteText('features','hero','badge','Features') }}</div>
                    <h1 class="rr-hero-title">
                        <span class="block">{{ siteText('features','hero','title_line1','Smart Features for') }}</span>
                        <span class="rr-gradient-text block">{{ siteText('features','hero','title_line2','Better Rides') }}</span>
                    </h1>
                    <p class="rr-copy">
                        {{ siteText('features','hero','subtitle','ReadyRide is built with powerful features to make every ride safe, smooth and convenient for everyone.') }}
                    </p>
                </div>

                <div>
                    <div class="features-hero-visual">
                        <img src="{{ siteImage('features','hero','image','assets/images/v2/about-feature-hero-foreground.png') }}"
                             alt="{{ __('ReadyRide app booking interface with car') }}"
                             class="features-hero-art">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="rr-wrap">
            <div class="rr-section-head">
                <h2 class="rr-section-title">{{ siteText('features','grid_head','title','Everything You Need, All in One App') }}</h2>
                <p class="rr-section-sub">{{ siteText('features','grid_head','subtitle','Explore the powerful features that make ReadyRide your perfect travel partner.') }}</p>
            </div>

            <div class="features-grid-8">
                @forelse (siteList('features','features') as $item)
                    <article class="rr-card rr-card-center">
                        <div class="rr-icon-disc {{ $item['tone'] }}">
                            @if($item['icon'] === 'bolt')
                                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M13 2 5 14h6l-1 8 9-13h-6l1-7Z"/></svg>
                            @elseif($item['icon'] === 'card')
                                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                            @elseif($item['icon'] === 'support')
                                @include('partials.safety-icon', ['icon' => 'phone'])
                            @elseif($item['icon'] === 'document')
                                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7V3Z"/><path d="M14 3v5h5M10 12h6M10 16h6"/></svg>
                            @else
                                @include('partials.safety-icon', ['icon' => $item['icon']])
                            @endif
                        </div>
                        <h3 class="rr-card-title">{{ $item['title'] }}</h3>
                        <p class="rr-card-copy">{{ $item['copy'] }}</p>
                    </article>
                @empty
                @foreach([
                    ['Quick Booking', 'Book a ride in seconds with our simple and intuitive booking process.', 'rr-tone-purple', 'bolt'],
                    ['Safe & Secure', 'Verified drivers, live tracking and emergency support for your complete safety.', 'rr-tone-blue', 'shield'],
                    ['Real-time Tracking', 'Track your ride in real-time and share your trip details with family and friends.', 'rr-tone-green', 'pin'],
                    ['Multiple Payments', 'Pay your way with cash, card, mobile banking and digital wallets.', 'rr-tone-orange', 'card'],
                    ['24/7 Support', 'Our support team is always available to help you anytime, anywhere.', 'rr-tone-teal', 'support'],
                    ['Rate & Review', 'Rate your driver and share your experience to help us serve you better.', 'rr-tone-orange', 'star'],
                    ['Ride History', 'View your past rides, invoices and download receipts whenever you need.', 'rr-tone-purple', 'document'],
                    ['Smart Notifications', 'Get real-time updates about your rides, offers and important alerts.', 'rr-tone-pink', 'bell'],
                ] as [$title, $copy, $tone, $icon])
                    <article class="rr-card rr-card-center">
                        <div class="rr-icon-disc {{ $tone }}">
                            @if($icon === 'bolt')
                                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M13 2 5 14h6l-1 8 9-13h-6l1-7Z"/></svg>
                            @elseif($icon === 'card')
                                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                            @elseif($icon === 'support')
                                @include('partials.safety-icon', ['icon' => 'phone'])
                            @elseif($icon === 'document')
                                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7V3Z"/><path d="M14 3v5h5M10 12h6M10 16h6"/></svg>
                            @else
                                @include('partials.safety-icon', ['icon' => $icon])
                            @endif
                        </div>
                        <h3 class="rr-card-title">{{ __($title) }}</h3>
                        <p class="rr-card-copy">{{ __($copy) }}</p>
                    </article>
                @endforeach
                @endforelse
            </div>

            <div class="rr-stats-band features-stats">
                @forelse (siteList('features','stats') as $item)
                    <div class="rr-stat">
                        <div class="rr-icon-disc {{ $item['tone'] }}">
                            @include('partials.safety-icon', ['icon' => $item['icon']])
                        </div>
                        <div><strong>{{ $item['value'] }}</strong><span>{{ $item['label'] }}</span></div>
                    </div>
                @empty
                @foreach([
                    ['1M+', 'Happy Riders', 'rr-tone-purple', 'users'],
                    ['10M+', 'Rides Completed', 'rr-tone-green', 'car'],
                    ['50+', 'Cities', 'rr-tone-blue', 'pin'],
                    ['4.8', 'User Rating', 'rr-tone-orange', 'star'],
                ] as [$value, $label, $tone, $icon])
                    <div class="rr-stat">
                        <div class="rr-icon-disc {{ $tone }}">
                            @include('partials.safety-icon', ['icon' => $icon])
                        </div>
                        <div><strong>{{ $value }}</strong><span>{{ __($label) }}</span></div>
                    </div>
                @endforeach
                @endforelse
            </div>

            <section class="rr-cta-card">
                <div class="rr-cta-content">
                    <div>
                        <h2 class="rr-cta-title">{{ siteText('features','cta','title','Ready to Experience the Difference?') }}</h2>
                        <p class="rr-copy">{{ siteText('features','cta','subtitle','Download the ReadyRide app and enjoy a smarter way to travel.') }}</p>
                    </div>
                    <div class="rr-store-row">
                        @include('partials.store-buttons')
                    </div>
                </div>
            </section>
        </div>
    </section>
</main>
@endsection
