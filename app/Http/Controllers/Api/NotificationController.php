<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NotificationController extends Controller
{
    use ApiResponse;

    public function userIndex(Request $request)
    {
        return $this->list($request, 'user', $request->user()->id);
    }

    public function driverIndex(Request $request)
    {
        return $this->list($request, 'driver', $request->user()->id);
    }

    public function markRead(Request $request)
    {
        return $this->doMarkRead($request, 'user', $request->user()->id);
    }

    public function driverMarkRead(Request $request)
    {
        return $this->doMarkRead($request, 'driver', $request->user()->id);
    }

    // ---------------------------------------------------------------------

    private function list(Request $request, string $type, int $id)
    {
        $base = Notification::where('notifiable_type', $type)->where('notifiable_id', $id);

        $unread = (clone $base)->whereNull('read_at')->count();

        $query = (clone $base)
            ->when($request->boolean('unread_only'), fn ($q) => $q->whereNull('read_at'))
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        // Driver notifications → attach the related order's customer (name + image).
        $customers = [];
        if ($type === 'driver') {
            $orderIds = $query->getCollection()
                ->map(fn ($n) => $n->data['order_id'] ?? null)
                ->filter()->unique()->values()->all();

            if ($orderIds) {
                $customers = Order::whereIn('id', $orderIds)->with('user:id,name,avatar')->get()
                    ->mapWithKeys(fn ($o) => [$o->id => $o->user])->all();
            }
        }

        $items = $query->getCollection()->map(function ($n) use ($type, $customers) {
            $item = [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'type' => $n->type,
                'data' => $n->data,
                'is_read' => ! is_null($n->read_at),
                'created_at' => $n->created_at->toISOString(),
            ];

            if ($type === 'driver') {
                $u = $customers[$n->data['order_id'] ?? null] ?? null;
                $item['customer_name'] = $u->name ?? null;
                $item['customer_image'] = ($u && $u->avatar) ? asset(Storage::url($u->avatar)) : null;
            }

            return $item;
        })->all();

        return $this->paginated($query, ['unread_count' => $unread, 'notifications' => $items], 'Notifications fetched.');
    }

    private function doMarkRead(Request $request, string $type, int $id)
    {
        $data = $request->validate([
            'notification_ids' => ['nullable', 'array'],
            'mark_all' => ['nullable', 'boolean'],
        ]);

        $base = Notification::where('notifiable_type', $type)->where('notifiable_id', $id)->whereNull('read_at');

        if (! empty($data['mark_all'])) {
            $base->update(['read_at' => now()]);
        } elseif (! empty($data['notification_ids'])) {
            $base->whereIn('id', $data['notification_ids'])->update(['read_at' => now()]);
        }

        return $this->success(null, 'Notifications marked as read.');
    }
}
