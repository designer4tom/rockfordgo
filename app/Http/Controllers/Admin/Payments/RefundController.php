<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RefundController extends Controller implements HasMiddleware
{
    public function __construct(private WalletService $wallet)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments,read', only: ['index']),
            new Middleware('permission:payments,write', only: ['store']),
        ];
    }

    public function index(Request $request)
    {
        $refunds = WalletTransaction::where('category', 'refund')
            ->with('order:id,order_number')
            ->latest()
            ->paginate(20);

        // Resolve customer names (refunds always target users).
        $userIds = $refunds->getCollection()->pluck('owner_id')->unique();
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'phone'])->keyBy('id');
        foreach ($refunds->getCollection() as $tx) {
            $tx->customer = $users[$tx->owner_id] ?? null;
        }

        return view('admin.payments.refunds.index', [
            'refunds' => $refunds,
            'recentCustomers' => User::latest()->limit(50)->get(['id', 'name', 'phone']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $user = User::findOrFail($data['user_id']);

        // If an order is supplied, ensure it belongs to the customer.
        if (! empty($data['order_id'])) {
            $order = Order::find($data['order_id']);
            if (! $order || $order->user_id !== $user->id) {
                return back()->withErrors(['order_id' => 'That order does not belong to this customer.']);
            }
        }

        $this->wallet->refundToCustomer($user, (float) $data['amount'], $data['order_id'] ?? null, $data['reason']);

        // Link to a dispute on the same order if one exists.
        if (! empty($data['order_id'])) {
            Order::find($data['order_id'])?->dispute?->update([
                'refund_issued' => true,
                'refund_amount' => $data['amount'],
            ]);
        }

        app(\App\Services\NotificationService::class)->sendPush('user', $user->id, 'Refund received',
            'আপনার wallet এ ' . number_format($data['amount'], 2) . ' refund করা হয়েছে। কারণ: ' . $data['reason'], 'payment');

        return back()->with('success', 'Refund of ' . number_format($data['amount'], 2) . ' issued to ' . $user->name . '.');
    }
}
