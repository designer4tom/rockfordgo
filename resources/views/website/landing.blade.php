@extends('layouts.public')

@section('title', siteBrand() . ' - ' . __('Get a Ride Anytime, Anywhere'))
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
        align-items: flex-start;
        min-height: 556px;
    }
    .phone-stage::before {
        content: '';
        position: absolute;
        width: 350px;
        max-width: calc(100vw - 32px);
        height: 350px;
        top: 78px;
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

    /* ---- hand-built animated device mockup ---- */
    .hero-device {
        position: relative;
        z-index: 1;
        width: 300px;
        margin-inline-start: 12px;
        border-radius: 46px;
        padding: 9px;
        background: linear-gradient(160deg, #232b3f, #0b0f1e 52%, #171e31);
        box-shadow:
            0 36px 64px -22px rgba(17,24,51,.38),
            0 10px 24px rgba(17,24,51,.14),
            inset 0 1px 1px rgba(255,255,255,.16);
        animation: rr-float 7s ease-in-out infinite;
    }
    .hero-screen {
        position: relative;
        border-radius: 37px;
        background: #f7f8fc;
        overflow: hidden;
        padding: 0 11px 11px;
    }
    .dev-island {
        position: absolute;
        top: 9px;
        left: 50%;
        transform: translateX(-50%);
        width: 84px;
        height: 21px;
        border-radius: 999px;
        background: #0b0f1e;
        z-index: 5;
    }
    .dev-status {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 11px 8px 5px;
        font-size: 11px;
        font-weight: 800;
        color: #0b1235;
    }
    .dev-status svg { display: block; color: #0b1235; }
    .dev-top {
        display: flex;
        justify-content: space-between;
        margin: 3px 1px 9px;
    }
    .dev-chip-btn {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid #eef0f6;
        display: grid;
        place-items: center;
        color: #0b1235;
        box-shadow: 0 2px 6px rgba(17,24,51,.05);
    }
    .dev-trip {
        position: relative;
        background: #fff;
        border: 1px solid #eef0f6;
        border-radius: 15px;
        padding: 10px 34px 10px 11px;
        box-shadow: 0 6px 16px rgba(17,24,51,.06);
    }
    .dev-stop { display: flex; gap: 9px; align-items: flex-start; }
    .dev-stop + .dev-stop { margin-top: 13px; }
    .dev-dot {
        position: relative;
        flex: none;
        width: 9px;
        height: 9px;
        border-radius: 999px;
        margin-top: 4px;
    }
    .dev-dot.green { background: #10b981; }
    .dev-dot.red { background: #ef4444; }
    .dev-dot.green::after {
        content: '';
        position: absolute;
        inset: -4px;
        border-radius: 999px;
        background: rgba(16,185,129,.28);
        animation: rr-ping 2.4s ease-out infinite;
    }
    .dev-stop small {
        display: block;
        font-size: 8.5px;
        letter-spacing: .3px;
        font-weight: 700;
        color: #72788b;
        margin-bottom: 1px;
    }
    .dev-stop strong { font-size: 11.5px; color: #0b1235; font-weight: 700; line-height: 1.25; }
    .dev-line {
        position: absolute;
        left: 15px;
        top: 32px;
        height: 20px;
        border-left: 2px dotted #cdd3e1;
    }
    .dev-add {
        position: absolute;
        right: 9px;
        top: 50%;
        transform: translateY(-50%);
        width: 25px;
        height: 25px;
        border-radius: 999px;
        border: 1px solid #e6e9f2;
        display: grid;
        place-items: center;
        color: #0b1235;
        font-weight: 700;
        font-size: 13px;
        background: #fff;
    }
    .dev-map {
        --m-bg1: #eaf1ea;
        --m-bg2: #e2ebf7;
        --m-park: #dcebd7;
        --m-road: #ffffff;
        position: relative;
        border-radius: 15px;
        overflow: hidden;
        margin-top: 9px;
        height: 148px;
        border: 1px solid #eef0f6;
    }
    .dev-map > svg { position: absolute; inset: 0; width: 100%; height: 100%; }
    .rr-route-dash { stroke-dasharray: 3 9; animation: rr-dash 1.5s linear infinite; }
    .rr-pin-pulse { transform-box: fill-box; transform-origin: center; animation: rr-pinpulse 2.4s ease-out infinite; }
    .dev-eta {
        position: absolute;
        left: 51%;
        top: 24%;
        background: #fff;
        color: #0b1235;
        font-size: 9.5px;
        font-weight: 800;
        line-height: 1.15;
        padding: 5px 9px;
        border-radius: 10px 10px 10px 3px;
        box-shadow: 0 5px 14px rgba(17,24,51,.14);
        white-space: nowrap;
    }
    .dev-car {
        position: absolute;
        left: 0;
        top: 0;
        width: 30px;
        height: 15px;
        offset-path: path('M50 32 C 90 58, 120 52, 148 76 S 194 104, 210 118');
        offset-rotate: auto;
        animation: rr-drive 7.5s cubic-bezier(.5,.06,.42,.95) infinite;
        filter: drop-shadow(0 3px 4px rgba(11,18,53,.3));
    }
    .dev-car svg { display: block; width: 100%; height: 100%; }
    .dev-rides { margin-top: 9px; display: flex; flex-direction: column; gap: 7px; }
    .dev-ride {
        display: flex;
        align-items: center;
        gap: 9px;
        background: #fff;
        border: 1.5px solid #eef0f6;
        border-radius: 14px;
        padding: 8px 11px;
    }
    .dev-ride.active {
        border-color: #563bff;
        background: #f6f4ff;
        box-shadow: 0 5px 16px rgba(86,59,255,.16);
    }
    .dev-ride .ic { flex: none; width: 37px; height: 21px; }
    .dev-ride-info { flex: 1; min-width: 0; }
    .dev-ride-info strong { display: block; font-size: 11.5px; font-weight: 800; color: #0b1235; }
    .dev-ride-info span {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 8.5px;
        font-weight: 600;
        color: #72788b;
    }
    .dev-ride-price { text-align: right; margin-right: 2px; }
    .dev-ride-price strong { display: block; font-size: 12px; font-weight: 800; color: #0b1235; }
    .dev-ride-price span { font-size: 8px; font-weight: 600; color: #72788b; }
    .dev-radio {
        flex: none;
        width: 15px;
        height: 15px;
        border-radius: 999px;
        border: 2px solid #cdd3e1;
    }
    .dev-ride.active .dev-radio {
        border-color: #563bff;
        background: radial-gradient(circle, #563bff 0 4px, transparent 4.5px);
    }
    .dev-cta {
        position: relative;
        overflow: hidden;
        margin-top: 10px;
        background: linear-gradient(135deg, #6d4dff, #4c2af8);
        color: #fff;
        text-align: center;
        font-weight: 800;
        font-size: 12.5px;
        padding: 11px;
        border-radius: 13px;
        box-shadow: 0 9px 20px rgba(86,59,255,.32);
    }
    .dev-cta::after {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        width: 45%;
        transform: translateX(-160%) skewX(-18deg);
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.34), transparent);
        animation: rr-sheen 4.6s ease-in-out infinite;
    }
    @keyframes rr-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    @keyframes rr-ping {
        0% { transform: scale(.55); opacity: .9; }
        75%, 100% { transform: scale(1.9); opacity: 0; }
    }
    @keyframes rr-drive {
        0% { offset-distance: 0%; opacity: 0; }
        7% { opacity: 1; }
        86% { offset-distance: 100%; opacity: 1; }
        93%, 100% { offset-distance: 100%; opacity: 0; }
    }
    @keyframes rr-dash { to { stroke-dashoffset: -12; } }
    @keyframes rr-pinpulse {
        0% { transform: scale(.5); opacity: .75; }
        80%, 100% { transform: scale(2.1); opacity: 0; }
    }
    @keyframes rr-sheen {
        0%, 58% { transform: translateX(-160%) skewX(-18deg); }
        88%, 100% { transform: translateX(340%) skewX(-18deg); }
    }
    @media (prefers-reduced-motion: reduce) {
        .hero-device,
        .dev-car,
        .dev-cta::after,
        .dev-dot.green::after,
        .rr-route-dash,
        .rr-pin-pulse { animation: none; }
        .dev-car { offset-distance: 58%; }
    }
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
    html.dark .hero-device {
        background: linear-gradient(160deg, #313a54, #05070f 52%, #1c2438);
        box-shadow:
            0 40px 70px -22px rgba(0,0,0,.72),
            0 10px 26px rgba(0,0,0,.4),
            inset 0 1px 1px rgba(255,255,255,.12);
    }
    html.dark .hero-screen { background: #0c1120; }
    html.dark .dev-status { color: #f1f5f9; }
    html.dark .dev-status svg { color: #f1f5f9; }
    html.dark .dev-chip-btn,
    html.dark .dev-trip,
    html.dark .dev-ride,
    html.dark .dev-add {
        background: #141a2e;
        border-color: rgba(255,255,255,.075);
        box-shadow: none;
    }
    html.dark .dev-chip-btn,
    html.dark .dev-add { color: #e2e8f0; }
    html.dark .dev-stop strong,
    html.dark .dev-ride-info strong,
    html.dark .dev-ride-price strong { color: #f1f5f9; }
    html.dark .dev-stop small,
    html.dark .dev-ride-info span,
    html.dark .dev-ride-price span { color: #8b93a8; }
    html.dark .dev-line { border-color: #33405e; }
    html.dark .dev-map {
        --m-bg1: #101726;
        --m-bg2: #0d1422;
        --m-park: #14213a;
        --m-road: #1e2b47;
        border-color: rgba(255,255,255,.075);
    }
    html.dark .dev-eta { background: #1b2237; color: #f1f5f9; box-shadow: 0 5px 14px rgba(0,0,0,.4); }
    html.dark .dev-ride.active {
        border-color: #6d4dff;
        background: rgba(86,59,255,.16);
        box-shadow: 0 5px 18px rgba(86,59,255,.22);
    }
    html.dark .dev-radio { border-color: #3a4763; }
    html.dark .dev-car .car-body { fill: #e8ecf5; }
    html.dark .dev-car .car-glass { fill: #55627e; }
    html.dark .dev-car { filter: drop-shadow(0 3px 5px rgba(0,0,0,.55)); }
    html.dark .dev-ride.active .dev-radio {
        border-color: #8b78ff;
        background: radial-gradient(circle, #8b78ff 0 4px, transparent 4.5px);
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
        .hero-device { zoom: .92; margin-inline-start: 0; }
        .phone-stage:has(.hero-device) { min-height: 520px; }
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
        .hero-device { zoom: .82; }
        .phone-stage:has(.hero-device) { min-height: 468px; }
        .phone-stage::before { width: 300px; height: 300px; top: 64px; }
    }
</style>
@endsection

@section('content')
@php
    $playUrl = siteText('shared', 'app_download', 'play_url', 'https://play.google.com/store/apps/details?id=com.readyrider.apps&client_id=1751005720.1778910176&session_id=1779684173');
    $appUrl  = siteText('shared', 'app_download', 'app_url', 'https://testflight.apple.com/join/hRRvUR98?client_id=1751005720.1778910176&session_id=1779684173');
    $homeTrust = siteList('home', 'trust_badges');
    $homeFeatures = siteList('home', 'features');
    $homeStats = siteList('home', 'stats');
    $homeSteps = siteList('home', 'how');
@endphp
<main class="lp-page">
    <section class="lp-hero">
        <div class="lp-wrap">
            <div class="lp-hero-grid">
                <div>
                    <div class="lp-pill">
                        <img src="{{ asset('assets/images/v2/landing-icons/sparkle-mini.svg') }}" alt="" width="18" height="18">
                        {{ siteText('home', 'hero', 'badge', 'Your Ride, Your Way') }}
                    </div>

                    <h1 class="lp-title">
                        <span class="block">{{ siteText('home', 'hero', 'title_line1', 'Get a Ride') }}</span>
                        <span class="lp-gradient-text block whitespace-nowrap">{{ siteText('home', 'hero', 'title_line2', 'Anytime, Anywhere') }}</span>
                    </h1>

                    <p class="lp-copy">{{ siteText('home', 'hero', 'subtitle', 'Book comfortable and affordable rides with trusted drivers in just a few taps.') }}</p>

                    <div class="store-row">
                        <a href="{{ $playUrl }}" class="store-btn" aria-label="{{ __('Get it on Google Play') }}">
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
                        <a href="{{ $appUrl }}" class="store-btn" aria-label="{{ __('Download on the App Store') }}">
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
                        @forelse ($homeTrust as $t)
                            <span class="trust-item"><img src="{{ asset('assets/images/v2/landing-icons/'.($t['icon'] ?? 'shield-mini.svg')) }}" alt="">{{ $t['label'] ?? '' }}</span>
                        @empty
                            <span class="trust-item"><img src="{{ asset('assets/images/v2/landing-icons/shield-mini.svg') }}" alt="">{{ __('Safe & Secure') }}</span>
                            <span class="trust-item"><img src="{{ asset('assets/images/v2/landing-icons/bolt-mini.svg') }}" alt="">{{ __('Quick Booking') }}</span>
                            <span class="trust-item"><img src="{{ asset('assets/images/v2/landing-icons/headset-mini.svg') }}" alt="">{{ __('24/7 Support') }}</span>
                        @endforelse
                    </div>
                </div>

                <div class="phone-stage" dir="ltr">
                    @if (siteText('home', 'hero', 'phone_light', '') !== '')
                        {{-- Admin uploaded a custom phone image from Website CMS — use it as before --}}
                        <img class="hero-phone hero-phone-light" src="{{ siteImage('home', 'hero', 'phone_light', 'assets/images/v2/home_hero_phone_cutout.png') }}" alt="{{ __('ReadyRide app booking screen') }}">
                        <img class="hero-phone hero-phone-dark" src="{{ siteImage('home', 'hero', 'phone_dark', 'assets/images/dark-mode-mobile.png') }}" alt="{{ __('ReadyRide app booking screen') }}">
                    @else
                        <div class="hero-device" role="img" aria-label="{{ __('ReadyRide app booking screen') }}">
                            <div class="hero-screen">
                                <span class="dev-island"></span>

                                <div class="dev-status">
                                    <span>9:41</span>
                                    <svg width="44" height="11" viewBox="0 0 44 11" fill="none" aria-hidden="true">
                                        <rect x="0" y="6" width="2.5" height="4" rx="1" fill="currentColor"/>
                                        <rect x="4" y="4" width="2.5" height="6" rx="1" fill="currentColor"/>
                                        <rect x="8" y="2" width="2.5" height="8" rx="1" fill="currentColor"/>
                                        <rect x="12" y="0" width="2.5" height="10" rx="1" fill="currentColor" opacity=".35"/>
                                        <path d="M20 4.2a6.4 6.4 0 0 1 8 0l-1.3 1.5a4.4 4.4 0 0 0-5.4 0L20 4.2Zm2.3 2.6a3 3 0 0 1 3.4 0L24 8.8l-1.7-2Z" fill="currentColor"/>
                                        <rect x="32" y="1.5" width="10" height="8" rx="2.4" stroke="currentColor" stroke-width="1" fill="none" opacity=".45"/>
                                        <rect x="33.4" y="2.9" width="6" height="5.2" rx="1.2" fill="currentColor"/>
                                        <path d="M43 4.4v2.2a1.4 1.4 0 0 0 0-2.2Z" fill="currentColor" opacity=".45"/>
                                    </svg>
                                </div>

                                <div class="dev-top">
                                    <span class="dev-chip-btn" aria-hidden="true">
                                        <svg width="13" height="11" viewBox="0 0 13 11" fill="none"><path d="M1 1.5h11M1 5.5h11M1 9.5h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                    </span>
                                    <span class="dev-chip-btn" aria-hidden="true">
                                        <svg width="12" height="13" viewBox="0 0 12 13" fill="none"><path d="M6 1a3.6 3.6 0 0 0-3.6 3.6c0 2.6-.9 3.6-1.4 4.2h10c-.5-.6-1.4-1.6-1.4-4.2A3.6 3.6 0 0 0 6 1Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M4.7 11a1.4 1.4 0 0 0 2.6 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                                    </span>
                                </div>

                                <div class="dev-trip">
                                    <span class="dev-line" aria-hidden="true"></span>
                                    <div class="dev-stop">
                                        <span class="dev-dot green"></span>
                                        <span><small>Pickup</small><strong>Mirpur 10, Dhaka</strong></span>
                                    </div>
                                    <div class="dev-stop">
                                        <span class="dev-dot red"></span>
                                        <span><small>Drop-off</small><strong>Gulshan 2, Dhaka</strong></span>
                                    </div>
                                    <span class="dev-add" aria-hidden="true">+</span>
                                </div>

                                <div class="dev-map">
                                    <svg viewBox="0 0 256 148" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                                        <defs>
                                            <linearGradient id="rrMapBg" x1="0" y1="0" x2="1" y2="1">
                                                <stop offset="0" stop-color="var(--m-bg1)"/>
                                                <stop offset="1" stop-color="var(--m-bg2)"/>
                                            </linearGradient>
                                        </defs>
                                        <rect width="256" height="148" fill="url(#rrMapBg)"/>
                                        <rect x="-14" y="-16" width="72" height="52" rx="12" fill="var(--m-park)"/>
                                        <rect x="196" y="8" width="76" height="46" rx="12" fill="var(--m-park)"/>
                                        <rect x="24" y="102" width="64" height="60" rx="12" fill="var(--m-park)"/>
                                        <g stroke="var(--m-road)" stroke-width="5" stroke-linecap="round" fill="none">
                                            <path d="M-6 52 H262"/>
                                            <path d="M-6 108 H262"/>
                                            <path d="M74 -6 V154"/>
                                            <path d="M170 -6 V154"/>
                                            <path d="M216 -6 V80"/>
                                        </g>
                                        <path d="M50 32 C 90 58, 120 52, 148 76 S 194 104, 210 118" stroke="#5b3dff" stroke-width="3.5" stroke-linecap="round" fill="none"/>
                                        <path class="rr-route-dash" d="M50 32 C 90 58, 120 52, 148 76 S 194 104, 210 118" stroke="rgba(255,255,255,.75)" stroke-width="1.4" stroke-linecap="round" fill="none"/>
                                        <circle class="rr-pin-pulse" cx="210" cy="118" r="9" fill="rgba(91,61,255,.35)"/>
                                        <circle cx="210" cy="118" r="5.5" fill="#5b3dff" stroke="#fff" stroke-width="2.4"/>
                                    </svg>
                                    <span class="dev-car" aria-hidden="true">
                                        <svg viewBox="0 0 30 15" fill="none">
                                            <rect class="car-body" x="1" y="1.5" width="28" height="12" rx="5.6" fill="#111827"/>
                                            <rect x="1" y="1.5" width="28" height="12" rx="5.6" stroke="rgba(255,255,255,.28)" stroke-width=".8"/>
                                            <rect class="car-glass" x="7.4" y="3" width="6.4" height="9" rx="2.2" fill="#3d4a63"/>
                                            <rect class="car-glass" x="17" y="3" width="5.4" height="9" rx="2.2" fill="#3d4a63"/>
                                            <rect x="25" y="4.4" width="2.6" height="6.2" rx="1.3" fill="#fbbf24"/>
                                        </svg>
                                    </span>
                                    <span class="dev-eta">3 min away</span>
                                </div>

                                <div class="dev-rides">
                                    @foreach ([
                                        ['name' => 'Economy', 'seats' => 4, 'eta' => '3 min away', 'price' => '120', 'body' => '#3b82f6', 'roof' => '#60a5fa', 'active' => true],
                                        ['name' => 'Comfort', 'seats' => 4, 'eta' => '5 min away', 'price' => '180', 'body' => '#8f9bad', 'roof' => '#b9c2d0', 'active' => false],
                                        ['name' => 'SUV', 'seats' => 6, 'eta' => '8 min away', 'price' => '240', 'body' => '#475569', 'roof' => '#64748b', 'active' => false],
                                    ] as $ride)
                                        <div class="dev-ride {{ $ride['active'] ? 'active' : '' }}">
                                            <svg class="ic" viewBox="0 0 37 21" fill="none" aria-hidden="true">
                                                <path d="M3 15.5 Q3 10.5 8.5 9.5 L12 4.5 Q13 3 15.5 3 L23.5 3 Q25.5 3 27 5 L30 9 Q34.5 10 34.5 13.5 L34.5 15 Q34.5 16.5 33 16.5 L4.5 16.5 Q3 16.5 3 15.5 Z" fill="{{ $ride['body'] }}"/>
                                                <path d="M13.5 5 L23 5 Q24.3 5 25.3 6.3 L27.2 9 L11 9 L13 5.6 Q13.2 5 13.5 5 Z" fill="{{ $ride['roof'] }}" opacity=".85"/>
                                                <circle cx="10" cy="16.5" r="3.2" fill="#1e293b"/><circle cx="10" cy="16.5" r="1.4" fill="#94a3b8"/>
                                                <circle cx="28" cy="16.5" r="3.2" fill="#1e293b"/><circle cx="28" cy="16.5" r="1.4" fill="#94a3b8"/>
                                            </svg>
                                            <span class="dev-ride-info">
                                                <strong>{{ $ride['name'] }}</strong>
                                                <span>
                                                    <svg width="8" height="8" viewBox="0 0 8 8" fill="currentColor" aria-hidden="true"><circle cx="4" cy="2.2" r="1.9"/><path d="M.6 8a3.4 3.4 0 0 1 6.8 0Z"/></svg>
                                                    {{ $ride['seats'] }} &nbsp;·&nbsp; {{ $ride['eta'] }}
                                                </span>
                                            </span>
                                            <span class="dev-ride-price"><strong>৳{{ $ride['price'] }}</strong></span>
                                            <span class="dev-radio" aria-hidden="true"></span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="dev-cta">Confirm Ride</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="features">
        <div class="lp-wrap">
            <div class="section-head">
                <h2 class="section-title">{{ siteText('home', 'features', 'title', 'Why Choose ReadyRide?') }}</h2>
                <p class="section-sub">{{ siteText('home', 'features', 'subtitle', 'Enjoy the best ride experience with features designed for you.') }}</p>
            </div>

            <div class="feature-grid">
                @forelse ($homeFeatures as $f)
                    <article class="lp-card feature-card">
                        <img src="{{ asset('assets/images/v2/landing-icons/'.($f['icon'] ?? 'fare.svg')) }}" alt="">
                        <h3 class="card-title">{{ $f['title'] ?? '' }}</h3>
                        <p class="card-copy">{{ $f['copy'] ?? '' }}</p>
                    </article>
                @empty
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
                @endforelse
            </div>

            <div class="lp-card stats-band">
                @forelse ($homeStats as $s)
                    <div class="stat">
                        <img src="{{ asset('assets/images/v2/landing-icons/'.($s['icon'] ?? 'star.svg')) }}" alt="">
                        <div><strong>{{ $s['value'] ?? '' }}</strong><span>{{ $s['label'] ?? '' }}</span></div>
                    </div>
                @empty
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
                @endforelse
            </div>
        </div>
    </section>

    <section id="how-it-works" class="how">
        <div class="lp-wrap">
            <div class="section-head">
                <h2 class="section-title">{{ siteText('home', 'how', 'title', 'How It Works') }}</h2>
                <p class="section-sub">{{ siteText('home', 'how', 'subtitle', 'Getting your ride is simple and quick.') }}</p>
            </div>

            <div class="steps">
                @php($homeStepsList = count($homeSteps) ? $homeSteps : [
                    ['icon' => 'pin.svg', 'title' => 'Enter Location', 'copy' => 'Enter your pickup and drop-off location.'],
                    ['icon' => 'car.svg', 'title' => 'Choose a Ride', 'copy' => 'Select the ride that suits you best.'],
                    ['icon' => 'card.svg', 'title' => 'Confirm & Pay', 'copy' => 'Confirm your ride and choose a payment method.'],
                    ['icon' => 'flag.svg', 'title' => 'Enjoy Your Ride', 'copy' => 'Track your driver and enjoy a comfortable ride.'],
                ])
                @foreach($homeStepsList as $index => $step)
                    <div class="step">
                        <img class="step-icon" src="{{ asset('assets/images/v2/landing-icons/'.($step['icon'] ?? 'pin.svg')) }}" alt="">
                        <div class="step-num">{{ $index + 1 }}</div>
                        <h3 class="card-title">{{ $step['title'] ?? '' }}</h3>
                        <p class="card-copy">{{ $step['copy'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="download">
        <div class="lp-wrap">
            <div class="cta-card">
                <div>
                    <h2 class="cta-title">{{ siteText('home', 'cta', 'title', 'Ready to Get Moving?') }}</h2>
                    <p class="lp-copy">{{ siteText('home', 'cta', 'subtitle', 'Download the ReadyRide app and enjoy a smarter way to travel.') }}</p>
                </div>
                <div class="store-row">
                    <a href="{{ $playUrl }}" class="store-btn" aria-label="{{ __('Get it on Google Play') }}">
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
                    <a href="{{ $appUrl }}" class="store-btn" aria-label="{{ __('Download on the App Store') }}">
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
