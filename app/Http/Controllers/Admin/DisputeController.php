<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Driver;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DisputeController extends Controller implements HasMiddleware
{
    public function __construct(
        private WalletService $wallet,
        private NotificationService $notifications,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:disputes,read', only: ['index', 'show']),
            new Middleware('permission:disputes,write', only: ['update']),
        ];
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'open');
        $statusMap = ['open' => 'open', 'under_review' => 'under_review', 'resolved' => 'resolved'];

        $disputes = Dispute::with('order:id,order_number,status,user_id,driver_id')
            ->when(isset($statusMap[$tab]), fn ($q) => $q->where('status', $statusMap[$tab]))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('raised_by'), fn ($q) => $q->where('raised_by', $request->raised_by))
            ->when($request->input('has_refund') === 'yes', fn ($q) => $q->where('refund_issued', true))
            ->when($request->input('has_refund') === 'no', fn ($q) => $q->where('refund_issued', false))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $this->hydrateRaisers($disputes->getCollection());

        return view('admin.disputes.index', [
            'disputes' => $disputes,
            'tab' => $tab,
            'counts' => [
                'open' => Dispute::where('status', 'open')->count(),
                'under_review' => Dispute::where('status', 'under_review')->count(),
            ],
            'filters' => $request->only(['category', 'raised_by', 'has_refund', 'from', 'to']),
        ]);
    }

    public function show(string $id)
    {
        $dispute = Dispute::with([
            'order.user', 'order.driver', 'resolvedBy:id,name',
        ])->findOrFail($id);

        $raiser = $this->resolveRaiser($dispute);

        return view('admin.disputes.show', compact('dispute', 'raiser'));
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,under_review,resolved'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'issue_refund' => ['nullable', 'boolean'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $dispute = Dispute::with('order.user')->findOrFail($id);

        $dispute->status = $data['status'];
        if (array_key_exists('admin_note', $data)) {
            $dispute->admin_note = $data['admin_note'];
        }

        $refundMessage = '';

        // Issue a refund only once (rule #4).
        if (! empty($data['issue_refund']) && ! $dispute->refund_issued) {
            $amount = (float) ($data['refund_amount'] ?? $dispute->order?->total_amount ?? 0);
            $customer = $dispute->order?->user;

            if ($amount > 0 && $customer) {
                $this->wallet->refundToCustomer($customer, $amount, $dispute->order_id, 'Dispute #' . $dispute->id . ' resolution');
                $dispute->refund_issued = true;
                $dispute->refund_amount = $amount;
                $refundMessage = ' Refund of ' . number_format($amount, 2) . ' credited.';
            }
        } elseif (! empty($data['issue_refund']) && $dispute->refund_issued) {
            $refundMessage = ' (Refund already issued — skipped.)';
        }

        if ($data['status'] === 'resolved') {
            $dispute->resolved_by = auth('admin')->id();
            $dispute->resolved_at = now();
        }

        $dispute->save();

        // Notify the customer when resolved.
        if ($data['status'] === 'resolved' && $dispute->order?->user) {
            $this->notifications->sendPush('user', $dispute->order->user_id, 'Dispute resolved',
                'আপনার dispute resolve করা হয়েছে।' . ($dispute->refund_issued ? ' Refund issue করা হয়েছে।' : ''),
                'dispute', ['dispute_id' => (string) $dispute->id]);
        }

        return back()->with('success', 'Dispute updated.' . $refundMessage);
    }

    // ---------------------------------------------------------------------

    private function hydrateRaisers($collection): void
    {
        $userIds = $collection->where('raised_by', 'user')->pluck('raised_by_id')->unique();
        $driverIds = $collection->where('raised_by', 'driver')->pluck('raised_by_id')->unique();

        $users = User::whereIn('id', $userIds)->pluck('name', 'id');
        $drivers = Driver::whereIn('id', $driverIds)->pluck('name', 'id');

        foreach ($collection as $d) {
            $d->raiser_name = $d->raised_by === 'driver'
                ? ($drivers[$d->raised_by_id] ?? '#' . $d->raised_by_id)
                : ($users[$d->raised_by_id] ?? '#' . $d->raised_by_id);
        }
    }

    private function resolveRaiser(Dispute $dispute): ?array
    {
        $model = $dispute->raised_by === 'driver'
            ? Driver::find($dispute->raised_by_id)
            : User::find($dispute->raised_by_id);

        if (! $model) {
            return null;
        }

        return ['type' => $dispute->raised_by, 'name' => $model->name, 'phone' => $model->phone, 'id' => $model->id];
    }
}
