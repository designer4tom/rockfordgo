<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments,read'),
        ];
    }

    public function index(Request $request)
    {
        $transactions = $this->filteredQuery($request)
            ->with('order:id,order_number')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        // Attach owner names in one pass to avoid per-row lookups.
        $this->hydrateOwners($transactions->getCollection());

        return view('admin.payments.transactions.index', [
            'transactions' => $transactions,
            'stats' => $this->stats(),
            'filters' => $request->only(['from', 'to', 'type', 'category', 'owner_type', 'min', 'max']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $transactions = $this->filteredQuery($request)->with('order:id,order_number')->get();
        $this->hydrateOwners($transactions);

        $filename = 'transactions-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Owner Type', 'Owner', 'Order#', 'Type', 'Category', 'Amount', 'Balance After', 'Note', 'Date']);
            foreach ($transactions as $tx) {
                fputcsv($out, [
                    $tx->id, $tx->owner_type, $tx->owner_name ?? '', $tx->order->order_number ?? '',
                    $tx->type, $tx->category, $tx->amount, $tx->balance_after, $tx->note,
                    $tx->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ---------------------------------------------------------------------

    private function filteredQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return WalletTransaction::query()
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('owner_type'), fn ($q) => $q->where('owner_type', $request->owner_type))
            ->when($request->filled('min'), fn ($q) => $q->where('amount', '>=', $request->min))
            ->when($request->filled('max'), fn ($q) => $q->where('amount', '<=', $request->max));
    }

    // Resolve owner display names for a collection of transactions.
    private function hydrateOwners($collection): void
    {
        $driverIds = $collection->where('owner_type', 'driver')->pluck('owner_id')->unique();
        $userIds = $collection->where('owner_type', 'user')->pluck('owner_id')->unique();

        $drivers = Driver::whereIn('id', $driverIds)->pluck('name', 'id');
        $users = User::whereIn('id', $userIds)->pluck('name', 'id');

        foreach ($collection as $tx) {
            $tx->owner_name = $tx->owner_type === 'driver'
                ? ($drivers[$tx->owner_id] ?? '#' . $tx->owner_id)
                : ($users[$tx->owner_id] ?? '#' . $tx->owner_id);
        }
    }

    private function stats(): array
    {
        return [
            'total_revenue' => (float) Order::where('status', 'completed')->sum('total_amount'),
            'today_revenue' => (float) Order::where('status', 'completed')->whereDate('completed_at', today())->sum('total_amount'),
            'commission' => (float) WalletTransaction::where('category', 'commission')->sum('amount'),
            'pending_withdrawals' => (float) WithdrawalRequest::where('status', 'pending')->sum('amount'),
            'refunds' => (float) WalletTransaction::where('category', 'refund')->sum('amount'),
        ];
    }
}
