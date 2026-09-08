<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BroadcastNotification;
use App\Models\Notification;
use App\Models\ScheduledNotification;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class NotificationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,write', only: ['broadcastForm', 'sendBroadcast']),
            new Middleware('permission:settings,read', only: ['history']),
        ];
    }

    public function broadcastForm()
    {
        return view('admin.notifications.broadcast', [
            'zones' => Zone::orderBy('name')->get(),
        ]);
    }

    public function sendBroadcast(Request $request)
    {
        $data = $request->validate([
            'target' => ['required', 'in:all_customers,all_drivers,zone_customers,zone_drivers'],
            'zone_id' => ['nullable', 'required_if:target,zone_customers,zone_drivers', 'exists:zones,id'],
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
            'when' => ['required', 'in:now,later'],
            'scheduled_at' => ['nullable', 'required_if:when,later', 'date', 'after:now'],
        ]);

        $zoneId = in_array($data['target'], ['zone_customers', 'zone_drivers'], true) ? (int) $data['zone_id'] : null;

        if ($data['when'] === 'later') {
            ScheduledNotification::create([
                'type' => 'broadcast',
                'target_type' => $data['target'],
                'target_id' => $zoneId ?? 0,
                'title' => $data['title'],
                'body' => $data['body'],
                'scheduled_at' => $data['scheduled_at'],
                'status' => 'pending',
            ]);

            return back()->with('success', 'Broadcast scheduled.');
        }

        // Record history then dispatch immediately.
        ScheduledNotification::create([
            'type' => 'broadcast',
            'target_type' => $data['target'],
            'target_id' => $zoneId ?? 0,
            'title' => $data['title'],
            'body' => $data['body'],
            'scheduled_at' => now(),
            'sent_at' => now(),
            'status' => 'sent',
        ]);

        dispatch(new BroadcastNotification($data['target'], $data['title'], $data['body'], $zoneId));

        return back()->with('success', 'Broadcast is being sent.');
    }

    public function history()
    {
        $broadcasts = ScheduledNotification::where('type', 'broadcast')
            ->latest('scheduled_at')
            ->paginate(20);

        return view('admin.notifications.history', compact('broadcasts'));
    }

    // JSON feed for the admin notification bell.
    public function adminNotifications()
    {
        $adminId = auth('admin')->id();

        $query = Notification::where('notifiable_type', 'admin')
            ->where(fn ($q) => $q->whereNull('notifiable_id')->orWhere('notifiable_id', $adminId));

        return response()->json([
            'unread' => (clone $query)->whereNull('read_at')->count(),
            'items' => (clone $query)->latest()->limit(5)->get(['id', 'title', 'body', 'type', 'read_at', 'created_at']),
        ]);
    }

    public function markRead(Request $request)
    {
        $adminId = auth('admin')->id();

        Notification::where('notifiable_type', 'admin')
            ->where(fn ($q) => $q->whereNull('notifiable_id')->orWhere('notifiable_id', $adminId))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
