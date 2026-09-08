@extends('layouts.public')

@section('title', $page->title . ' - ' . siteBrand())

@section('styles')
<style>
    .doc-shell {
        background: #ffffff;
        color: #121936;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .dark .doc-shell { background: #0b1020; color: #e5e7eb; }
    .doc-wrap { max-width: 860px; width: 100%; margin: 0 auto; padding: 0 20px; }
    .doc-head { padding: 48px 0 18px; border-bottom: 1px solid rgba(17,24,54,.08); }
    .dark .doc-head { border-color: rgba(255,255,255,.08); }
    .doc-head h1 { font-size: clamp(28px, 4vw, 40px); font-weight: 800; letter-spacing: -.02em; margin: 0; }
    .doc-updated { margin-top: 8px; font-size: 13px; color: #6b7280; }
    .dark .doc-updated { color: #9ca3af; }

    /* Typography for admin-authored HTML */
    .doc-body { padding: 28px 0 72px; font-size: 16px; line-height: 1.85; }
    .doc-body h1, .doc-body h2, .doc-body h3, .doc-body h4 {
        font-weight: 700; letter-spacing: -.01em; line-height: 1.35;
        margin: 1.8em 0 .6em; color: #0b1235;
    }
    .dark .doc-body h1, .dark .doc-body h2, .dark .doc-body h3, .dark .doc-body h4 { color: #f3f4f6; }
    .doc-body h1 { font-size: 1.6em; }
    .doc-body h2 { font-size: 1.35em; }
    .doc-body h3 { font-size: 1.15em; }
    .doc-body p { margin: 0 0 1.1em; }
    .doc-body ul, .doc-body ol { margin: 0 0 1.1em; padding-inline-start: 1.4em; }
    .doc-body li { margin: .4em 0; }
    .doc-body a { color: #563BFF; text-decoration: underline; text-underline-offset: 2px; }
    .dark .doc-body a { color: #a5b4fc; }
    .doc-body strong { font-weight: 700; }
    .doc-body hr { border: 0; border-top: 1px solid rgba(17,24,54,.10); margin: 2em 0; }
    .dark .doc-body hr { border-color: rgba(255,255,255,.10); }
    .doc-body blockquote {
        margin: 1.2em 0; padding: .2em 0 .2em 1em;
        border-inline-start: 3px solid #563BFF; color: #4b5563;
    }
    .dark .doc-body blockquote { color: #9ca3af; }
    .doc-body table { width: 100%; border-collapse: collapse; margin: 1.2em 0; font-size: .95em; }
    .doc-body th, .doc-body td { border: 1px solid rgba(17,24,54,.12); padding: 8px 10px; text-align: start; }
    .dark .doc-body th, .dark .doc-body td { border-color: rgba(255,255,255,.12); }
    .doc-body img { max-width: 100%; height: auto; }
    .doc-body pre { overflow-x: auto; background: rgba(17,24,54,.04); padding: 12px; border-radius: 8px; }
    .dark .doc-body pre { background: rgba(255,255,255,.06); }
</style>
@endsection

@section('content')
<div class="doc-shell">
    <div class="doc-wrap">
        <header class="doc-head">
            <h1>{{ $page->title }}</h1>
            @if ($page->updated_at)
                <p class="doc-updated">{{ __('Last updated') }}: {{ $page->updated_at->format('d M Y') }}</p>
            @endif
        </header>

        {{-- Content is HTML authored by an admin in Admin → Pages (trusted input). --}}
        <article class="doc-body">
            {!! $page->content !!}
        </article>
    </div>
</div>
@endsection
