<!DOCTYPE html>
<html dir="{{ adminIsRtl() ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('admin.revenue_report') }}</title>
    <style>
        * { font-family: -apple-system, Arial, sans-serif; }
        body { color: #1f2937; margin: 32px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6b7280; font-size: 12px; }
        .cards { display: flex; gap: 12px; margin: 20px 0; }
        .card { flex: 1; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; }
        .card p { margin: 0; }
        .card .v { font-size: 18px; font-weight: 700; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        thead { background: #f9fafb; }
        .actions { margin-bottom: 16px; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="actions">
        <button onclick="window.print()">{{ __('admin.print_save_pdf') }}</button>
    </div>

    <h1>{{ __('admin.revenue_report') }}</h1>
    <p class="muted">{{ $filters['from'] }} → {{ $filters['to'] }} · {{ __('admin.grouped') }} {{ $filters['group_by'] }} · {{ __('admin.generated') }} {{ $generatedAt }}</p>

    <div class="cards">
        <div class="card"><p class="muted">{{ __('admin.gross_revenue') }}</p><p class="v">{{ number_format($summary['gross'], 0) }} {{ $currency }}</p></div>
        <div class="card"><p class="muted">{{ __('admin.commission') }}</p><p class="v">{{ number_format($summary['commission'], 0) }} {{ $currency }}</p></div>
        <div class="card"><p class="muted">{{ __('admin.driver_payout') }}</p><p class="v">{{ number_format($summary['driver_payouts'], 0) }} {{ $currency }}</p></div>
        <div class="card"><p class="muted">{{ __('admin.net_revenue') }}</p><p class="v">{{ number_format($summary['net'], 0) }} {{ $currency }}</p></div>
    </div>

    <table>
        <thead>
            <tr><th>{{ __('admin.period') }}</th><th>{{ __('admin.rides') }}</th><th>{{ __('admin.parcels') }}</th><th>{{ __('admin.gross') }}</th><th>{{ __('admin.commission') }}</th><th>{{ __('admin.refunds') }}</th><th>{{ __('admin.net') }}</th></tr>
        </thead>
        <tbody>
            @forelse ($breakdown as $row)
                <tr>
                    <td>{{ $row['bucket'] }}</td>
                    <td>{{ $row['rides'] }}</td>
                    <td>{{ $row['parcels'] }}</td>
                    <td>{{ number_format($row['gross'], 2) }}</td>
                    <td>{{ number_format($row['commission'], 2) }}</td>
                    <td>{{ number_format($row['refunds'], 2) }}</td>
                    <td>{{ number_format($row['net'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center">{{ __('admin.no_data_range') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
