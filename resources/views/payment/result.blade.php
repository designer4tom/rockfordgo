<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ appName() }}</title>
    @include('partials.favicon')
    {{-- The app's webview can read this to detect the outcome --}}
    <meta name="payment-status" content="{{ $ok ? 'success' : 'failed' }}">
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
               min-height:100vh; display:flex; align-items:center; justify-content:center;
               background:#f3f4f6; padding:24px; }
        .card { background:#fff; border-radius:20px; padding:36px 28px; max-width:380px; width:100%;
                text-align:center; box-shadow:0 10px 40px rgba(0,0,0,.08); }
        .icon { width:84px; height:84px; border-radius:50%; margin:0 auto 20px; display:flex;
                align-items:center; justify-content:center; }
        .ok  { background:#dcfce7; color:#16a34a; }
        .bad { background:#fee2e2; color:#dc2626; }
        .icon svg { width:44px; height:44px; }
        h1 { font-size:22px; margin:0 0 8px; color:#111827; }
        p  { font-size:15px; color:#6b7280; margin:0 0 20px; line-height:1.5; }
        .amt { font-size:28px; font-weight:700; color:#111827; margin:4px 0 18px; }
        .rows { text-align:left; background:#f9fafb; border-radius:12px; padding:14px 16px; margin-bottom:20px; }
        .rows div { display:flex; justify-content:space-between; font-size:14px; padding:4px 0; color:#374151; }
        .rows b { color:#111827; }
        .btn { display:inline-block; background:#4f46e5; color:#fff; text-decoration:none;
               padding:13px 22px; border-radius:12px; font-weight:600; font-size:15px; border:0; cursor:pointer; width:100%; }
        @media (prefers-color-scheme: dark) {
            body { background:#0b0f19; } .card { background:#111827; box-shadow:none; }
            h1,.amt,.rows b { color:#f3f4f6; } p,.rows div { color:#9ca3af; } .rows { background:#0b0f19; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon {{ $ok ? 'ok' : 'bad' }}">
            @if ($ok)
                <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            @else
                <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            @endif
        </div>

        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>

        @if ($ok && ! is_null($amount))
            <div class="amt">৳ {{ number_format((float) $amount, 2) }}</div>
            @if (! empty($rows))
                <div class="rows">
                    @foreach ($rows as $label => $value)
                        <div><span>{{ $label }}</span> <b>৳ {{ number_format((float) $value, 2) }}</b></div>
                    @endforeach
                </div>
            @endif
        @endif

        <button class="btn" onclick="closeFlow()">Done</button>
    </div>

    <script>
        // Notify the host app (covers common webview bridges) so it can close & refresh.
        (function () {
            var payload = JSON.stringify({
                event: 'payment_result',
                status: '{{ $ok ? "success" : "failed" }}'
            });
            try { if (window.ReactNativeWebView) window.ReactNativeWebView.postMessage(payload); } catch (e) {}
            try { if (window.flutter_inappwebview) window.flutter_inappwebview.callHandler('paymentResult', payload); } catch (e) {}
            try { if (window.PaymentChannel) window.PaymentChannel.postMessage(payload); } catch (e) {}
        })();
        function closeFlow() {
            // App intercepts this URL to close the webview; harmless in a normal browser.
            window.location.href = 'readyride://payment/{{ $ok ? "success" : "cancel" }}';
        }
    </script>
</body>
</html>
