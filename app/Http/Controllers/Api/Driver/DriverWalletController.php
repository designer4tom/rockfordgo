<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Customer\WalletController as CustomerWallet;
use App\Models\WithdrawalRequest;
use App\Services\SystemSettingService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DriverWalletController extends Controller
{
    use ApiResponse;

    public function __construct(private SystemSettingService $settings)
    {
    }

    public function balance(Request $request)
    {
        $driver = $request->user();
        $limit = (float) $this->settings->get('due_limit_amount', 500);

        return $this->success([
            'balance' => number_format((float) $driver->wallet_balance, 2, '.', ''),
            'due_amount' => number_format((float) $driver->due_amount, 2, '.', ''),
            'due_limit' => number_format($limit, 2, '.', ''),
            'can_accept_orders' => (bool) $driver->can_accept_orders,
        ], 'Wallet fetched.');
    }

    public function transactions(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);

        $txns = $request->user()->walletTransactions()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->with('order:id,order_number')
            ->latest()
            ->paginate($perPage);

        $items = $txns->getCollection()->map(fn ($t) => [
            'id' => $t->id,
            'type' => $t->type,
            'category' => $t->category,
            'category_label' => CustomerWallet::CATEGORY_LABELS[$t->category] ?? ucwords(str_replace('_', ' ', $t->category)),
            'amount' => number_format((float) $t->amount, 2, '.', ''),
            'balance_after' => number_format((float) $t->balance_after, 2, '.', ''),
            'note' => $t->note,
            'order_number' => $t->order->order_number ?? null,
            'created_at' => $t->created_at->toISOString(),
        ])->all();

        return $this->paginated($txns, $items, 'Transactions fetched.');
    }

    public function requestWithdrawal(Request $request)
    {
        $min = (float) $this->settings->get('withdrawal_minimum_amount', 100);
        $driver = $request->user();

        $data = $request->validate([
            'amount' => ['required', 'numeric', "min:$min", 'max:' . (float) $driver->wallet_balance],
            'method' => ['required', \Illuminate\Validation\Rule::in(\App\Models\WithdrawalMethod::active()->pluck('code')->all())],
            'account' => ['required', 'string', 'max:100'],
        ]);

        if ($driver->withdrawalRequests()->where('status', 'pending')->exists()) {
            return $this->error('You already have a pending withdrawal request.', 422);
        }

        $req = WithdrawalRequest::create([
            'driver_id' => $driver->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'account_details' => ['account' => $data['account']],
            'status' => 'pending',
        ]);

        return $this->success([
            'request_id' => $req->id,
            'amount' => number_format((float) $req->amount, 2, '.', ''),
            'status' => $req->status,
        ], 'Withdrawal request submitted');
    }

    public function withdrawalHistory(Request $request)
    {
        $items = $request->user()->withdrawalRequests()->latest()->paginate(20);

        $mapped = $items->getCollection()->map(fn ($w) => [
            'id' => $w->id,
            'amount' => number_format((float) $w->amount, 2, '.', ''),
            'method' => $w->method,
            'status' => $w->status,
            'rejection_reason' => $w->rejection_reason,
            'processed_at' => $w->processed_at?->toISOString(),
            'created_at' => $w->created_at->toISOString(),
        ])->all();

        return $this->paginated($items, $mapped, 'Withdrawal history fetched.');
    }
}
