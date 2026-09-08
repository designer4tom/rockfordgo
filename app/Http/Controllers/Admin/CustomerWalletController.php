<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CustomerWalletController extends Controller implements HasMiddleware
{
    public function __construct(
        private WalletService $wallet,
        private NotificationService $notifications,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments,write'),
        ];
    }

    // Admin manually clears a customer's outstanding sender due (COD delivery charge).
    public function clearDue(Request $request, string $id)
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $customer = User::findOrFail($id);

        if ($customer->due_amount <= 0) {
            return back()->with('warning', 'This customer has no outstanding due.');
        }

        $this->wallet->payUserDue($customer, (float) $customer->due_amount, 'Admin cleared: ' . $request->reason);

        $this->notify($customer, 'Due cleared',
            'আপনার delivery charge due পরিশোধ হিসেবে গণ্য করা হয়েছে।');

        return back()->with('success', 'Due cleared for ' . $customer->name . '.');
    }

    // Admin recharge — clears outstanding sender due first, then tops up the
    // balance (same logic as the customer app top-up / driver recharge). Logged
    // as a TopupRequest with the admin's id.
    public function recharge(Request $request, string $id)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = User::findOrFail($id);
        $note = 'Admin recharge' . (! empty($data['note']) ? ': ' . $data['note'] : '');

        $result = $this->wallet->processUserTopup($customer, (float) $data['amount'], 'top_up', $note);

        \App\Models\TopupRequest::create([
            'user_id' => $customer->id,
            'amount' => $data['amount'],
            'payment_method' => 'admin',
            'status' => 'success',
            'admin_id' => auth('admin')->id(),
        ]);

        $this->notify($customer, 'Wallet recharged',
            'আপনার wallet এ ' . number_format($result['balance_added'], 2) . ' যোগ হয়েছে'
            . ($result['due_cleared'] > 0 ? ' এবং due ' . number_format($result['due_cleared'], 2) . ' পরিশোধ হয়েছে' : '') . '।');

        return back()->with('success', sprintf(
            'Recharged %s for %s — due cleared %s, balance added %s.',
            number_format((float) $data['amount'], 2),
            $customer->name,
            number_format($result['due_cleared'], 2),
            number_format($result['balance_added'], 2),
        ));
    }

    // Manual wallet credit/debit by admin.
    public function adjust(Request $request, string $id)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:add,deduct'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $customer = User::findOrFail($id);

        // Guard: a deduction must not exceed the current balance.
        if ($data['direction'] === 'deduct' && $data['amount'] > (float) $customer->wallet_balance) {
            return back()->withErrors(['amount' => 'Deduction exceeds the customer\'s wallet balance.']);
        }

        $note = 'Admin adjustment: ' . $data['reason'];
        if ($data['direction'] === 'add') {
            $this->wallet->creditUser($customer, (float) $data['amount'], 'top_up', null, $note);
        } else {
            $this->wallet->debitUser($customer, (float) $data['amount'], 'top_up', null, $note);
        }

        $this->notify($customer, 'Wallet update',
            'আপনার wallet ' . ($data['direction'] === 'add' ? 'এ ' . $data['amount'] . ' যোগ' : ' থেকে ' . $data['amount'] . ' বাদ') . ' করা হয়েছে।');

        return back()->with('success', 'Wallet adjusted for ' . $customer->name . '.');
    }

    // Manual refund into the customer's wallet.
    public function refund(Request $request, string $id)
    {
        $data = $request->validate([
            'order_id' => ['nullable', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $customer = User::findOrFail($id);

        // If an order is supplied, make sure it belongs to this customer.
        if (! empty($data['order_id'])) {
            $owns = Order::where('id', $data['order_id'])->where('user_id', $customer->id)->exists();
            if (! $owns) {
                return back()->withErrors(['order_id' => 'That order does not belong to this customer.']);
            }
        }

        $this->wallet->refundToCustomer($customer, (float) $data['amount'], $data['order_id'] ?? null, $data['reason']);

        $this->notify($customer, 'Refund received',
            'আপনার wallet এ ' . $data['amount'] . ' refund করা হয়েছে। কারণ: ' . $data['reason']);

        return back()->with('success', 'Refund of ' . $data['amount'] . ' issued to ' . $customer->name . '.');
    }

    // ---------------------------------------------------------------------

    private function notify(User $customer, string $title, string $body, string $type = 'payment'): void
    {
        $this->notifications->sendPush('user', $customer->id, $title, $body, $type);
    }
}
