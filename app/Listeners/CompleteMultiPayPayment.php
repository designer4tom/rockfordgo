<?php

namespace App\Listeners;

use Abedin\MultiPay\Events\PaymentFailed;
use Abedin\MultiPay\Events\PaymentSucceeded;
use App\Models\Order;
use App\Models\RechargeRequest;
use App\Models\TopupRequest;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Log;

/**
 * Single fulfilment point for every MultiPay transaction in this app.
 *
 * MultiPay::confirm() verifies server-side then fires PaymentSucceeded /
 * PaymentFailed. We map the session's order_id back to the originating record
 * and run the SAME wallet action the legacy PaymentCallbackController used, so
 * all money movement is driven by verified gateway events.
 *
 * order_id conventions (set at initiation):
 *   recharge-{id}  → driver wallet recharge (due cleared first, then balance)
 *   topup-{id}     → customer wallet add-money
 *   order-{id}     → post-trip online payment (ride/parcel): mark the order
 *                    paid + settle the driver's earning (total − commission)
 *
 * Idempotent: only a still-`pending` record is completed, so calling confirm()
 * from both the browser return and the webhook is safe.
 */
class CompleteMultiPayPayment
{
    public function __construct(
        private WalletService $wallet,
        private NotificationService $notifications,
    ) {
    }

    public function handleSucceeded(PaymentSucceeded $event): void
    {
        [$type, $id] = $this->parse($event->session->order_id);
        if (! $id) {
            return;
        }

        $ref = $event->result->paymentId ?? $event->session->payment_id ?? null;

        match ($type) {
            'recharge' => $this->completeRecharge((int) $id, $ref),
            'topup' => $this->completeTopup((int) $id, $ref),
            'order' => $this->completeOrderPayment((int) $id, $ref),
            default => null,
        };
    }

    /**
     * Post-trip online payment verified: mark the order paid, then run the
     * SAME commission split used for wallet trips — the driver is credited
     * total − admin commission (processTripCommission handles the math).
     * Idempotent via the payment_status guard.
     */
    private function completeOrderPayment(int $id, ?string $ref): void
    {
        $order = Order::where('id', $id)->where('payment_status', '!=', 'paid')->first();
        if (! $order) {
            return;
        }

        $order->update(['payment_status' => 'paid', 'payment_intent_id' => $ref]);
        $order = $order->fresh();

        $split = $order->type === 'parcel'
            ? $this->wallet->processParcelCommission($order)
            : $this->wallet->processTripCommission($order);

        Log::info("MultiPay order-{$id} paid: driver credited {$split['driver_amount']}, commission {$split['admin_amount']}.");

        if ($order->driver_id) {
            $this->notifications->sendPush(
                'driver',
                $order->driver_id,
                'Payment received',
                'Trip ' . $order->order_number . ' paid online — earning added to your wallet.',
                'payment', // must be a value of the notifications.type enum
                ['order_id' => (string) $order->id],
            );
        }
    }

    public function handleFailed(PaymentFailed $event): void
    {
        [$type, $id] = $this->parse($event->session->order_id);
        if (! $id) {
            return;
        }

        if ($type === 'recharge') {
            RechargeRequest::where('id', $id)->where('status', 'pending')->update(['status' => 'failed']);
        } elseif ($type === 'topup') {
            TopupRequest::where('id', $id)->where('status', 'pending')->update(['status' => 'failed']);
        }
    }

    // ---------------------------------------------------------------------

    private function completeRecharge(int $id, ?string $ref): void
    {
        $recharge = RechargeRequest::with('driver')->find($id);
        if (! $recharge || $recharge->status !== 'pending' || ! $recharge->driver) {
            return; // missing or already processed → idempotent no-op
        }

        $result = $this->wallet->processDriverRecharge(
            $recharge->driver,
            (float) $recharge->amount,
            'Recharge #' . $recharge->id . ' (' . $recharge->payment_method . ')'
        );

        $recharge->update([
            'status' => 'success',
            'transaction_id' => $ref ?: $recharge->transaction_id,
            'due_cleared' => $result['due_cleared'],
            'balance_added' => $result['balance_added'],
        ]);

        Log::info('MultiPay recharge completed', ['recharge_id' => $id, 'ref' => $ref]);
    }

    private function completeTopup(int $id, ?string $ref): void
    {
        $topup = TopupRequest::with('user')->find($id);
        if (! $topup || $topup->status !== 'pending' || ! $topup->user) {
            return;
        }

        // Clears any outstanding sender due first, then tops up the balance.
        $this->wallet->processUserTopup($topup->user, (float) $topup->amount, 'top_up', 'Wallet add money #' . $topup->id);

        $topup->update([
            'status' => 'success',
            'transaction_id' => $ref ?: $topup->transaction_id,
        ]);

        Log::info('MultiPay topup completed', ['topup_id' => $id, 'ref' => $ref]);
    }

    /** "recharge-12" → ['recharge', '12']; unknown → [null, null]. */
    private function parse(?string $orderId): array
    {
        if ($orderId && preg_match('/^(recharge|topup|order)-(\d+)$/', $orderId, $m)) {
            return [$m[1], $m[2]];
        }

        return [null, null];
    }
}
