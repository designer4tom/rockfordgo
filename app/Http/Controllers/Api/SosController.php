<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SosAlert;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SosController extends Controller
{
    use ApiResponse;

    public function __construct(private NotificationService $notifications)
    {
    }

    public function userSos(Request $request)
    {
        return $this->trigger($request, 'user');
    }

    public function driverSos(Request $request)
    {
        return $this->trigger($request, 'driver');
    }

    private function trigger(Request $request, string $type)
    {
        $data = $request->validate([
            'order_id' => ['nullable', 'exists:orders,id'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);

        $alert = SosAlert::create([
            'order_id' => $data['order_id'] ?? null,
            'triggered_by' => $type,
            'triggered_by_id' => $request->user()->id,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'status' => 'active',
        ]);

        // Real-time alert to the admin dashboard (graceful without Pusher).
        $this->notifications->notifyAdmins('SosAlert', [
            'id' => $alert->id,
            'triggered_by' => $type,
            'lat' => (float) $data['lat'],
            'lng' => (float) $data['lng'],
        ]);

        return $this->success(null, 'SOS alert sent. Help is on the way.');
    }
}
