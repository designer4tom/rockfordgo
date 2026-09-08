<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DriverComplaintController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $disputes = Dispute::where('raised_by', 'driver')
            ->where('raised_by_id', $request->user()->id)
            ->with('order:id,order_number')
            ->latest()
            ->paginate(20);

        $items = $disputes->getCollection()->map(fn ($d) => [
            'id' => $d->id,
            'order_number' => $d->order->order_number ?? null,
            'category' => $d->category,
            'description' => $d->description,
            'status' => $d->status,
            'admin_note' => $d->admin_note,
            'created_at' => $d->created_at->toISOString(),
        ])->all();

        return $this->paginated($disputes, $items, 'Complaints fetched.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'category' => ['required', 'in:driver_behavior,route_issue,overcharging,parcel_issue,payment_issue,other'],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $owns = Order::where('id', $data['order_id'])->where('driver_id', $request->user()->id)->exists();
        if (! $owns) {
            return $this->error('Order not found.', 404);
        }

        $dispute = Dispute::create([
            'order_id' => $data['order_id'],
            'raised_by' => 'driver',
            'raised_by_id' => $request->user()->id,
            'category' => $data['category'],
            'description' => $data['description'],
            'status' => 'open',
        ]);

        return $this->success(['complaint_id' => $dispute->id], 'Complaint submitted.');
    }
}
