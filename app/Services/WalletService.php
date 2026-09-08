<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DueTransaction;
use App\Models\Order;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserDueTransaction;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for every wallet / due / commission operation.
 * Controllers must go through this service rather than touching balances
 * directly, so that the ledger (wallet_transactions / due_transactions)
 * always stays consistent with the cached balance columns.
 */
class WalletService
{
    // ----- Driver wallet -----

    public function creditDriver(Driver $driver, float $amount, string $category, ?int $orderId = null, ?string $note = null): WalletTransaction
    {
        return $this->move('driver', $driver, $amount, 'credit', $category, $orderId, $note);
    }

    public function debitDriver(Driver $driver, float $amount, string $category, ?int $orderId = null, ?string $note = null): WalletTransaction
    {
        return $this->move('driver', $driver, $amount, 'debit', $category, $orderId, $note);
    }

    // ----- Customer wallet -----

    public function creditUser(User $user, float $amount, string $category, ?int $orderId = null, ?string $note = null): WalletTransaction
    {
        return $this->move('user', $user, $amount, 'credit', $category, $orderId, $note);
    }

    public function debitUser(User $user, float $amount, string $category, ?int $orderId = null, ?string $note = null): WalletTransaction
    {
        return $this->move('user', $user, $amount, 'debit', $category, $orderId, $note);
    }

    // ----- Driver due -----

    public function addDriverDue(Driver $driver, float $amount, ?int $orderId = null, ?string $note = null): DueTransaction
    {
        return DB::transaction(function () use ($driver, $amount, $orderId, $note) {
            $driver->refresh();
            $before = (float) $driver->due_amount;
            $after = $before + $amount;

            $tx = DueTransaction::create([
                'driver_id' => $driver->id,
                'order_id' => $orderId,
                'type' => 'added',
                'amount' => $amount,
                'due_before' => $before,
                'due_after' => $after,
                'note' => $note,
            ]);

            $driver->update(['due_amount' => $after]);
            $this->syncAcceptFlag($driver);

            return $tx;
        });
    }

    public function payDriverDue(Driver $driver, float $amount, ?string $note = null): DueTransaction
    {
        return DB::transaction(function () use ($driver, $amount, $note) {
            $driver->refresh();
            $before = (float) $driver->due_amount;
            $after = max(0, $before - $amount);

            $tx = DueTransaction::create([
                'driver_id' => $driver->id,
                'type' => 'paid',
                'amount' => $amount,
                'due_before' => $before,
                'due_after' => $after,
                'note' => $note,
            ]);

            $driver->update(['due_amount' => $after]);
            $this->syncAcceptFlag($driver);

            return $tx;
        });
    }

    // ----- Sender (customer) due -----
    // Parcel-COD only: when the receiver does not pay the delivery charge, it is
    // billed to the sender; whatever their wallet can't cover becomes a due.

    public function addUserDue(User $user, float $amount, ?int $orderId = null, ?string $note = null): UserDueTransaction
    {
        return DB::transaction(function () use ($user, $amount, $orderId, $note) {
            $user->refresh();
            $before = (float) $user->due_amount;
            $after = $before + $amount;

            $tx = UserDueTransaction::create([
                'user_id' => $user->id,
                'order_id' => $orderId,
                'type' => 'added',
                'amount' => $amount,
                'due_before' => $before,
                'due_after' => $after,
                'note' => $note,
            ]);

            $user->update(['due_amount' => $after]);

            return $tx;
        });
    }

    public function payUserDue(User $user, float $amount, ?string $note = null): UserDueTransaction
    {
        return DB::transaction(function () use ($user, $amount, $note) {
            $user->refresh();
            $before = (float) $user->due_amount;
            $after = max(0, $before - $amount);

            $tx = UserDueTransaction::create([
                'user_id' => $user->id,
                'type' => 'paid',
                'amount' => $amount,
                'due_before' => $before,
                'due_after' => $after,
                'note' => $note,
            ]);

            $user->update(['due_amount' => $after]);

            return $tx;
        });
    }

    // A sender may place a new COD parcel only while their due is under the limit.
    public function checkUserDueLimit(User $user): bool
    {
        $limit = (float) SystemSetting::get('sender_due_limit_amount', 500);

        return (float) $user->due_amount < $limit;
    }

    // Apply a customer wallet top-up: clear any outstanding sender due first, then
    // put the remainder into the wallet balance (same model as a driver recharge).
    public function processUserTopup(User $user, float $amount, string $category = 'top_up', ?string $note = null): array
    {
        return DB::transaction(function () use ($user, $amount, $category, $note) {
            $user->refresh();
            $remaining = $amount;
            $dueCleared = 0.0;

            $due = (float) $user->due_amount;
            if ($due > 0) {
                $dueCleared = min($remaining, $due);
                $this->payUserDue($user, $dueCleared, $note ?? 'Top-up — due cleared');
                $remaining -= $dueCleared;
            }

            $balanceAdded = 0.0;
            if ($remaining > 0) {
                $this->creditUser($user, $remaining, $category, null, $note ?? 'Wallet top-up');
                $balanceAdded = $remaining;
            }

            $user->refresh();

            return [
                'due_cleared' => round($dueCleared, 2),
                'balance_added' => round($balanceAdded, 2),
                'new_balance' => round((float) $user->wallet_balance, 2),
                'new_due' => round((float) $user->due_amount, 2),
            ];
        });
    }

    // ----- Recharge -----

    /**
     * Apply a successful driver recharge: clear outstanding due first, then put
     * any remainder into the wallet balance. Returns the breakdown.
     *
     * Example: due 200, recharge 500 → due_cleared 200, balance_added 300.
     */
    public function processDriverRecharge(Driver $driver, float $amount, ?string $note = null): array
    {
        return DB::transaction(function () use ($driver, $amount, $note) {
            $driver->refresh();
            $remaining = $amount;
            $dueCleared = 0.0;

            // 1) Pay down the due first.
            $due = (float) $driver->due_amount;
            if ($due > 0) {
                $dueCleared = min($remaining, $due);
                $this->payDriverDue($driver, $dueCleared, $note ?? 'Recharge — due cleared');
                $remaining -= $dueCleared;
            }

            // 2) Whatever is left tops up the wallet balance.
            $balanceAdded = 0.0;
            if ($remaining > 0) {
                $this->creditDriver($driver, $remaining, 'top_up', null, $note ?? 'Wallet recharge');
                $balanceAdded = $remaining;
            }

            $driver->refresh();

            return [
                'due_cleared' => round($dueCleared, 2),
                'balance_added' => round($balanceAdded, 2),
                'new_balance' => round((float) $driver->wallet_balance, 2),
                'new_due' => round((float) $driver->due_amount, 2),
            ];
        });
    }

    // ----- Commission -----

    // Split a completed ride between admin commission and driver earning.
    public function processTripCommission(Order $order): array
    {
        $admin = (float) ($order->admin_commission ?? 0);
        $driverEarning = (float) ($order->driver_earning ?? max(0, (float) $order->total_amount - $admin));

        if (! $order->driver) {
            return ['admin_amount' => $admin, 'driver_amount' => $driverEarning];
        }

        $driver = $order->driver;

        if (in_array($order->payment_method, ['online', 'wallet'], true)) {
            // Money flowed to the platform: credit the driver's earning.
            $this->creditDriver($driver, $driverEarning, 'trip_earning', $order->id, 'Trip ' . $order->order_number);
        } else {
            // Cash collected by the driver: recover the admin commission.
            $this->collectCommissionFromCash($driver, $admin, $order);
        }

        return ['admin_amount' => $admin, 'driver_amount' => $driverEarning];
    }

    // Parcel delivery split (same model as a ride for the earning/commission piece).
    public function processParcelCommission(Order $order): array
    {
        $admin = (float) ($order->admin_commission ?? 0);
        $driverEarning = (float) ($order->driver_earning ?? max(0, (float) ($order->delivery_charge ?? 0) - $admin));

        if (! $order->driver) {
            return ['admin_amount' => $admin, 'driver_amount' => $driverEarning];
        }

        $driver = $order->driver;

        if (in_array($order->payment_method, ['online', 'wallet'], true)) {
            $this->creditDriver($driver, $driverEarning, 'parcel_earning', $order->id, 'Parcel ' . $order->order_number);
        } else {
            $this->collectCommissionFromCash($driver, $admin, $order);
        }

        return ['admin_amount' => $admin, 'driver_amount' => $driverEarning];
    }

    // COD parcel settlement.
    // The receiver pays only the product price (cod_amount); the rider collects
    // that cash and the full amount is paid out to the sender. The delivery charge
    // is billed to the SENDER's wallet — whatever the wallet can't cover becomes a
    // sender due. The driver still receives their share of the delivery charge.
    public function processCodCollection(Order $order, float $collectedAmount): array
    {
        $deliveryCharge = (float) ($order->delivery_charge ?? 0);
        $admin = (float) ($order->admin_commission ?? 0);
        $driverShare = max(0, $deliveryCharge - $admin);
        // The whole collected amount is the sender's product money now that the
        // delivery charge is billed to the sender separately.
        $productPrice = max(0, $collectedAmount);

        return DB::transaction(function () use ($order, $productPrice, $deliveryCharge, $driverShare, $admin) {
            $senderCharged = 0.0;
            $senderDueAdded = 0.0;

            if ($order->user) {
                // 1) Bill the delivery charge to the sender's CURRENT balance first;
                //    the uncovered remainder is recorded as a sender due.
                if ($deliveryCharge > 0) {
                    $balance = (float) $order->user->fresh()->wallet_balance;
                    $senderCharged = min($balance, $deliveryCharge);
                    if ($senderCharged > 0) {
                        $this->debitUser($order->user, $senderCharged, 'parcel_delivery_charge', $order->id, 'Delivery charge ' . $order->order_number);
                    }
                    $senderDueAdded = round($deliveryCharge - $senderCharged, 2);
                    if ($senderDueAdded > 0) {
                        $this->addUserDue($order->user, $senderDueAdded, $order->id, 'Delivery charge due ' . $order->order_number);
                    }
                }

                // 2) Pay the product price out to the sender (the customer who shipped it).
                if ($productPrice > 0) {
                    $this->creditUser($order->user, $productPrice, 'cod_payout', $order->id, 'COD payout ' . $order->order_number);
                }
            }

            // 3) Driver keeps their share of the delivery charge.
            if ($order->driver && $driverShare > 0) {
                $this->creditDriver($order->driver, $driverShare, 'parcel_earning', $order->id, 'COD delivery share ' . $order->order_number);
            }

            return [
                'sender_payout' => $productPrice,
                'delivery_charge' => $deliveryCharge,
                'sender_charged' => round($senderCharged, 2),
                'sender_due_added' => $senderDueAdded,
                'admin_amount' => $admin,
                'driver_amount' => $driverShare,
            ];
        });
    }

    // ----- Due limit -----

    public function checkDueLimit(Driver $driver): bool
    {
        $limit = (float) SystemSetting::get('due_limit_amount', 500);

        return (float) $driver->due_amount < $limit;
    }

    // ----- Refund -----

    public function refundToCustomer(User $user, float $amount, ?int $orderId = null, string $reason = ''): WalletTransaction
    {
        $note = trim('Refund' . ($reason !== '' ? ': ' . $reason : ''));

        return $this->creditUser($user, $amount, 'refund', $orderId, $note);
    }

    // ---------------------------------------------------------------------

    // Core ledger write: records the transaction and updates the cached balance.
    private function move(string $ownerType, Driver|User $owner, float $amount, string $type, string $category, ?int $orderId, ?string $note): WalletTransaction
    {
        return DB::transaction(function () use ($ownerType, $owner, $amount, $type, $category, $orderId, $note) {
            $owner->refresh();
            $before = (float) $owner->wallet_balance;
            $after = $type === 'credit' ? $before + $amount : max(0, $before - $amount);

            $tx = WalletTransaction::create([
                'owner_type' => $ownerType,
                'owner_id' => $owner->id,
                'order_id' => $orderId,
                'type' => $type,
                'category' => $category,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'note' => $note,
            ]);

            $owner->update(['wallet_balance' => $after]);

            return $tx;
        });
    }

    // When a driver pays in cash, recover the commission from their wallet;
    // fall back to adding it as due if the balance is short.
    private function collectCommissionFromCash(Driver $driver, float $commission, Order $order): void
    {
        if ($commission <= 0) {
            return;
        }

        $driver->refresh();

        if ((float) $driver->wallet_balance >= $commission) {
            $this->debitDriver($driver, $commission, 'commission', $order->id, 'Commission for ' . $order->order_number);
        } else {
            $this->addDriverDue($driver, $commission, $order->id, 'Commission due for ' . $order->order_number);
        }
    }

    // Keep the can_accept_orders flag in sync with the due limit. When a driver
    // crosses the limit (was allowed, now blocked), fire the due_limit rule event.
    private function syncAcceptFlag(Driver $driver): void
    {
        $wasAllowed = (bool) $driver->can_accept_orders;
        $nowAllowed = $this->checkDueLimit($driver);
        $driver->update(['can_accept_orders' => $nowAllowed]);

        if ($wasAllowed && ! $nowAllowed) {
            app(NotificationService::class)->notifyEvent(
                'due_limit', 'driver', $driver->id,
                'Due limit reached',
                'Your due limit has been exceeded. Please recharge to accept new orders.',
                'due_limit', [], $driver->phone,
            );
        }
    }
}
