<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\StripeService;
use App\Services\SystemSettingService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WalletController extends Controller
{
    use ApiResponse;

    public const CATEGORY_LABELS = [
        'trip_earning' => 'Trip Payment',
        'parcel_earning' => 'Parcel Payment',
        'commission' => 'Commission',
        'cod_collection' => 'COD Collection',
        'cod_payout' => 'COD Payout',
        'parcel_delivery_charge' => 'Delivery Charge',
        'withdrawal' => 'Withdrawal',
        'refund' => 'Refund',
        'referral_bonus' => 'Referral Bonus',
        'top_up' => 'Wallet Top-up',
        'due_payment' => 'Due Payment',
    ];

    public function __construct(
        private SystemSettingService $settings,
        private StripeService $stripe,
        private WalletService $wallet,
    ) {
    }

    public function balance(Request $request)
    {
        $user = $request->user();
        $dueLimit = (float) $this->settings->get('sender_due_limit_amount', 500);

        return $this->success([
            'balance' => number_format((float) $user->wallet_balance, 2, '.', ''),
            'due_amount' => number_format((float) $user->due_amount, 2, '.', ''),
            'due_limit' => number_format($dueLimit, 2, '.', ''),
            'can_place_cod' => (float) $user->due_amount < $dueLimit,
            'currency' => $this->settings->get('currency', 'BDT'),
            'currency_symbol' => $this->settings->get('currency_symbol', '৳'),
        ], 'Balance fetched.');
    }

    public function dues(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);

        $dues = $request->user()->dueTransactions()
            ->with('order:id,order_number')
            ->latest()
            ->paginate($perPage);

        $items = $dues->getCollection()->map(fn ($d) => [
            'id' => $d->id,
            'type' => $d->type,
            'amount' => number_format((float) $d->amount, 2, '.', ''),
            'due_before' => number_format((float) $d->due_before, 2, '.', ''),
            'due_after' => number_format((float) $d->due_after, 2, '.', ''),
            'note' => $d->note,
            'order_number' => $d->order->order_number ?? null,
            'created_at' => $d->created_at->toISOString(),
        ])->all();

        return $this->paginated($dues, $items, 'Dues fetched.');
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

        $items = $txns->getCollection()->map(fn ($t) => $this->formatTxn($t))->all();

        return $this->paginated($txns, $items, 'Transactions fetched.');
    }

    public function initiateTopup(Request $request)
    {
        $min = (float) $this->settings->get('wallet_topup_min', 50);
        $max = (float) $this->settings->get('wallet_topup_max', 10000);

        $data = $request->validate([
            'amount' => ['required', 'numeric', "min:$min", "max:$max"],
        ]);

        if (! $this->settings->getBool('wallet_topup_enabled', true)) {
            return $this->error('Wallet top-up is currently disabled.', 422);
        }

        $currency = strtolower($this->settings->get('currency', 'BDT'));
        $intent = $this->stripe->createPaymentIntent((int) round($data['amount'] * 100), $currency, [
            'user_id' => (string) $request->user()->id,
            'purpose' => 'wallet_topup',
        ]);

        if (! $intent['ok']) {
            return $this->error($intent['message'], 422);
        }

        Cache::put('pending_topup_' . $intent['id'], ['user_id' => $request->user()->id, 'amount' => $data['amount']], now()->addHour());

        return $this->success([
            'client_secret' => $intent['client_secret'],
            'payment_intent_id' => $intent['id'],
            'amount' => number_format((float) $data['amount'], 2, '.', ''),
            'currency' => $currency,
        ], 'Top-up initiated.');
    }

    public function confirmTopup(Request $request)
    {
        $data = $request->validate(['payment_intent_id' => ['required', 'string']]);
        $user = $request->user();

        $intent = $this->stripe->retrievePaymentIntent($data['payment_intent_id']);
        if (! $intent['ok'] || $intent['status'] !== 'succeeded') {
            return $this->error('Payment not completed.', 422);
        }

        // Guard against double-credit.
        $cacheKey = 'topup_done_' . $data['payment_intent_id'];
        if (Cache::has($cacheKey)) {
            return $this->error('This top-up has already been processed.', 422);
        }

        $amount = round(((int) $intent['amount']) / 100, 2);
        // Clears any outstanding sender due first, then tops up the balance.
        $result = $this->wallet->processUserTopup($user, $amount, 'top_up', 'Wallet top-up via card');
        Cache::put($cacheKey, true, now()->addDays(7));
        Cache::forget('pending_topup_' . $data['payment_intent_id']);

        return $this->success([
            'due_cleared' => number_format($result['due_cleared'], 2, '.', ''),
            'balance_added' => number_format($result['balance_added'], 2, '.', ''),
            'new_balance' => number_format($result['new_balance'], 2, '.', ''),
            'new_due' => number_format($result['new_due'], 2, '.', ''),
        ], $this->settings->get('currency_symbol', '৳') . number_format($amount, 0) . ' added to your wallet');
    }

    public function requestWithdrawal(Request $request)
    {
        $min = (float) $this->settings->get('user_withdrawal_minimum', 100);
        $user = $request->user();

        $data = $request->validate([
            'amount' => ['required', 'numeric', "min:$min", 'max:' . (float) $user->wallet_balance],
            'method' => ['required', \Illuminate\Validation\Rule::in(\App\Models\WithdrawalMethod::active()->pluck('code')->all())],
            'account' => ['required', 'string', 'max:100'],
        ]);

        if ($user->withdrawalRequests()->where('status', 'pending')->exists()) {
            return $this->error('You already have a pending withdrawal request.', 422);
        }

        $req = WithdrawalRequest::create([
            'user_id' => $user->id,
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
            'account' => $w->account_details['account'] ?? null,
            'status' => $w->status,
            'rejection_reason' => $w->rejection_reason,
            'processed_at' => $w->processed_at?->toISOString(),
            'created_at' => $w->created_at->toISOString(),
        ])->all();

        return $this->paginated($items, $mapped, 'Withdrawal history fetched.');
    }

    private function formatTxn($t): array
    {
        return [
            'id' => $t->id,
            'type' => $t->type,
            'category' => $t->category,
            'category_label' => self::CATEGORY_LABELS[$t->category] ?? ucwords(str_replace('_', ' ', $t->category)),
            'amount' => number_format((float) $t->amount, 2, '.', ''),
            'balance_after' => number_format((float) $t->balance_after, 2, '.', ''),
            'note' => $t->note,
            'order_number' => $t->order->order_number ?? null,
            'created_at' => $t->created_at->toISOString(),
        ];
    }
}
