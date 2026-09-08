<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DriverWalletController extends Controller implements HasMiddleware
{
    public function __construct(private WalletService $wallet)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments,write'),
        ];
    }

    // Admin manually clears a driver's outstanding due.
    public function clearDue(Request $request, string $id)
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $driver = Driver::findOrFail($id);

        if ($driver->due_amount <= 0) {
            return back()->with('warning', 'This driver has no outstanding due.');
        }

        // WalletService records the due_transaction AND re-syncs can_accept_orders.
        $this->wallet->payDriverDue($driver, (float) $driver->due_amount, 'Admin cleared: ' . $request->reason);

        return back()->with('success', 'Due cleared for ' . $driver->name . '.');
    }

    // Admin manual recharge — clears due first, then tops up balance (same
    // logic as a driver app recharge). Logged with the admin's id.
    public function recharge(Request $request, string $id)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $driver = Driver::findOrFail($id);
        $note = 'Admin recharge' . (! empty($data['note']) ? ': ' . $data['note'] : '');

        $result = $this->wallet->processDriverRecharge($driver, (float) $data['amount'], $note);

        \App\Models\RechargeRequest::create([
            'driver_id' => $driver->id,
            'amount' => $data['amount'],
            'payment_method' => 'admin',
            'status' => 'success',
            'due_cleared' => $result['due_cleared'],
            'balance_added' => $result['balance_added'],
            'admin_id' => auth('admin')->id(),
        ]);

        return back()->with('success', sprintf(
            'Recharged %s for %s — due cleared %s, balance added %s.',
            number_format((float) $data['amount'], 2),
            $driver->name,
            number_format($result['due_cleared'], 2),
            number_format($result['balance_added'], 2),
        ));
    }

    // Manual wallet credit/debit by admin.
    public function adjust(Request $request, string $id)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:add,deduct'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $driver = Driver::findOrFail($id);
        $note = 'Admin adjustment: ' . $data['reason'];

        if ($data['direction'] === 'add') {
            $this->wallet->creditDriver($driver, (float) $data['amount'], 'top_up', null, $note);
        } else {
            $this->wallet->debitDriver($driver, (float) $data['amount'], 'withdrawal', null, $note);
        }

        return back()->with('success', 'Wallet adjusted for ' . $driver->name . '.');
    }
}
