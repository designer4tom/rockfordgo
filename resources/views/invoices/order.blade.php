<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $inv['invoice_number'] }}</title>
    <style>
        *{font-family:-apple-system,Arial,sans-serif;color:#1f2937}
        body{margin:32px}
        h1{font-size:18px;margin:0}
        .muted{color:#6b7280;font-size:12px}
        table{width:100%;border-collapse:collapse;margin-top:12px;font-size:13px}
        td{padding:5px 0}
        .row td:last-child{text-align:right}
        .total{border-top:2px solid #111;font-weight:700;font-size:15px}
        .sec{margin-top:18px}
        @media print{.noprint{display:none}body{margin:0}}
    </style>
</head>
<body onload="window.print()">
    <div class="noprint"><button onclick="window.print()">Print / Save as PDF</button></div>
    <h1>{{ appName() }} — Invoice</h1>
    <p class="muted">{{ $inv['invoice_number'] }} · {{ ucfirst($inv['order_type']) }} · {{ \Illuminate\Support\Carbon::parse($inv['date'])->format('d M Y, H:i') }}</p>

    <div class="sec">
        <strong>Customer:</strong> {{ $inv['customer']['name'] }} ({{ $inv['customer']['phone'] }})<br>
        @if($inv['driver'])<strong>Driver:</strong> {{ $inv['driver']['name'] }} — {{ $inv['driver']['vehicle'] }}@endif
    </div>

    <div class="sec">
        <strong>Route</strong><br>
        <span class="muted">{{ $inv['route']['from'] }} → {{ $inv['route']['to'] }} · {{ $inv['route']['distance_km'] }} km · {{ $inv['route']['duration_minutes'] }} min</span>
    </div>

    <table class="sec">
        @foreach (['base_fare'=>'Base Fare','distance_charge'=>'Distance Charge','time_charge'=>'Time Charge','delivery_charge'=>'Delivery Charge','surge_amount'=>'Surge','coupon_discount'=>'Coupon Discount','tip'=>'Tip'] as $k=>$label)
            @if ((float) $inv['fare_breakdown'][$k] != 0)
                <tr class="row"><td>{{ $label }}</td><td>{{ $inv['fare_breakdown'][$k] }}</td></tr>
            @endif
        @endforeach
        <tr class="row total"><td>Total</td><td>{{ $inv['fare_breakdown']['total'] }}</td></tr>
    </table>

    <p class="sec muted">Payment: {{ ucfirst($inv['payment']['method']) }} · {{ ucfirst($inv['payment']['status']) }}</p>
</body>
</html>
