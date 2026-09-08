<?php

use App\Models\Admin;
use App\Models\Conversation;
use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels (authorized via Sanctum tokens)
|--------------------------------------------------------------------------
| The mobile apps authorise private channels through POST /api/broadcasting/auth
| (auth:sanctum). The resolved $user is the token's owner — a User, Driver,
| or Admin depending on the token.
*/

// Customer order channel — only the order's owner may listen.
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    if (! $user instanceof User) {
        return false;
    }
    $order = Order::find($orderId);

    return $order && (int) $order->user_id === (int) $user->id;
}, ['guards' => ['sanctum']]);

// Driver channel — only the driver themselves.
Broadcast::channel('driver.{driverId}', function ($driver, $driverId) {
    return $driver instanceof Driver && (int) $driver->id === (int) $driverId;
}, ['guards' => ['sanctum']]);

// Admin channel — any authenticated admin.
Broadcast::channel('admin', function ($admin) {
    return $admin instanceof Admin;
}, ['guards' => ['sanctum']]);

// Ride/parcel chat — the ONLY two people allowed are the customer and the
// driver on that order. Unlike the other channels this one is shared, so it
// must accept either identity and check the matching column.
Broadcast::channel('conversation.{conversationId}', function ($account, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (! $conversation) {
        return false;
    }

    if ($account instanceof User) {
        return (int) $conversation->user_id === (int) $account->id;
    }

    if ($account instanceof Driver) {
        return (int) $conversation->driver_id === (int) $account->id;
    }

    return false;
}, ['guards' => ['sanctum']]);
