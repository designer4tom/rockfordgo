<?php

namespace App\Http\Controllers\Api\Customer;

use Abedin\MultiPay\Facades\MultiPay;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\TopupRequest;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Customer "Add Money" — wallet top-up through MultiPay (hosted checkout).
 * Fulfilment happens in the CompleteMultiPayPayment listener (order_id
 * "topup-{id}"). The full amount is credited on success; no due for customers.
 */
class CustomerTopupController extends Controller
{
    use ApiResponse;

    public function __construct(
        private WalletService $wallet,
    ) {
    }

    // GET /user/wallet/payment-methods — the app's methods ARE MultiPay's active gateways.
    public function paymentMethods()
    {
        return $this->success(MultiPay::activeGateways(), 'Payment methods fetched.');
    }

    // POST /user/wallet/add-money/initiate
    public function initiate(Request $request)
    {
        $min = (float) SystemSetting::get('min_recharge_amount', config('recharge.min_amount', 50));
        $max = (float) SystemSetting::get('max_recharge_amount', 10000);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:' . $min, 'max:' . $max],
            // payment_method IS the MultiPay gateway key (stripe, razorpay, …).
            'payment_method' => ['required', Rule::in(array_column(MultiPay::activeGateways(), 'name'))],
        ]);

        $user = $request->user();

        $topup = TopupRequest::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'status' => 'pending',
        ]);

        try {
            $res = MultiPay::gateway($data['payment_method'])->pay([
                'amount' => (float) $data['amount'],
                'currency' => SystemSetting::get('currency', 'BDT'),
                'order_id' => 'topup-' . $topup->id,
                'customer' => ['name' => $user->name, 'email' => $user->email],
                'meta' => [
                    'title' => 'Wallet Add Money',
                    'description' => 'Top-up #' . $topup->id,
                    'phone' => $user->phone,
                ],
            ]);
        } catch (\Throwable $e) {
            $topup->update(['status' => 'failed']);

            return $this->error('Payment could not be started: ' . $e->getMessage(), 422);
            }

        if (empty($res['success'])) {
            $topup->update(['status' => 'failed']);

            return $this->error($res['message'] ?? 'Payment could not be started.', 422);
        }

        $topup->update(['payment_url' => $res['payment_url'], 'transaction_id' => $res['payment_id'] ?? null]);

        return $this->success([
            'topup_id' => $topup->id,
            'amount' => number_format((float) $topup->amount, 2, '.', ''),
            'payment_method' => $topup->payment_method,
            'payment_url' => $topup->payment_url,
            'status' => $topup->status,
        ], 'Top-up initiated');
    }

    // POST /user/wallet/add-money/confirm — test/manual; Stripe verified server-side.
    public function confirm(Request $request)
    {
        $data = $request->validate([
            'topup_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $topup = TopupRequest::where('id', $data['topup_id'])->where('user_id', $user->id)->first();

        if (! $topup) {
            return $this->error('Top-up request not found.', 404);
        }
        if ($topup->status === 'success') {
            return $this->success([
                'amount_added' => number_format((float) $topup->amount, 2, '.', ''),
                'new_balance' => number_format((float) $user->wallet_balance, 2, '.', ''),
            ], 'Money added successfully');
        }
        if ($topup->status !== 'pending') {
            return $this->error('This top-up has already been processed.', 422);
        }

        // Verify via MultiPay; the CompleteMultiPayPayment listener credits the wallet
        // (idempotent). The client's word is never trusted.
        $session = \Abedin\MultiPay\Models\PaymentSession::where('order_id', 'topup-' . $topup->id)
            ->latest('id')->first();
        if (! $session) {
            return $this->error('No gateway session for this top-up.', 422);
        }

        MultiPay::confirm($session->session_id, $request);
        $topup->refresh();

        if ($topup->status !== 'success') {
            return $this->error('Payment not completed.', 422);
        }

        $user->refresh();

        return $this->success([
            'amount_added' => number_format((float) $topup->amount, 2, '.', ''),
            'new_balance' => number_format((float) $user->wallet_balance, 2, '.', ''),
        ], 'Money added successfully');
    }

    // GET /user/wallet/add-money/history
    public function history(Request $request)
    {
        $items = TopupRequest::where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(20)
            ->through(fn ($t) => [
                'topup_id' => $t->id,
                'amount' => number_format((float) $t->amount, 2, '.', ''),
                'payment_method' => $t->payment_method,
                'status' => $t->status,
                'date' => $t->created_at?->toIso8601String(),
            ]);

        return $this->success($items, 'Top-up history fetched.');
    }
}
