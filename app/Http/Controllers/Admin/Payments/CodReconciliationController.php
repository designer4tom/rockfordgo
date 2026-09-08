<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CodReconciliationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments,read'),
        ];
    }

    public function index(Request $request)
    {
        $orders = $this->filteredQuery($request)
            ->with(['driver:id,name', 'user:id,name'])
            ->latest('completed_at')
            ->paginate(25)
            ->withQueryString();

        // Settlement = a cod_payout transaction exists for the order.
        $settledOrderIds = $this->settledOrderIds($orders->getCollection()->pluck('id'));
        foreach ($orders->getCollection() as $order) {
            $order->is_settled = $settledOrderIds->contains($order->id);
        }

        return view('admin.payments.cod-reconciliation.index', [
            'orders' => $orders,
            'summary' => $this->summary(),
            'drivers' => Driver::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['from', 'to', 'driver_id', 'status']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $orders = $this->filteredQuery($request)->with(['driver:id,name', 'user:id,name'])->get();
        $settledOrderIds = $this->settledOrderIds($orders->pluck('id'));

        $filename = 'cod-reconciliation-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($orders, $settledOrderIds) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order#', 'Driver', 'Sender', 'COD Amount', 'Delivery Charge', 'Sender Payout', 'Status', 'Date']);
            foreach ($orders as $o) {
                $payout = max(0, (float) $o->cod_amount - (float) $o->delivery_charge);
                fputcsv($out, [
                    $o->order_number, $o->driver->name ?? '', $o->user->name ?? '',
                    $o->cod_amount, $o->delivery_charge, $payout,
                    $settledOrderIds->contains($o->id) ? 'Settled' : 'Pending',
                    $o->completed_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ---------------------------------------------------------------------

    private function filteredQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return Order::query()
            ->where('is_cod', true)
            ->whereNotNull('cod_amount')
            ->when($request->filled('driver_id'), fn ($q) => $q->where('driver_id', $request->driver_id))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->input('status') === 'settled', fn ($q) => $q->whereIn('id', $this->allSettledIds()))
            ->when($request->input('status') === 'pending', fn ($q) => $q->whereNotIn('id', $this->allSettledIds()));
    }

    private function settledOrderIds($orderIds)
    {
        return WalletTransaction::where('category', 'cod_payout')
            ->whereIn('order_id', $orderIds)
            ->pluck('order_id')
            ->unique();
    }

    private function allSettledIds(): array
    {
        return WalletTransaction::where('category', 'cod_payout')
            ->whereNotNull('order_id')
            ->pluck('order_id')
            ->unique()
            ->all();
    }

    private function summary(): array
    {
        $codOrders = Order::where('is_cod', true)->whereNotNull('cod_amount');

        $totalCollected = (float) (clone $codOrders)->where('status', 'completed')->sum('cod_amount');
        $totalSettled = (float) WalletTransaction::where('category', 'cod_payout')->sum('amount');

        return [
            'collected' => $totalCollected,
            'settled' => $totalSettled,
            'pending' => max(0, $totalCollected - $totalSettled),
        ];
    }
}
