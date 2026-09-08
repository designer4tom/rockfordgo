<?php

namespace App\Http\Controllers;

use App\Models\RechargeRequest;
use App\Models\TopupRequest;
use App\Services\Payment\PaymentService;
use App\Services\WalletService;
use Illuminate\Http\Request;

/**
 * Public landing pages the payment gateway redirects the browser to after
 * checkout. Handles every payment type (driver recharge, customer top-up…)
 * via the `type` query param. Payment is trusted only after a server-side
 * verify(); on success the relevant wallet action is completed.
 */
class PaymentCallbackController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private WalletService $wallet,
    ) {
    }

    public function success(Request $request)
    {
        return match ($request->query('type', 'recharge')) {
            'topup' => $this->topupSuccess($request),
            default => $this->rechargeSuccess($request),
        };
    }

    public function cancel(Request $request)
    {
        $type = $request->query('type', 'recharge');

        if ($type === 'topup') {
            $topup = TopupRequest::find($request->query('topup_id'));
            if ($topup && $topup->status === 'pending') {
                $topup->update(['status' => 'failed']);
            }
        } else {
            $recharge = RechargeRequest::find($request->query('recharge_id'));
            if ($recharge && $recharge->status === 'pending') {
                $recharge->update(['status' => 'failed']);
            }
        }

        return $this->page(false, 'Payment Cancelled', 'Your payment was cancelled. No amount was charged.');
    }

    // ----- Driver recharge -----

    private function rechargeSuccess(Request $request)
    {
        $recharge = RechargeRequest::find($request->query('recharge_id'));
        if (! $recharge) {
            return $this->page(false, 'Payment', 'Recharge not found.');
        }
        if ($recharge->status === 'success') {
            return $this->page(true, 'Payment Successful', 'Your recharge is already complete.', (float) $recharge->amount, [
                'Due cleared' => $recharge->due_cleared, 'Balance added' => $recharge->balance_added,
            ]);
        }
        if ($recharge->status === 'failed') {
            return $this->page(false, 'Payment Failed', 'This payment was not completed.');
        }

        $verify = $this->verify($request, $recharge->payment_method, $recharge->transaction_id);
        if (! $verify) {
            $recharge->update(['status' => 'failed']);

            return $this->page(false, 'Payment Failed', 'Payment could not be verified.');
        }

        $result = $this->wallet->processDriverRecharge(
            $recharge->driver,
            (float) $recharge->amount,
            'Recharge #' . $recharge->id . ' (' . $recharge->payment_method . ')'
        );
        $recharge->update([
            'status' => 'success',
            'transaction_id' => $verify ?: $recharge->transaction_id,
            'due_cleared' => $result['due_cleared'],
            'balance_added' => $result['balance_added'],
        ]);

        return $this->page(true, 'Payment Successful', 'Your recharge is complete.', (float) $recharge->amount, [
            'Due cleared' => $result['due_cleared'], 'Balance added' => $result['balance_added'],
        ]);
    }

    // ----- Customer top-up -----

    private function topupSuccess(Request $request)
    {
        $topup = TopupRequest::find($request->query('topup_id'));
        if (! $topup) {
            return $this->page(false, 'Payment', 'Top-up not found.');
        }
        if ($topup->status === 'success') {
            return $this->page(true, 'Payment Successful', 'Money already added to your wallet.', (float) $topup->amount);
        }
        if ($topup->status === 'failed') {
            return $this->page(false, 'Payment Failed', 'This payment was not completed.');
        }

        $verify = $this->verify($request, $topup->payment_method, $topup->transaction_id);
        if (! $verify) {
            $topup->update(['status' => 'failed']);

            return $this->page(false, 'Payment Failed', 'Payment could not be verified.');
        }

        // Clears any outstanding sender due first, then tops up the balance.
        $this->wallet->processUserTopup($topup->user, (float) $topup->amount, 'top_up', 'Wallet add money #' . $topup->id);
        $topup->update(['status' => 'success', 'transaction_id' => $verify ?: $topup->transaction_id]);
        $topup->user->refresh();

        return $this->page(true, 'Payment Successful', 'Money added to your wallet.', (float) $topup->amount, [
            'New balance' => $topup->user->wallet_balance,
        ]);
    }

    // ----- Shared -----

    // Verify the payment server-side. Returns the gateway reference if paid, else null.
    private function verify(Request $request, string $paymentMethod, ?string $storedRef): ?string
    {
        $gateway = config('recharge.gateways')[$paymentMethod] ?? null;
        $reference = $request->query('session_id') ?: $storedRef;
        if (! $reference) {
            return null;
        }

        $res = $this->payments->verify($reference, $gateway);

        return $res->isPaid ? ($res->reference ?: $reference) : null;
    }

    private function page(bool $ok, string $title, string $message, ?float $amount = null, array $rows = [])
    {
        return response()->view('payment.result', [
            'ok' => $ok,
            'title' => $title,
            'message' => $message,
            'amount' => $amount,
            'rows' => $rows,
        ]);
    }
}
