{{--
    Print-to-PDF export shared by every admin list page.
    Header and footer repeat on each printed page via a fixed <header>/<footer>
    plus thead repetition, which is how print CSS handles running headers.
--}}
@php($brand = \App\Models\SystemSetting::get('app_name', appName()))
@php($logo = \App\Models\SystemSetting::get('admin_logo'))
<!DOCTYPE html>
<html dir="{{ adminIsRtl() ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 landscape; margin: 108px 24px 76px; }

        /* This document is always light — it is meant to be printed on paper,
           so the viewer's dark mode must not invert it. */
        :root { color-scheme: light; }

        * { box-sizing: border-box; font-family: -apple-system, "Segoe UI", Arial, sans-serif; }
        body {
            margin: 0;
            background: #fff;
            color: #1f2937;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            /* On screen the fixed header/footer would sit on top of the table;
               in print the @page margins already reserve that space. */
            padding: 104px 24px 70px;
        }
        @media print { body { padding: 0; } }

        /* ---- running header ---- */
        .doc-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 92px;
            padding: 14px 24px 10px;
            border-bottom: 3px solid #4f46e5;
            background: #fff;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .doc-header img { height: 40px; width: 40px; object-fit: contain; border-radius: 8px; }
        .doc-header .brand { font-size: 17px; font-weight: 800; color: #312e81; line-height: 1.2; }
        .doc-header .sub { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .doc-header .right { margin-inline-start: auto; text-align: end; }
        .doc-header .title { font-size: 14px; font-weight: 700; color: #111827; }
        .doc-header .meta { font-size: 9.5px; color: #6b7280; line-height: 1.5; margin-top: 3px; }

        /* ---- running footer ---- */
        .doc-footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 58px;
            padding: 10px 24px;
            border-top: 1px solid #e5e7eb;
            background: #fff;
            font-size: 9px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .doc-footer .pager::after {
            /* browsers substitute the real page number when printing */
            content: counter(page) " / " counter(pages);
        }

        /* ---- table ---- */
        table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
        thead { display: table-header-group; }   /* repeat header on every page */
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; }
        th {
            background: #eef2ff;
            color: #312e81;
            font-size: 8.5px;
            letter-spacing: .05em;
            text-transform: uppercase;
            border: 1px solid #e0e7ff;
            padding: 7px 8px;
            text-align: start;
        }
        td { border: 1px solid #eef0f6; padding: 6px 8px; background: #fff; color: #1f2937; }
        tbody tr:nth-child(even) td { background: #fafafe; }
        .empty { text-align: center; color: #9ca3af; padding: 26px; }

        .chips { margin: 0 0 10px; font-size: 9.5px; color: #4b5563; }
        .chip {
            display: inline-block;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            padding: 2px 8px;
            margin-inline-end: 4px;
        }

        .actions { margin: 0 0 14px; }
        .actions button {
            background: #4f46e5; color: #fff; border: 0; border-radius: 8px;
            padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        @media print { .actions { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="actions">
        <button onclick="window.print()">{{ __('admin.print_save_pdf') }}</button>
    </div>

    <header class="doc-header">
        @if ($logo)
            <img src="{{ public_path('storage/' . $logo) }}" alt="">
        @endif
        <div>
            <div class="brand">{{ $brand }}</div>
            <div class="sub">{{ __('admin.admin_panel') }}</div>
        </div>
        <div class="right">
            <div class="title">{{ $title }}</div>
            <div class="meta">
                {{ __('admin.generated') }}: {{ $generatedAt }}<br>
                {{ __('admin.generated_by') }}: {{ $generatedBy }} · {{ __('admin.total_records') }}: {{ number_format($total) }}
            </div>
        </div>
    </header>

    <footer class="doc-footer">
        <span>&copy; {{ date('Y') }} {{ $brand }} · {{ __('admin.confidential') }}</span>
        <span class="pager"></span>
    </footer>

    @if (! empty($appliedFilters))
        <p class="chips">
            <strong>{{ __('admin.applied_filters') }}:</strong>
            @foreach ($appliedFilters as $label => $value)
                <span class="chip">{{ $label }}: {{ $value }}</span>
            @endforeach
        </p>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($headers as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ count($headers) }}">{{ __('admin.no_data') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
