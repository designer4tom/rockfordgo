<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WithdrawalController extends Controller implements HasMiddleware
{
    public function __construct(private WalletService $wallet)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments,read', only: ['index']),
            new Middleware('permission:payments,write', only: ['approve', 'reject', 'bulkApprove']),
        ];
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'pending');       // pending | approved | rejected
        $owner = $request->query('owner', 'driver');     // driver | customer
        $statusMap = ['pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected'];

        $withdrawals = WithdrawalRequest::with(['driver:id,name,phone', 'user:id,name,phone', 'processedBy:id,name'])
            ->when($owner === 'customer', fn ($q) => $q->whereNotNull('user_id'), fn ($q) => $q->whereNull('user_id'))
            ->when(isset($statusMap[$tab]), fn ($q) => $q->where('status', $statusMap[$tab]))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.withdrawals.index', [
            'withdrawals' => $withdrawals,
            'tab' => $tab,
            'owner' => $owner,
            'pendingCount' => WithdrawalRequest::where('status', 'pending')->whereNull('user_id')->count(),
            'pendingCustomerCount' => WithdrawalRequest::where('status', 'pending')->whereNotNull('user_id')->count(),
        ]);
    }

    public function approve(string $id)
    {
        $withdrawal = WithdrawalRequest::with(['driver', 'user'])->findOrFail($id);
        $result = $this->approveOne($withdrawal);

        return back()->with($result['type'], $result['message']);
    }

    public function reject(Request $request, string $id)
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $withdrawal = WithdrawalRequest::with(['driver', 'user'])->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('warning', 'This request has already been processed.');
        }

        $withdrawal->update([
            'status' => 'rejected',
            'processed_by' => auth('admin')->id(),
            'processed_at' => now(),
            'rejection_reason' => $request->reason,
        ]);

        $this->notifyOwner($withdrawal, 'Withdrawal rejected',
            'আপনার ' . number_format($withdrawal->amount, 2) . ' withdrawal request reject হয়েছে। কারণ: ' . $request->reason);

        return back()->with('success', 'Withdrawal rejected.');
    }

    public function bulkApprove(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:withdrawal_requests,id'],
        ]);

        $withdrawals = WithdrawalRequest::with(['driver', 'user'])
            ->whereIn('id', $data['ids'])->where('status', 'pending')->get();

        $approved = 0;
        $skipped = 0;
        foreach ($withdrawals as $withdrawal) {
            $result = $this->approveOne($withdrawal);
            $result['type'] === 'success' ? $approved++ : $skipped++;
        }

        $msg = $approved . ' withdrawal(s) approved.';
        if ($skipped) {
            $msg .= ' ' . $skipped . ' skipped (insufficient balance).';
        }

        return back()->with('success', $msg);
    }

    // ---------------------------------------------------------------------

    // Approve a single request: balance check then debit the owner's wallet
    // (driver or customer) via WalletService — the single source of truth.
    private function approveOne(WithdrawalRequest $withdrawal): array
    {
        if ($withdrawal->status !== 'pending') {
            return ['type' => 'warning', 'message' => 'This request has already been processed.'];
        }

        $owner = $withdrawal->user_id ? $withdrawal->user : $withdrawal->driver;
        if (! $owner) {
            return ['type' => 'error', 'message' => 'Account holder not found.'];
        }

        if ((float) $owner->wallet_balance < (float) $withdrawal->amount) {
            return ['type' => 'error', 'message' => $owner->name . ' has insufficient wallet balance.'];
        }

        $note = 'Withdrawal #' . $withdrawal->id . ' approved';
        if ($withdrawal->user_id) {
            $this->wallet->debitUser($owner, (float) $withdrawal->amount, 'withdrawal', null, $note);
        } else {
            $this->wallet->debitDriver($owner, (float) $withdrawal->amount, 'withdrawal', null, $note);
        }

        $withdrawal->update([
            'status' => 'approved',
            'processed_by' => auth('admin')->id(),
            'processed_at' => now(),
        ]);

        $this->notifyOwner($withdrawal, 'Withdrawal approved',
            'আপনার ' . number_format($withdrawal->amount, 2) . ' withdrawal approve করা হয়েছে।', 'withdrawal_approved');

        return ['type' => 'success', 'message' => 'Withdrawal for ' . $owner->name . ' approved.'];
    }

    // Notify the driver or customer in-app + push. When $event is a Notification
    // Rule key, routes through the rule engine (push + SMS per the admin toggles).
    private function notifyOwner(WithdrawalRequest $withdrawal, string $title, string $body, ?string $event = null): void
    {
        $type = $withdrawal->user_id ? 'user' : 'driver';
        $id = $withdrawal->user_id ?: $withdrawal->driver_id;
        $svc = app(\App\Services\NotificationService::class);

        if ($event) {
            $phone = $type === 'user'
                ? \App\Models\User::whereKey($id)->value('phone')
                : \App\Models\Driver::whereKey($id)->value('phone');
            $svc->notifyEvent($event, $type, $id, $title, $body, 'withdrawal', [], $phone);
        } else {
            $svc->sendPush($type, $id, $title, $body, 'withdrawal');
        }
    }
}
