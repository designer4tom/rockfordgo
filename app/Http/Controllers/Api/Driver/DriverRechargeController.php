<?php

namespace App\Http\Controllers\Api\Driver;

use Abedin\MultiPay\Facades\MultiPay;
use App\Http\Controllers\Controller;
use App\Models\RechargeRequest;
use App\Models\SystemSetting;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverRechargeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private WalletService $wallet,
    ) {
    }

    // GET /driver/recharge/payment-methods — the app's methods ARE MultiPay's active gateways.
    public function paymentMethods()
    {
        return $this->success(MultiPay::activeGateways(), 'Payment methods fetched.');
    }

    // POST /driver/recharge/initiate — record a pending recharge + MultiPay hosted checkout URL.
    public function initiate(Request $request)
    {
        $min = (float) SystemSetting::get('min_recharge_amount', config('recharge.min_amount', 50));
        $max = (float) SystemSetting::get('max_recharge_amount', 10000);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:' . $min, 'max:' . $max],
            // payment_method IS the MultiPay gateway key (stripe, razorpay, …).
            'payment_method' => ['required', Rule::in(array_column(MultiPay::activeGateways(), 'name'))],
        ]);

        $driver = $request->user();

        $recharge = RechargeRequest::create([
            'driver_id' => $driver->id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'status' => 'pending',
        ]);

        // Hosted checkout via MultiPay. The return routes call MultiPay::confirm();
        // fulfilment happens in CompleteMultiPayPayment (order_id "recharge-{id}").
        try {
            $res = MultiPay::gateway($data['payment_method'])->pay([
                'amount' => (float) $data['amount'],
                'currency' => SystemSetting::get('currency', 'BDT'),
                'order_id' => 'recharge-' . $recharge->id,
                'customer' => ['name' => $driver->name, 'email' => $driver->email],
                'meta' => [
                    'title' => 'Driver Wallet Recharge',
                    'description' => 'Recharge #' . $recharge->id,
                    'phone' => $driver->phone,
                ],
            ]);
        } catch (\Throwable $e) {
            $recharge->update(['status' => 'failed']);

            return $this->error('Payment could not be started: ' . $e->getMessage(), 422);
        }

        if (empty($res['success'])) {
            $recharge->update(['status' => 'failed']);

            return $this->error($res['message'] ?? 'Payment could not be started.', 422);
        }

        $recharge->update(['payment_url' => $res['payment_url'], 'transaction_id' => $res['payment_id'] ?? null]);

        return $this->success([
            'recharge_id' => $recharge->id,
            'amount' => number_format((float) $recharge->amount, 2, '.', ''),
            'payment_method' => $recharge->payment_method,
            'payment_url' => $recharge->payment_url,
            'status' => $recharge->status,
        ], 'Recharge initiated');
    }

    // POST /driver/recharge/confirm — test/manual confirm (later: gateway webhook).
    public function confirm(Request $request)
    {
        $data = $request->validate([
            'recharge_id' => ['required', 'integer'],
        ]);

        $driver = $request->user();
        $recharge = RechargeRequest::where('id', $data['recharge_id'])
            ->where('driver_id', $driver->id)
            ->first();

        if (! $recharge) {
            return $this->error('Recharge request not found.', 404);
        }
        if ($recharge->status !== 'pending') {
            return $recharge->status === 'success'
                ? $this->success($this->rechargeResult($recharge, $driver), 'Recharge successful')
                : $this->error('This recharge has already been processed.', 422);
        }

        // Verify server-side via MultiPay; the CompleteMultiPayPayment listener credits
        // the wallet (idempotent). The client's word is never trusted.
        $session = \Abedin\MultiPay\Models\PaymentSession::where('order_id', 'recharge-' . $recharge->id)
            ->latest('id')->first();
        if (! $session) {
            return $this->error('No gateway session for this recharge.', 422);
        }

        MultiPay::confirm($session->session_id, $request);
        $recharge->refresh();

        if ($recharge->status !== 'success') {
            return $this->error('Payment not completed.', 422);
        }

        return $this->success($this->rechargeResult($recharge, $driver->fresh()), 'Recharge successful');
    }

    private function rechargeResult(RechargeRequest $recharge, $driver): array
    {
        return [
            'amount_paid' => number_format((float) $recharge->amount, 2, '.', ''),
            'due_cleared' => number_format((float) $recharge->due_cleared, 2, '.', ''),
            'balance_added' => number_format((float) $recharge->balance_added, 2, '.', ''),
            'new_balance' => number_format((float) $driver->wallet_balance, 2, '.', ''),
            'new_due' => number_format((float) $driver->due_amount, 2, '.', ''),
        ];
    }

    // GET /driver/recharge/history — driver's recharge list.
    public function history(Request $request)
    {
        $driver = $request->user();

        $items = RechargeRequest::where('driver_id', $driver->id)
            ->latest('id')
            ->paginate(20)
            ->through(fn ($r) => [
                'recharge_id' => $r->id,
                'amount' => number_format((float) $r->amount, 2, '.', ''),
                'payment_method' => $r->payment_method,
                'status' => $r->status,
                'due_cleared' => number_format((float) $r->due_cleared, 2, '.', ''),
                'balance_added' => number_format((float) $r->balance_added, 2, '.', ''),
                'date' => $r->created_at?->toIso8601String(),
            ]);

        return $this->success($items, 'Recharge history fetched.');
    }
}
