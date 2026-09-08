<style>
    :root {
        --rr-violet: #4c2af8;
        --rr-violet-2: #563bff;
        --rr-violet-hover: #3f22e6;
        --rr-navy: #0b1235;
        --rr-body: #465272;
        --rr-muted: #72788b;
        --rr-border: rgba(215, 220, 232, .28);
        --rr-border-soft: rgba(215, 210, 250, .30);
        --rr-border-violet: rgba(124, 99, 255, .14);
        --rr-dark-border: rgba(255, 255, 255, .052);
        --rr-dark-border-soft: rgba(167, 139, 250, .12);
        --rr-soft-violet: #f3efff;
        --rr-page: #ffffff;
        --rr-band: #faf7ff;
    }

    .rr-page,
    .rr-page *,
    .rr-page *::before,
    .rr-page *::after {
        box-sizing: border-box;
    }

    .rr-page {
        background: var(--rr-page);
        color: var(--rr-navy);
        font-family: 'Plus Jakarta Sans', sans-serif;
        overflow: hidden;
    }

    .rr-wrap {
        max-width: 1120px;
        margin: 0 auto;
        padding: 0 20px;
        width: 100%;
    }

    .rr-gradient-text {
        background: linear-gradient(135deg, #4f36ff 0%, #5f39ff 45%, #6f46ff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .rr-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 15px;
        border-radius: 999px;
        background: #f2edff;
        color: var(--rr-violet-2);
        font-size: 13px;
        font-weight: 800;
    }

    .rr-hero-title {
        margin: 26px 0 18px;
        color: var(--rr-navy);
        font-size: clamp(42px, 5.2vw, 56px);
        font-weight: 800;
        line-height: 1.12;
        letter-spacing: 0;
    }

    .rr-copy {
        max-width: 410px;
        color: var(--rr-body);
        font-size: 16px;
        font-weight: 500;
        line-height: 1.85;
        margin: 0;
    }

    .rr-section-head {
        text-align: center;
        margin-bottom: 26px;
    }

    .rr-section-title {
        color: var(--rr-navy);
        font-size: 29px;
        font-weight: 800;
        line-height: 1.2;
        margin: 0 0 10px;
    }

    .rr-section-sub {
        color: #4c5877;
        font-size: 14px;
        font-weight: 500;
        margin: 0;
    }

    .rr-card {
        border: 1px solid var(--rr-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(17,24,51,.05), 0 10px 28px rgba(17,24,51,.04);
    }

    .rr-feature-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }

    .rr-card-center {
        min-height: 178px;
        padding: 28px 20px 22px;
        text-align: center;
    }

    .rr-icon-disc {
        width: 54px;
        height: 54px;
        margin: 0 auto 20px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .rr-icon-disc svg,
    .rr-icon-disc img {
        width: 28px;
        height: 28px;
    }

    .rr-tone-purple { background: #f2edff; color: #563bff; }
    .rr-tone-blue { background: #eef5ff; color: #157bff; }
    .rr-tone-green { background: #eafbf2; color: #12c878; }
    .rr-tone-orange { background: #fff1e8; color: #ff7816; }
    .rr-tone-pink { background: #ffeaf3; color: #f42f78; }
    .rr-tone-teal { background: #e9fbfb; color: #18aeb8; }

    .rr-card-title {
        color: var(--rr-navy);
        font-size: 15px;
        font-weight: 800;
        margin: 0 0 9px;
    }

    .rr-card-copy {
        color: #4d5877;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.65;
        margin: 0;
    }

    .rr-stats-band {
        margin-top: 22px;
        padding: 23px 18px;
        border: 1px solid var(--rr-border-soft);
        border-radius: 14px;
        background: linear-gradient(90deg, #faf7ff 0%, #fff 48%, #faf7ff 100%);
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        box-shadow: 0 1px 3px rgba(17,24,51,.04), 0 8px 24px rgba(17,24,51,.03);
    }

    .rr-stat {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 13px;
        border-right: 1px solid rgba(215,220,232,.30);
    }

    .rr-stat:last-child { border-right: 0; }

    .rr-stat .rr-icon-disc {
        width: 45px;
        height: 45px;
        margin: 0;
    }

    .rr-stat strong {
        display: block;
        color: var(--rr-navy);
        font-size: 24px;
        font-weight: 800;
        line-height: 1;
    }

    .rr-stat span {
        display: block;
        margin-top: 5px;
        color: var(--rr-body);
        font-size: 12px;
        font-weight: 700;
    }

    .rr-store-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .rr-store-btn {
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
    .rr-store-btn:hover { background: rgba(11,18,53,.05); }

    .rr-store-btn small {
        display: block;
        font-size: 9px;
        line-height: 1;
        color: rgba(11,18,53,.6);
        font-weight: 500;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .rr-store-btn strong {
        display: block;
        font-size: 17px;
        line-height: 1.1;
        color: #0b1235;
        font-weight: 700;
        letter-spacing: -.025em;
        margin-top: 2px;
    }

    html.dark .rr-store-btn { border-color: rgba(255,255,255,.2); color: #fff; }
    html.dark .rr-store-btn:hover { background: rgba(255,255,255,.06); }
    html.dark .rr-store-btn small { color: rgba(255,255,255,.7); }
    html.dark .rr-store-btn strong { color: #fff; }

    .rr-cta-card {
        position: relative;
        overflow: hidden;
        margin-bottom: 34px;
        padding: 28px 42px;
        border-radius: 20px;
        background: var(--rr-soft-violet);
        border: 1px solid var(--rr-border-violet);
        box-shadow: 0 2px 8px rgba(86,59,255,.06), 0 16px 40px rgba(86,59,255,.06);
    }

    .rr-cta-card::after {
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

    .rr-cta-content {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 26px;
    }

    .rr-cta-title {
        color: var(--rr-navy);
        font-size: 23px;
        font-weight: 800;
        margin: 0 0 10px;
    }

    .rr-outline-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 32px;
        padding: 0 22px;
        border-radius: 6px;
        border: 1px solid var(--rr-violet-2);
        color: var(--rr-violet);
        background: #fff;
        font-size: 12px;
        font-weight: 800;
        transition: background-color .2s ease;
    }

    .rr-outline-btn:hover { background: #f2edff; }

    .rr-primary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        padding: 0 24px;
        border-radius: 7px;
        background: var(--rr-violet);
        color: #fff;
        font-size: 13px;
        font-weight: 800;
        transition: background-color .2s ease;
    }

    .rr-primary-btn:hover { background: var(--rr-violet-hover); }

    html.dark .rr-page {
        background:
            radial-gradient(ellipse 80% 48% at 70% 4%, rgba(86,59,255,.12) 0%, transparent 100%),
            radial-gradient(ellipse 52% 52% at 14% 84%, rgba(86,59,255,.07) 0%, transparent 100%),
            #090914;
        color: #f8fafc;
    }
    html.dark .rr-pill { background: rgba(86, 59, 255, .18); color: #a78bfa; }
    html.dark .rr-hero-title,
    html.dark .rr-section-title,
    html.dark .rr-card-title,
    html.dark .rr-stat strong,
    html.dark .rr-cta-title { color: #f8fafc; }
    html.dark .rr-copy,
    html.dark .rr-section-sub,
    html.dark .rr-card-copy,
    html.dark .rr-stat span { color: #94a3b8; }
    html.dark .rr-card { background: rgba(255,255,255,.038); border-color: rgba(255,255,255,.052); box-shadow: 0 1px 3px rgba(0,0,0,.18), 0 10px 28px rgba(0,0,0,.14); }
    html.dark .rr-stats-band { background: rgba(255,255,255,.030); border-color: rgba(255,255,255,.05); box-shadow: none; }
    html.dark .rr-stat { border-color: rgba(255,255,255,.055); }
    html.dark .rr-cta-card { background: rgba(86,59,255,.10); border-color: rgba(167,139,250,.12); }
    html.dark .rr-cta-card::after {
        opacity: .22;
        filter: brightness(.58) saturate(.72);
    }
    html.dark .rr-outline-btn { background: transparent; border-color: rgba(167, 139, 250, .72); color: #c4b5fd; }

    @media (max-width: 1024px) {
        .rr-feature-grid,
        .rr-stats-band { grid-template-columns: repeat(2, 1fr); }
        .rr-stat { border-right: 0; }
    }

    @media (max-width: 640px) {
        .rr-wrap { padding: 0 16px; }
        .rr-hero-title { font-size: 38px; }
        .rr-feature-grid { grid-template-columns: 1fr; }
        .rr-cta-card { padding: 24px; }
        .rr-cta-content {
            align-items: flex-start;
            flex-direction: column;
        }
        .rr-store-row { width: 100%; }
        .rr-store-btn { flex: 1; min-width: 130px; }
    }
</style>

{{--
    Shared motion + colour layer.
    Loaded on every public page, so the selector lists below deliberately cover
    all four class vocabularies in use: rr-* (features/help), lp-* (landing),
    safety-* and about-*.
--}}
<style>
    :root {
        --rr-cyan: #06b6d4;
        --rr-pink: #ec4899;
        --rr-amber: #f59e0b;
        --rr-emerald: #10b981;
        --rr-accent-grad: linear-gradient(90deg, #4c2af8, #7c3aed 32%, #ec4899 66%, #f59e0b);
    }

    /* ---------- ambient aurora ---------- */
    body::before,
    body::after {
        content: '';
        position: fixed;
        z-index: 0;
        pointer-events: none;
        border-radius: 50%;
        filter: blur(90px);
        opacity: .5;
    }
    body::before {
        width: 46vw;
        height: 46vw;
        max-width: 620px;
        max-height: 620px;
        top: -14vw;
        right: -10vw;
        background: radial-gradient(circle at 30% 30%, rgba(124,58,237,.30), transparent 68%),
                    radial-gradient(circle at 72% 68%, rgba(6,182,212,.22), transparent 66%);
        animation: rr-drift-a 26s ease-in-out infinite;
    }
    body::after {
        width: 40vw;
        height: 40vw;
        max-width: 540px;
        max-height: 540px;
        bottom: -12vw;
        left: -12vw;
        background: radial-gradient(circle at 40% 40%, rgba(236,72,153,.20), transparent 68%),
                    radial-gradient(circle at 68% 66%, rgba(245,158,11,.16), transparent 66%);
        animation: rr-drift-b 32s ease-in-out infinite;
    }
    html.dark body::before { opacity: .42; }
    html.dark body::after { opacity: .34; }

    @keyframes rr-drift-a {
        0%, 100% { transform: translate3d(0,0,0) scale(1); }
        50% { transform: translate3d(-4vw, 5vh, 0) scale(1.12); }
    }
    @keyframes rr-drift-b {
        0%, 100% { transform: translate3d(0,0,0) scale(1); }
        50% { transform: translate3d(5vw, -4vh, 0) scale(1.14); }
    }

    /* page shells sit above the aurora */
    .rr-page,
    .lp-page,
    .safety-page,
    .about-shell {
        position: relative;
        z-index: 1;
        background: transparent !important;
    }

    /* ---------- scroll progress ---------- */
    .rr-progress {
        position: fixed;
        top: 0;
        left: 0;
        height: 3px;
        width: 100%;
        transform: scaleX(var(--rr-p, 0));
        transform-origin: 0 50%;
        background: var(--rr-accent-grad);
        z-index: 60;
        pointer-events: none;
        will-change: transform;
    }

    /* ---------- scroll reveal (JS-gated so no-JS still shows content) ---------- */
    html.rr-motion [data-rr-r] {
        opacity: 0;
        transform: translateY(22px);
    }
    html.rr-motion [data-rr-r].rr-in {
        opacity: 1;
        transform: none;
        transition:
            opacity .62s cubic-bezier(.22,.68,.34,1) calc(var(--rr-i, 0) * 75ms),
            transform .62s cubic-bezier(.22,.68,.34,1) calc(var(--rr-i, 0) * 75ms);
    }

    /* ---------- proportion: section rhythm ----------
       Page stylesheets load after this partial and redefine several of these
       class names, so each rule below is doubled to out-specify them. */
    .features.features,
    .how.how,
    .safety-features.safety-features,
    .features-section.features-section,
    .help-topics.help-topics,
    .help-faq.help-faq { padding-block: clamp(46px, 6vw, 82px); }

    .rr-section-head.rr-section-head,
    .section-head.section-head { margin-bottom: clamp(30px, 3.6vw, 46px); }

    .rr-section-title.rr-section-title,
    .section-title.section-title {
        font-size: clamp(28px, 3.1vw, 39px);
        line-height: 1.16;
        letter-spacing: -.015em;
    }

    .rr-section-sub.rr-section-sub,
    .section-sub.section-sub {
        max-width: 560px;
        font-size: 15px;
        line-height: 1.7;
    }

    /* centre the sub only under a centred heading — help's section head is
       left-aligned and must keep its sub flush left */
    .rr-section-head .rr-section-sub,
    .section-head .section-sub { margin-inline: auto; }

    /* animated gradient rule under centred section headings */
    .rr-section-head,
    .section-head { position: relative; }
    .rr-section-head::after,
    .section-head::after {
        content: '';
        display: block;
        width: 62px;
        height: 3px;
        margin: 18px auto 0;
        border-radius: 999px;
        background: var(--rr-accent-grad);
        background-size: 220% 100%;
        animation: rr-hue 7s ease-in-out infinite;
    }
    @keyframes rr-hue {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    .rr-feature-grid.rr-feature-grid,
    .feature-grid.feature-grid,
    .features-grid-8.features-grid-8,
    .steps.steps,
    .help-topic-grid.help-topic-grid { gap: clamp(18px, 2vw, 24px); }

    /* ---------- cards: lift + colour wash ---------- */
    .rr-card,
    .lp-card,
    .soft-card,
    .about-card {
        position: relative;
        isolation: isolate;
        transition: transform .3s cubic-bezier(.22,.68,.34,1), box-shadow .3s ease, border-color .3s ease;
    }
    .rr-card::before,
    .lp-card::before,
    .soft-card::before,
    .about-card::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: -1;
        border-radius: inherit;
        opacity: 0;
        transition: opacity .3s ease;
        background: linear-gradient(150deg, rgba(124,58,237,.09), rgba(6,182,212,.07) 52%, rgba(236,72,153,.07));
    }
    .rr-card:hover,
    .lp-card:hover,
    .soft-card:hover,
    .about-card:hover {
        transform: translateY(-6px);
        border-color: rgba(124,99,255,.34);
        box-shadow: 0 3px 8px rgba(76,42,248,.07), 0 22px 46px -14px rgba(76,42,248,.24);
    }
    .rr-card:hover::before,
    .lp-card:hover::before,
    .soft-card:hover::before,
    .about-card:hover::before { opacity: 1; }

    html.dark .rr-card:hover,
    html.dark .lp-card:hover,
    html.dark .soft-card:hover,
    html.dark .about-card:hover {
        border-color: rgba(167,139,250,.34);
        box-shadow: 0 3px 10px rgba(0,0,0,.3), 0 24px 50px -16px rgba(86,59,255,.34);
    }

    /* stats band shouldn't lift — it is a full-width strip, not a card */
    .rr-card.rr-stats-band:hover,
    .lp-card.stats-band:hover {
        transform: none;
        box-shadow: 0 1px 3px rgba(17,24,51,.04), 0 8px 24px rgba(17,24,51,.03);
    }
    .rr-card.rr-stats-band::before,
    .lp-card.stats-band::before { content: none; }

    /* ---------- icon discs: richer colour + motion ---------- */
    .rr-icon-disc,
    .step-icon {
        position: relative;
        transition: transform .35s cubic-bezier(.34,1.42,.44,1), box-shadow .3s ease;
    }
    .rr-tone-purple { background: linear-gradient(140deg, #efe8ff, #e2d7ff); color: #563bff; }
    .rr-tone-blue { background: linear-gradient(140deg, #e8f2ff, #d7e8ff); color: #157bff; }
    .rr-tone-green { background: linear-gradient(140deg, #e4fbf0, #d2f6e5); color: #12c878; }
    .rr-tone-orange { background: linear-gradient(140deg, #fff0e4, #ffe3cd); color: #ff7816; }
    .rr-tone-pink { background: linear-gradient(140deg, #ffe8f1, #ffd7e7); color: #f42f78; }
    .rr-tone-teal { background: linear-gradient(140deg, #e2fafa, #cef3f4); color: #18aeb8; }

    .rr-card:hover .rr-icon-disc,
    .lp-card:hover .rr-icon-disc,
    .soft-card:hover .rr-icon-disc,
    .about-card:hover .rr-icon-disc,
    .step:hover .step-icon {
        transform: translateY(-3px) scale(1.09) rotate(-5deg);
        box-shadow: 0 10px 22px -8px currentColor;
    }

    /* ---------- pills shimmer ---------- */
    .rr-pill,
    .lp-pill,
    .safety-pill,
    .about-pill {
        position: relative;
        overflow: hidden;
    }
    .rr-pill::after,
    .lp-pill::after,
    .safety-pill::after,
    .about-pill::after {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        width: 42%;
        transform: translateX(-180%) skewX(-18deg);
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.62), transparent);
        animation: rr-pill-sheen 5.4s ease-in-out infinite;
    }
    html.dark .rr-pill::after,
    html.dark .lp-pill::after,
    html.dark .safety-pill::after,
    html.dark .about-pill::after {
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.16), transparent);
    }
    @keyframes rr-pill-sheen {
        0%, 62% { transform: translateX(-180%) skewX(-18deg); }
        92%, 100% { transform: translateX(360%) skewX(-18deg); }
    }

    /* ---------- buttons ---------- */
    .rr-primary-btn,
    .rr-outline-btn,
    .rr-store-btn,
    .store-btn,
    .about-store-btn {
        position: relative;
        overflow: hidden;
        transition: transform .22s cubic-bezier(.22,.68,.34,1), box-shadow .22s ease, background-color .2s ease, border-color .2s ease;
    }
    .rr-primary-btn { background: linear-gradient(135deg, #6d4dff, #4c2af8); }
    .rr-primary-btn:hover {
        background: linear-gradient(135deg, #5f3dff, #3f22e6);
        transform: translateY(-2px);
        box-shadow: 0 12px 26px -10px rgba(76,42,248,.75);
    }
    .rr-primary-btn::after {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        width: 45%;
        transform: translateX(-180%) skewX(-18deg);
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.36), transparent);
        animation: rr-pill-sheen 5s ease-in-out infinite;
    }
    .rr-outline-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 22px -12px rgba(76,42,248,.6); }
    .rr-store-btn:hover,
    .store-btn:hover,
    .about-store-btn:hover {
        transform: translateY(-2px);
        border-color: rgba(86,59,255,.55);
        box-shadow: 0 12px 26px -14px rgba(76,42,248,.6);
    }

    /* ---------- stats ---------- */
    .rr-stat strong,
    .stat strong {
        background: linear-gradient(135deg, #4c2af8, #7c3aed 48%, #ec4899);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    html.dark .rr-stat strong,
    html.dark .stat strong {
        background: linear-gradient(135deg, #a78bfa, #c4b5fd 46%, #f9a8d4);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* ---------- CTA band ---------- */
    .rr-cta-card,
    .cta-card,
    .about-cta-card { position: relative; overflow: hidden; }
    .rr-cta-card::before,
    .cta-card::before,
    .about-cta-card::before {
        content: '';
        position: absolute;
        inset: -40%;
        z-index: 0;
        background: conic-gradient(from 0deg, rgba(124,58,237,.16), rgba(6,182,212,.14), rgba(236,72,153,.14), rgba(245,158,11,.12), rgba(124,58,237,.16));
        animation: rr-spin 22s linear infinite;
        pointer-events: none;
    }
    .rr-cta-content,
    .cta-content { position: relative; z-index: 2; }
    @keyframes rr-spin { to { transform: rotate(1turn); } }

    @media (max-width: 640px) {
        body::before, body::after { filter: blur(64px); opacity: .38; }
        .rr-section-sub, .section-sub { font-size: 14px; }
    }

    @media (prefers-reduced-motion: reduce) {
        body::before,
        body::after,
        .rr-section-head::after,
        .section-head::after,
        .rr-pill::after,
        .lp-pill::after,
        .safety-pill::after,
        .about-pill::after,
        .rr-primary-btn::after,
        .rr-cta-card::before,
        .cta-card::before,
        .about-cta-card::before { animation: none; }

        html.rr-motion [data-rr-r] { opacity: 1; transform: none; }
    }
</style>

<script>
    (function () {
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function ready(fn) {
            if (document.readyState !== 'loading') { fn(); }
            else { document.addEventListener('DOMContentLoaded', fn); }
        }

        ready(function () {
            /* ---- scroll progress bar ---- */
            var bar = document.createElement('div');
            bar.className = 'rr-progress';
            document.body.appendChild(bar);

            var ticking = false;
            function onScroll() {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(function () {
                    var max = document.documentElement.scrollHeight - window.innerHeight;
                    bar.style.setProperty('--rr-p', max > 0 ? (window.scrollY / max).toFixed(4) : 0);
                    ticking = false;
                });
            }
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            if (reduce || !('IntersectionObserver' in window)) return;

            /* Hidden tabs freeze CSS transitions, which would strand revealed
               elements at opacity 0. Arm the effect only once actually visible. */
            if (document.hidden) {
                document.addEventListener('visibilitychange', function onVis() {
                    if (document.hidden) return;
                    document.removeEventListener('visibilitychange', onVis);
                    setupMotion();
                });
                return;
            }
            setupMotion();
        });

        function setupMotion() {
            var root = document.documentElement;

            /* ---- scroll reveal ---- */
            root.classList.add('rr-motion');

            var groups = [
                '.rr-feature-grid > *', '.feature-grid > *', '.features-grid-8 > *', '.help-topic-grid > *',
                '.steps > *', '.faq-list > *', '.help-faq-grid > *', '.help-safety-grid > *',
                '.rr-section-head', '.section-head', '.help-section-left',
                '.rr-stats-band', '.stats-band',
                '.rr-cta-card', '.cta-card', '.about-cta-card',
                '.about-card', '.soft-card'
            ];

            var seen = new Set();
            groups.forEach(function (sel) {
                var nodes = document.querySelectorAll(sel);
                var i = 0;
                nodes.forEach(function (el) {
                    if (seen.has(el) || el.closest('.lp-hero, .rr-hero, .safety-hero, .about-hero, .help-hero')) return;
                    seen.add(el);
                    el.setAttribute('data-rr-r', '');
                    el.style.setProperty('--rr-i', (i++ % 4));
                });
            });

            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('rr-in');
                    io.unobserve(entry.target);
                });
            }, { rootMargin: '0px 0px -8% 0px', threshold: .12 });

            seen.forEach(function (el) { io.observe(el); });

            /* ---- count-up on stat numbers ---- */
            var stats = document.querySelectorAll('.rr-stat strong, .stat strong');
            var statIo = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var el = entry.target;
                    statIo.unobserve(el);

                    var raw = el.textContent.trim();
                    var match = raw.match(/^([^\d]*)([\d][\d,.]*)(.*)$/);
                    if (!match) return;

                    var target = parseFloat(match[2].replace(/,/g, ''));
                    if (!isFinite(target) || target <= 0) return;

                    var decimals = (match[2].split('.')[1] || '').length;
                    var grouped = match[2].indexOf(',') !== -1;
                    var start = performance.now();
                    var dur = 1250;

                    function frame(now) {
                        var t = Math.min((now - start) / dur, 1);
                        var eased = 1 - Math.pow(1 - t, 3);
                        var val = (target * eased).toFixed(decimals);
                        if (grouped) val = Number(val).toLocaleString('en-US', {
                            minimumFractionDigits: decimals,
                            maximumFractionDigits: decimals
                        });
                        el.textContent = match[1] + val + match[3];
                        if (t < 1) requestAnimationFrame(frame);
                    }
                    el.textContent = match[1] + (0).toFixed(decimals) + match[3];
                    requestAnimationFrame(frame);
                });
            }, { threshold: .4 });

            stats.forEach(function (el) { statIo.observe(el); });
        }
    })();
</script>
