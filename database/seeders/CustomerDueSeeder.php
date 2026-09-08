<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Database\Seeder;

/**
 * Demo sender (customer) dues for the Parcel-COD "sender pays delivery" feature.
 *
 * Creates a handful of customers each carrying a delivery-charge due, routed
 * through WalletService so users.due_amount and the user_due_transactions ledger
 * stay consistent (and the admin Customer Dues page shows real history).
 *
 * Run standalone:  php artisan db:seed --class=CustomerDueSeeder
 */
class CustomerDueSeeder extends Seeder
{
    public function run(WalletService $wallet): void
    {
        // Idempotency guard — don't double-seed.
        if (User::where('phone', 'like', '017600000%')->exists()) {
            $this->command?->warn('CustomerDueSeeder: demo due customers already exist — skipping.');

            return;
        }

        $limit = (float) SystemSetting::get('sender_due_limit_amount', 500);

        // [name, wallet_balance, [ [dueAmount, orderNumber], ... ], amountPaidBack|null]
        $defs = [
            ['Mizanur Rahman', 200.00, [['97.10', 'RR-2026-00051']], null],
            ['Shirin Akter',     0.00, [['125.00', 'RR-2026-00052'], ['80.00', 'RR-2026-00061']], null],
            ['Kamal Uddin',    500.00, [['300.00', 'RR-2026-00047'], ['260.00', 'RR-2026-00070']], null], // 560 → over limit
            ['Rupa Das',       150.00, [['150.00', 'RR-2026-00033']], '150.00'], // added then fully cleared → history only
            ['Jahangir Alam',   50.00, [['520.00', 'RR-2026-00080']], null], // over limit → COD blocked
        ];

        foreach ($defs as $i => [$name, $balance, $adds, $paidBack]) {
            $user = User::create([
                'name' => $name,
                'phone' => '0176000000' . ($i + 1),
                'email' => 'duedemo' . ($i + 1) . '@readyride.test',
                'wallet_balance' => $balance,
                'referral_code' => 'DUE' . (1000 + $i),
                'is_active' => true,
            ]);

            // A top-up transaction so the wallet history isn't empty.
            if ($balance > 0) {
                WalletTransaction::create([
                    'owner_type' => 'user', 'owner_id' => $user->id, 'type' => 'credit',
                    'category' => 'top_up', 'amount' => $balance, 'balance_before' => 0,
                    'balance_after' => $balance, 'note' => 'Wallet top-up',
                ]);
            }

            // Add each delivery-charge due (order_id left null; the RR number lives in the note).
            foreach ($adds as [$amount, $orderNumber]) {
                $wallet->addUserDue($user, (float) $amount, null, 'Delivery charge due ' . $orderNumber);
            }

            // Optionally simulate a top-up that cleared the due (leaves a "paid" ledger row).
            if ($paidBack !== null) {
                $wallet->payUserDue($user, (float) $paidBack, 'Top-up — due cleared');
            }

            $user->refresh();
            $flag = $user->due_amount >= $limit ? ' (OVER LIMIT — COD blocked)' : '';
            $this->command?->info(sprintf('  • %s — due %s%s', $name, number_format((float) $user->due_amount, 2), $flag));
        }

        $totalDue = (float) User::where('phone', 'like', '017600000%')->sum('due_amount');
        $this->command?->info('CustomerDueSeeder: done. Total demo due = ' . number_format($totalDue, 2) . ' (limit ' . number_format($limit, 2) . ').');
    }
}
