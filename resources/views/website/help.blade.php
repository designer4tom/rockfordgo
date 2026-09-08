@extends('layouts.public')

@section('title', __('Help Center') . ' - ' . siteBrand())
@section('active', 'help')

@section('styles')
    <style>
        .help-hero {
            position: relative;
            padding: 44px 0 48px;
            overflow: hidden;
            background:
                linear-gradient(90deg, rgba(255,255,255,.96) 0%, rgba(255,255,255,.86) 42%, rgba(255,255,255,.34) 100%),
                url("{{ asset('assets/images/v2/help-hero-bg-generated.png') }}") center right / cover no-repeat;
        }

        .help-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.22));
            pointer-events: none;
        }

        .help-hero::after { display: none; }

        .help-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: .86fr 1.14fr;
            gap: 46px;
            align-items: center;
        }

        .help-art-wrap {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .help-art-wrap::before { display: none; }

        .help-art {
            position: relative;
            z-index: 1;
            display: block;
            width: min(100%, 560px);
            height: auto;
            margin: 0 auto;
            filter: drop-shadow(0 18px 36px rgba(124,58,237,.10));
        }

        .help-search {
            position: relative;
            max-width: 445px;
            margin-top: 24px;
        }

        .help-search svg {
            position: absolute;
            left: 18px;
            top: 50%;
            width: 20px;
            height: 20px;
            color: #8b91a5;
            transform: translateY(-50%);
        }

        .help-search input {
            width: 100%;
            height: 52px;
            padding: 0 18px 0 50px;
            border: 1px solid rgba(219, 224, 236, .78);
            border-radius: 8px;
            background: #fff;
            color: var(--rr-navy);
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 8px 18px rgba(16, 23, 58, .08);
            outline: 0;
        }

        .help-search input::placeholder { color: #8b91a5; }

        .help-topics { padding: 12px 0 30px; }

        .help-section-left {
            margin-bottom: 24px;
        }

        .help-section-left .rr-section-title {
            text-align: left;
            font-size: 22px;
            margin-bottom: 7px;
        }

        .help-topic-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .help-topic-card {
            min-height: 190px;
            padding: 28px 22px 20px;
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .help-topic-card .rr-icon-disc {
            width: 62px;
            height: 62px;
            margin: 0 0 18px;
        }

        .help-topic-card .rr-card-title {
            font-size: 15px;
            margin-bottom: 9px;
        }

        .help-topic-card .rr-card-copy {
            font-size: 12px;
            line-height: 1.65;
        }

        .help-topic-arrow {
            width: 18px;
            height: 18px;
            margin-top: auto;
            color: #111833;
        }

        .help-faq { padding: 0 0 22px; }

        .help-faq-grid {
            display: grid;
            grid-template-columns: minmax(0, 540px) 360px;
            gap: 32px;
            align-items: start;
            justify-content: center;
        }

        .faq-title {
            color: var(--rr-navy);
            font-size: 20px;
            font-weight: 800;
            margin: 0 0 16px;
        }

        .faq-list {
            display: grid;
            gap: 9px;
        }

        .faq-hidden {
            display: none;
        }

        .faq-item {
            border: 1px solid rgba(215,220,232,.42);
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(17,24,51,.04);
            transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
        }

        .faq-item.open {
            border-color: rgba(86,59,255,.16);
            box-shadow: 0 8px 22px rgba(17,24,51,.055);
        }

        .faq-toggle {
            width: 100%;
            min-height: 42px;
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #465272;
            font-size: 13px;
            font-weight: 800;
            text-align: left;
            gap: 14px;
        }

        .faq-toggle:hover { background: #faf7ff; }

        .faq-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height .3s ease;
        }

        .faq-content.open { max-height: 140px; }

        .faq-content p {
            padding: 5px 16px 16px;
            color: #53607f;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.65;
        }

        .faq-chevron {
            width: 14px;
            height: 14px;
            color: #72788b;
            transition: transform .25s ease;
        }

        .faq-item.open .faq-chevron { transform: rotate(180deg); }

        .support-card {
            padding: 22px 22px 16px;
            border: 1px solid rgba(215,210,250,.28);
            border-radius: 14px;
            background: #f5f1ff;
            box-shadow: 0 1px 3px rgba(86,59,255,.05), 0 8px 24px rgba(86,59,255,.04);
        }

        .support-card h3 {
            color: var(--rr-navy);
            font-size: 16px;
            font-weight: 800;
            margin: 0 0 5px;
        }

        .support-card > p {
            color: var(--rr-body);
            font-size: 11px;
            font-weight: 500;
            margin: 0 0 16px;
        }

        .support-list {
            display: grid;
            gap: 9px;
            margin-bottom: 14px;
        }

        .support-row {
            min-height: 50px;
            padding: 9px 11px;
            border: 1px solid rgba(215,220,232,.25);
            border-radius: 10px;
            background: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 1px 3px rgba(17,24,51,.04);
        }

        .support-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: var(--rr-violet);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .support-icon svg {
            width: 18px;
            height: 18px;
        }

        .support-row strong {
            display: block;
            color: var(--rr-navy);
            font-size: 12px;
            font-weight: 800;
        }

        .support-row span {
            display: block;
            margin-top: 2px;
            color: #53607f;
            font-size: 10px;
            font-weight: 500;
        }

        .status-pill {
            margin-left: auto;
            padding: 3px 7px;
            border-radius: 999px;
            background: #eafbf2;
            color: #12a864;
            font-size: 9px;
            font-weight: 800;
            white-space: nowrap;
        }

        .help-safety-card {
            margin-bottom: 34px;
            padding: 22px 28px;
        }

        .help-safety-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 118px 1fr 1.08fr;
            gap: 24px;
            align-items: center;
        }

        .help-shield {
            width: 96px;
            height: 96px;
            margin: 0 auto;
            color: var(--rr-violet);
        }

        .tips-mini-list {
            display: grid;
            gap: 8px;
        }

        .tips-mini-list li {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #465272;
            font-size: 11px;
            font-weight: 700;
        }

        .tips-mini-list svg {
            width: 16px;
            height: 16px;
            color: var(--rr-violet);
            flex: 0 0 auto;
        }

        .help-faq .rr-outline-btn {
            height: 28px;
            padding: 0 20px;
            font-size: 11px;
        }

        .support-card .rr-primary-btn {
            height: 36px;
            font-size: 12px;
        }

        html.dark .help-hero {
            background:
                linear-gradient(180deg, rgba(9,9,20,.12) 0%, rgba(9,9,20,.42) 64%, #090914 100%),
                radial-gradient(circle at 83% 24%, rgba(92,64,255,.24) 0, rgba(92,64,255,.10) 28%, transparent 52%),
                linear-gradient(90deg, #090914 0%, rgba(20,18,34,.88) 40%, rgba(88,85,101,.58) 100%),
                url("{{ asset('assets/images/v2/help-hero-bg-generated.png') }}") center right / cover no-repeat;
        }
        html.dark .help-hero::before {
            background:
                linear-gradient(90deg, #090914 0%, rgba(9,9,20,.72) 30%, rgba(9,9,20,.08) 76%, rgba(92,64,255,.18) 100%),
                linear-gradient(180deg, transparent 0%, #090914 94%);
        }
        html.dark .help-hero::after { display: none; }
        html.dark .help-art {
            filter: drop-shadow(0 18px 36px rgba(0,0,0,.36)) brightness(.92);
        }
        html.dark .help-search input,
        html.dark .faq-item,
        html.dark .support-row {
            background: rgba(255,255,255,.038);
            border-color: rgba(255,255,255,.052);
            color: #f8fafc;
        }
        html.dark .faq-item {
            background: #151424;
            border-color: rgba(255,255,255,.075);
            box-shadow: 0 1px 3px rgba(0,0,0,.18);
        }
        html.dark .faq-item.open {
            background: #1a192a;
            border-color: rgba(167,139,250,.12);
            box-shadow: 0 12px 30px rgba(0,0,0,.22);
        }
        html.dark .support-row { box-shadow: 0 1px 3px rgba(0,0,0,.18); }
        html.dark .faq-title,
        html.dark .support-card h3,
        html.dark .support-row strong { color: #f8fafc; }
        html.dark .faq-toggle,
        html.dark .support-row span,
        html.dark .tips-mini-list li { color: #94a3b8; }
        html.dark .faq-content p { color: #b4bdd2; }
        html.dark .faq-toggle:hover { background: rgba(255,255,255,.05); }
        html.dark .help-topic-arrow { color: #94a3b8; }
        html.dark .status-pill { background: rgba(18,168,100,.15); color: #34d399; }
        html.dark .support-card { background: rgba(86,59,255,.10); border-color: rgba(167,139,250,.12); box-shadow: none; }

        @media (max-width: 1024px) {
            .help-hero-grid,
            .help-faq-grid,
            .help-safety-grid { grid-template-columns: 1fr; }
            .help-topic-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 640px) {
            .help-topic-grid { grid-template-columns: 1fr; }
            .support-card,
            .help-safety-card { padding: 24px; }
        }
    </style>
@endsection

@section('content')
    <main class="rr-page">
        <section class="help-hero">
            <div class="rr-wrap">
                <div class="help-hero-grid">
                    <div>
                        <div class="rr-pill">{{ siteText('help','hero','badge','Help Center') }}</div>
                        <h1 class="rr-hero-title">
                            <span class="block">{{ siteText('help','hero','title_line1','How can we') }}</span>
                            <span class="rr-gradient-text block">{{ siteText('help','hero','title_line2','help you?') }}</span>
                        </h1>
                        <p class="rr-copy">{{ siteText('help','hero','subtitle','Find answers, solve issues and get the support you need.') }}</p>

                        <div class="help-search">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>
                            <input type="text" placeholder="{{ siteText('help','hero','search_placeholder','Search for help articles...') }}">
                        </div>
                    </div>

                    <div class="help-art-wrap">
                        <img src="{{ siteImage('help','hero','image','assets/images/v2/help-hero-foreground.png') }}"
                             alt="{{ __('ReadyRide help center support illustration') }}"
                             class="help-art">
                    </div>
                </div>
            </div>
        </section>

        <section class="help-topics">
            <div class="rr-wrap">
                <div class="help-section-left">
                    <h2 class="rr-section-title">{{ siteText('help','topics_head','title','Browse Help Topics') }}</h2>
                    <p class="rr-section-sub">{{ siteText('help','topics_head','subtitle','Find answers to the most common questions.') }}</p>
                </div>

                <div class="help-topic-grid">
                    @forelse(siteList('help','topics') as $item)
                        <article class="rr-card help-topic-card">
                            <div class="rr-icon-disc {{ $item['tone'] }}">
                                @if($item['icon'] === 'card')
                                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                                @elseif($item['icon'] === 'chat')
                                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12c0 4.4-4 8-9 8a10 10 0 0 1-4.2-.9L3 20l1.4-3.7A7.2 7.2 0 0 1 3 12c0-4.4 4-8 9-8s9 3.6 9 8Z"/><path d="M8 12h.01M12 12h.01M16 12h.01"/></svg>
                                @else
                                    @include('partials.safety-icon', ['icon' => $item['icon']])
                                @endif
                            </div>
                            <h3 class="rr-card-title">{{ $item['title'] }}</h3>
                            <p class="rr-card-copy">{{ $item['copy'] }}</p>
                            <svg class="help-topic-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </article>
                    @empty
                        @foreach([
                            ['Getting Started', 'Learn how to sign up, book your first ride and more.', 'rr-tone-purple', 'users'],
                            ['Ride Options', 'Explore ride types, pricing and availability.', 'rr-tone-green', 'car'],
                            ['Payments & Billing', 'Learn about payments, promos and refunds.', 'rr-tone-blue', 'card'],
                            ['Safety & Security', 'Your safety is our priority. Learn more about our safety features.', 'rr-tone-orange', 'shield'],
                            ['Account & Profile', 'Manage your account, personal info and preferences.', 'rr-tone-purple', 'user'],
                            ['App & Technical', 'Troubleshoot app issues and technical problems.', 'rr-tone-blue', 'chat'],
                        ] as [$title, $copy, $tone, $icon])
                            <article class="rr-card help-topic-card">
                                <div class="rr-icon-disc {{ $tone }}">
                                    @if($icon === 'card')
                                        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                                    @elseif($icon === 'chat')
                                        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12c0 4.4-4 8-9 8a10 10 0 0 1-4.2-.9L3 20l1.4-3.7A7.2 7.2 0 0 1 3 12c0-4.4 4-8 9-8s9 3.6 9 8Z"/><path d="M8 12h.01M12 12h.01M16 12h.01"/></svg>
                                    @else
                                        @include('partials.safety-icon', ['icon' => $icon])
                                    @endif
                                </div>
                                <h3 class="rr-card-title">{{ __($title) }}</h3>
                                <p class="rr-card-copy">{{ __($copy) }}</p>
                                <svg class="help-topic-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </article>
                        @endforeach
                    @endforelse
                </div>
            </div>
        </section>

        <section class="help-faq">
            <div class="rr-wrap">
                <div class="help-faq-grid">
                    <div>
                        <h2 class="faq-title">{{ siteText('help','faq_head','title','Frequently Asked Questions') }}</h2>

                        @php
                            $faqItems = collect(siteList('help', 'faq'))->values();

                            if ($faqItems->isEmpty()) {
                                $faqItems = collect([
                                    [
                                        'question' => 'How do I book a ride?',
                                        'answer' => 'Open the app, enter your pickup location and destination, choose a ride type, confirm your booking and a driver will be assigned to you within minutes.',
                                    ],
                                    [
                                        'question' => 'How can I change or cancel my ride?',
                                        'answer' => 'You can change or cancel your ride from the My Rides section in the app before the driver arrives.',
                                    ],
                                    [
                                        'question' => 'What payment methods are accepted?',
                                        'answer' => 'ReadyRide accepts credit/debit cards, digital wallets and cash payments depending on your region.',
                                    ],
                                    [
                                        'question' => 'How do I share my trip details?',
                                        'answer' => 'Tap the Share Trip button during an active ride to send your real-time location and trip details to a contact.',
                                    ],
                                    [
                                        'question' => 'What should I do if I left something in the car?',
                                        'answer' => 'Go to your ride history, select the trip, and use the Lost Item feature to contact support.',
                                    ],
                                ]);
                            }
                        @endphp

                        <div class="faq-list" id="faqList">
                            @foreach($faqItems as $index => $item)
                                <div class="faq-item {{ $index >= 5 ? 'faq-hidden faq-extra-item' : '' }}">
                                    <button
                                        type="button"
                                        class="faq-toggle"
                                        aria-expanded="false"
                                        onclick="toggleFaq(this)"
                                    >
                                        <span>{{ __($item['question']) }}</span>
                                        <svg class="faq-chevron" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                                        </svg>
                                    </button>

                                    <div class="faq-content">
                                        <p>{{ __($item['answer']) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($faqItems->count() > 5)
                            <div class="mt-5 text-center" id="faqToggleWrapper">
                                <button
                                    type="button"
                                    id="faqToggleButton"
                                    class="rr-outline-btn"
                                    data-expanded="false"
                                    aria-expanded="false"
                                    aria-controls="faqList"
                                    onclick="toggleAllFaqs()"
                                >
                                    <span id="faqToggleButtonText">{{ __('View All FAQs') }}</span>
                                </button>
                            </div>
                        @endif
                    </div>

                    <aside class="support-card">
                        <h3>{{ siteText('help','support','title','Still Need Help?') }}</h3>
                        <p>{{ siteText('help','support','subtitle','Our support team is here for you 24/7.') }}</p>

                        <div class="support-list">
                            @forelse(siteList('help','support') as $item)
                                <div class="support-row">
                                    <div class="support-icon">
                                        @if($item['icon'] === 'chat')
                                            <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12c0 4.4-4 8-9 8a10 10 0 0 1-4.2-.9L3 20l1.4-3.7A7.2 7.2 0 0 1 3 12c0-4.4 4-8 9-8s9 3.6 9 8Z"/><path d="M8 12h.01M12 12h.01M16 12h.01"/></svg>
                                        @elseif($item['icon'] === 'mail')
                                            <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 6h16v12H4V6Z"/><path d="m4 7 8 6 8-6"/></svg>
                                        @else
                                            @include('partials.safety-icon', ['icon' => 'phone'])
                                        @endif
                                    </div>
                                    <div>
                                        <strong>{{ $item['title'] }}</strong>
                                        <span>{{ $item['copy'] }}</span>
                                    </div>
                                    @if(!empty($item['status']))
                                        <span class="status-pill">{{ $item['status'] }}</span>
                                    @else
                                        <svg class="ml-auto w-4 h-4 text-[#72788b]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/>
                                        </svg>
                                    @endif
                                </div>
                            @empty
                                @foreach([
                                    ['Live Chat', 'Chat with our support team', 'Online', 'chat'],
                                    ['Email Support', 'support@readyride.com', null, 'mail'],
                                    ['Call Us', '+1 (800) 123-4567', '24/7 Available', 'phone'],
                                ] as [$title, $copy, $status, $icon])
                                    <div class="support-row">
                                        <div class="support-icon">
                                            @if($icon === 'chat')
                                                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12c0 4.4-4 8-9 8a10 10 0 0 1-4.2-.9L3 20l1.4-3.7A7.2 7.2 0 0 1 3 12c0-4.4 4-8 9-8s9 3.6 9 8Z"/><path d="M8 12h.01M12 12h.01M16 12h.01"/></svg>
                                            @elseif($icon === 'mail')
                                                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 6h16v12H4V6Z"/><path d="m4 7 8 6 8-6"/></svg>
                                            @else
                                                @include('partials.safety-icon', ['icon' => 'phone'])
                                            @endif
                                        </div>
                                        <div>
                                            <strong>{{ __($title) }}</strong>
                                            <span>{{ __($copy) }}</span>
                                        </div>
                                        @if($status)
                                            <span class="status-pill">{{ __($status) }}</span>
                                        @else
                                            <svg class="ml-auto w-4 h-4 text-[#72788b]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/>
                                            </svg>
                                        @endif
                                    </div>
                                @endforeach
                            @endforelse
                        </div>

                        <button class="rr-primary-btn w-full">{{ siteText('help','support','button','Start a Conversation') }}</button>
                    </aside>
                </div>
            </div>
        </section>

        <section>
            <div class="rr-wrap">
                <div class="rr-cta-card help-safety-card">
                    <div class="help-safety-grid">
                        <svg class="help-shield" viewBox="0 0 80 90" fill="none" aria-hidden="true">
                            <path d="M40 2 7 16v29c0 22 15 38 33 45 18-7 33-23 33-45V16L40 2Z" fill="currentColor"/>
                            <path d="m26 44 9 9 20-18" stroke="white" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>

                        <div>
                            <h2 class="rr-cta-title">{{ siteText('help','tips','title','Safety Tips for a Better Ride') }}</h2>
                            <p class="rr-copy">{{ siteText('help','tips','subtitle','Follow these simple tips to ensure a safe and comfortable journey.') }}</p>
                            <a href="{{ route('safety') }}" class="rr-outline-btn mt-4">{{ siteText('help','tips','button','View Safety Tips') }}</a>
                        </div>

                        <ul class="tips-mini-list">
                            @forelse(siteList('help','tips') as $item)
                                <li>
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                                    </svg>
                                    <span>{{ $item['tip'] }}</span>
                                </li>
                            @empty
                                @foreach([
                                    'Share your trip with loved ones',
                                    'Verify your driver and car details',
                                    'Sit in the back seat',
                                    'Report any issues through the app',
                                ] as $tip)
                                    <li>
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                                        </svg>
                                        <span>{{ __($tip) }}</span>
                                    </li>
                                @endforeach
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

@section('scripts')
    <script>
        function toggleFaq(button) {
            const faqList = document.getElementById('faqList');

            if (!faqList) {
                return;
            }

            const currentItem = button.closest('.faq-item');
            const currentContent = currentItem?.querySelector('.faq-content');
            const isCurrentlyOpen = currentItem?.classList.contains('open');

            faqList.querySelectorAll('.faq-item').forEach((item) => {
                item.classList.remove('open');
                item.querySelector('.faq-content')?.classList.remove('open');
                item.querySelector('.faq-toggle')?.setAttribute('aria-expanded', 'false');
            });

            if (!isCurrentlyOpen && currentItem && currentContent) {
                currentItem.classList.add('open');
                currentContent.classList.add('open');
                button.setAttribute('aria-expanded', 'true');
            }
        }

        function toggleAllFaqs() {
            const faqList = document.getElementById('faqList');
            const toggleButton = document.getElementById('faqToggleButton');
            const toggleButtonText = document.getElementById('faqToggleButtonText');

            if (!faqList || !toggleButton || !toggleButtonText) {
                return;
            }

            const extraFaqItems = faqList.querySelectorAll('.faq-extra-item');
            const isExpanded = toggleButton.dataset.expanded === 'true';

            extraFaqItems.forEach((item) => {
                item.classList.toggle('faq-hidden', isExpanded);

                // Close hidden FAQ items when switching back to the first five.
                if (isExpanded) {
                    item.classList.remove('open');
                    item.querySelector('.faq-content')?.classList.remove('open');
                    item.querySelector('.faq-toggle')?.setAttribute('aria-expanded', 'false');
                }
            });

            const nextExpandedState = !isExpanded;

            toggleButton.dataset.expanded = String(nextExpandedState);
            toggleButton.setAttribute('aria-expanded', String(nextExpandedState));
            toggleButtonText.textContent = nextExpandedState
                ? @json(__('Show Less'))
                : @json(__('View All FAQs'));

            if (!nextExpandedState) {
                faqList.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            }
        }
    </script>
@endsection
