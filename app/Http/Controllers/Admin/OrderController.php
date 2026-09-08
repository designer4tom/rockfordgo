<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Zone;
use App\Support\TableExport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders,read', only: ['index', 'show', 'export']),
        ];
    }

    public function index(Request $request)
    {
        $orders = $this->filteredQuery($request)
            ->with(['user:id,name,phone', 'driver:id,name,phone', 'service:id,name'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'zones' => Zone::orderBy('name')->get(),
            'stats' => $this->stats(),
            'filters' => $request->only([
                'search', 'type', 'status', 'payment_method', 'payment_status',
                'zone_id', 'from', 'to', 'is_cod', 'is_scheduled',
            ]),
        ]);
    }

    public function show(string $id)
    {
        $order = Order::with([
            'user', 'driver.vehicles.vehicleCategory', 'service', 'vehicleCategory',
            'coupon', 'locations', 'ratings', 'dispute',
            'dispatchLogs' => fn ($q) => $q->orderBy('id'),
            'dispatchLogs.driver:id,name,phone',
        ])->findOrFail($id);

        // Live in-flight dispatch state (cache) — only present while still dispatching.
        $dispatchState = \Illuminate\Support\Facades\Cache::get("order:dispatch:{$order->id}");

        // Candidate drivers for the assign/reassign typeahead. All APPROVED drivers
        // (online listed first) so an admin can still assign when auto-dispatch found
        // nobody online — a manual override for the no_driver_found case.
        $candidates = \App\Models\Driver::query()
            ->approved()
            ->when($order->driver?->zone_id, fn ($q) => $q->where('zone_id', $order->driver->zone_id))
            ->where('id', '!=', $order->driver_id)
            ->orderByDesc('is_online')
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'phone', 'is_online']);

        $mapsKey = \App\Models\SystemSetting::get('google_maps_key');

        return view('admin.orders.show', compact('order', 'candidates', 'mapsKey', 'dispatchState'));
    }

    // CSV export of the currently filtered orders.
    /** Columns shared by every order export format. */
    private const EXPORT_HEADERS = [
        'Order#', 'Type', 'Status', 'Customer Name', 'Customer Phone',
        'Driver Name', 'Driver Phone', 'Pickup', 'Drop', 'Distance (km)',
        'Total Amount', 'Admin Commission', 'Driver Earning',
        'Payment Method', 'Payment Status', 'Created At', 'Completed At',
    ];

    // Export the currently filtered orders as CSV / Excel / Word / PDF.
    public function export(Request $request)
    {
        $format = $request->validate([
            'format' => ['nullable', 'in:csv,xlsx,docx,pdf'],
        ])['format'] ?? 'csv';

        $orders = $this->filteredQuery($request)
            ->with(['user:id,name,phone', 'driver:id,name,phone'])
            ->get();

        $rows = $orders->map(fn ($o) => [
            $o->order_number, ucfirst($o->type), ucfirst(str_replace('_', ' ', $o->status)),
            $o->user->name ?? '—', $o->user->phone ?? '—',
            $o->driver->name ?? '—', $o->driver->phone ?? '—',
            $o->pickup_address, $o->drop_address, $o->distance_km,
            number_format((float) $o->total_amount, 2),
            number_format((float) $o->admin_commission, 2),
            number_format((float) $o->driver_earning, 2),
            ucfirst($o->payment_method), ucfirst($o->payment_status),
            $o->created_at?->format('d M Y, H:i'),
            $o->completed_at?->format('d M Y, H:i') ?? '—',
        ])->all();

        $stamp = now()->format('Ymd-His');
        $title = __('admin.orders');

        return match ($format) {
            'xlsx' => TableExport::xlsx("orders-{$stamp}.xlsx", $title, self::EXPORT_HEADERS, $rows),
            'docx' => TableExport::docx(
                "orders-{$stamp}.docx",
                $title,
                [
                    __('admin.generated') . ': ' . now()->format('d M Y, h:i a'),
                    __('admin.total_records') . ': ' . count($rows),
                ],
                self::EXPORT_HEADERS,
                $rows
            ),
            'pdf' => response()->view('admin.exports.table-pdf', [
                'title' => $title,
                'headers' => self::EXPORT_HEADERS,
                'rows' => $rows,
                'total' => count($rows),
                'generatedAt' => now()->format('d M Y, h:i a'),
                'generatedBy' => adminUser()?->name ?? '—',
                'appliedFilters' => $this->filterLabels($request),
            ]),
            default => TableExport::csv("orders-{$stamp}.csv", self::EXPORT_HEADERS, $rows),
        };
    }

    /** Human-readable summary of the active filters, shown on the PDF header. */
    private function filterLabels(Request $request): array
    {
        $labels = [];

        if ($request->filled('search')) {
            $labels[__('admin.search')] = $request->string('search')->toString();
        }
        if ($request->filled('type')) {
            $labels[__('admin.type')] = ucfirst($request->string('type')->toString());
        }
        if ($request->filled('status')) {
            $labels[__('admin.status')] = ucfirst($request->string('status')->toString());
        }
        if ($request->filled('payment_method')) {
            $labels[__('admin.payment')] = ucfirst($request->string('payment_method')->toString());
        }
        if ($request->filled('payment_status')) {
            $labels[__('admin.payment_status')] = ucfirst($request->string('payment_status')->toString());
        }
        if ($request->filled('zone_id')) {
            $labels[__('admin.zone')] = Zone::find($request->zone_id)?->name ?? $request->zone_id;
        }
        if ($request->filled('is_cod')) {
            $labels['COD'] = $request->input('is_cod') === 'yes' ? __('admin.cod_only') : __('admin.non_cod');
        }
        if ($request->filled('is_scheduled')) {
            $labels[__('admin.scheduled')] = $request->input('is_scheduled') === 'yes' ? __('admin.scheduled') : __('admin.instant');
        }
        if ($request->filled('from') || $request->filled('to')) {
            $labels[__('admin.created')] = trim(($request->from ?? '…') . ' → ' . ($request->to ?? '…'));
        }

        return $labels;
    }

    // ---------------------------------------------------------------------

    private function filteredQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return Order::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search')->toString();
                $q->where(function ($w) use ($s) {
                    $w->where('order_number', 'like', "%{$s}%")
                        ->orWhereHas('user', fn ($u) => $u->where('phone', 'like', "%{$s}%"))
                        ->orWhereHas('driver', fn ($d) => $d->where('phone', 'like', "%{$s}%"));
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), function ($q) use ($request) {
                $request->status === 'ongoing'
                    ? $q->ongoing()
                    : $q->where('status', $request->status);
            })
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->filled('zone_id'), function ($q) use ($request) {
                $q->whereHas('driver', fn ($d) => $d->where('zone_id', $request->zone_id));
            })
            ->when($request->filled('is_cod'), fn ($q) => $q->where('is_cod', $request->is_cod === 'yes'))
            ->when($request->filled('is_scheduled'), function ($q) use ($request) {
                $request->is_scheduled === 'yes'
                    ? $q->whereNotNull('scheduled_at')
                    : $q->whereNull('scheduled_at');
            })
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to));
    }

    private function stats(): array
    {
        return [
            'today' => Order::whereDate('created_at', today())->count(),
            'completed' => Order::where('status', 'completed')->whereDate('created_at', today())->count(),
            'cancelled' => Order::where('status', 'cancelled')->whereDate('created_at', today())->count(),
            'revenue' => (float) Order::where('payment_status', 'paid')->whereDate('created_at', today())->sum('total_amount'),
        ];
    }
}
