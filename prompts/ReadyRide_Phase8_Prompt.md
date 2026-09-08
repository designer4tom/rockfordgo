# ReadyRide — Claude Code Master Instruction
# পর্যায় ৮: Notification + Dispute + SOS Management

---

## Context
পর্যায় ১-৭ শেষ। এই পর্যায়ে Notification system, Dispute management এবং SOS handling বানাবো।

---

## এই পর্যায়ে যা করবে

1. NotificationService (FCM + Pusher)
2. Notification Settings Configuration
3. Broadcast Notification (Admin → All)
4. Dispute Management
5. SOS Alert Management
6. In-app Notification History (Admin)

---

## ১. NotificationService

```php
// app/Services/NotificationService.php

class NotificationService
{
    // Single user/driver-কে FCM push পাঠানো
    public function sendPush(
        string $targetType,  // 'user' | 'driver'
        int $targetId,
        string $title,
        string $body,
        string $type,
        array $data = []
    ): bool

    // Multiple users/drivers-কে FCM push (bulk)
    public function sendPushToMany(
        string $targetType,
        array $targetIds,
        string $title,
        string $body,
        string $type,
        array $data = []
    ): array  // returns [success_count, fail_count]

    // Pusher দিয়ে real-time event
    public function sendPusher(
        string $channel,
        string $event,
        array $data
    ): bool

    // Order request driver-কে পাঠানো (FCM + Pusher একসাথে)
    public function sendOrderRequest(
        Driver $driver,
        Order $order
    ): bool

    // Driver-দের order request পাঠানোর loop
    // ৩০ সেকেন্ড wait, না নিলে next driver
    public function dispatchOrderToDrivers(Order $order): bool

    // notifications table-এ save করা
    private function saveNotification(
        string $type,
        int $id,
        string $notifiableType,
        string $title,
        string $body,
        string $notifType,
        array $data = []
    ): void
}
```

### FCM Implementation
```php
// FCM HTTP v1 API ব্যবহার করবে
// Package: kreait/laravel-firebase

// config/firebase.php configure করবে
// FIREBASE_CREDENTIALS=path/to/service-account.json

private function sendFcmNotification(string $fcmToken, array $payload): bool
{
    $messaging = app('firebase.messaging');

    $message = CloudMessage::withTarget('token', $fcmToken)
        ->withNotification(Notification::create($payload['title'], $payload['body']))
        ->withData($payload['data'] ?? []);

    try {
        $messaging->send($message);
        return true;
    } catch (\Exception $e) {
        Log::error('FCM Error: ' . $e->getMessage());
        return false;
    }
}
```

### Pusher Implementation
```php
// config/broadcasting.php — pusher driver
// .env: BROADCAST_DRIVER=pusher

// Order request channel:
// Channel: private-driver.{driver_id}
// Event: OrderRequest
// Data: order details, 30s timeout

private function sendPusherEvent(string $channel, string $event, array $data): bool
{
    try {
        Broadcast::channel($channel, $event, $data);
        // অথবা: event(new OrderRequestEvent($driver, $order));
        return true;
    } catch (\Exception $e) {
        Log::error('Pusher Error: ' . $e->getMessage());
        return false;
    }
}
```

### Order Dispatch Logic
```php
// app/Jobs/DispatchOrderToDrivers.php (Queue Job)

public function handle(): void
{
    $order = $this->order;
    $radius = SystemSetting::get('search_radius_km', 5);
    $timeout = SystemSetting::get('request_timeout_seconds', 30);
    $triedDrivers = [];

    while (true) {
        // Nearby online approved drivers খোঁজো
        // যারা already try করা হয়েছে তাদের বাদ দাও
        // Due limit exceed করেনি এমন
        $driver = Driver::query()
            ->where('is_online', true)
            ->where('status', 'approved')
            ->whereNotIn('id', $triedDrivers)
            ->withinRadius($order->pickup_lat, $order->pickup_lng, $radius)
            ->orderByDistance($order->pickup_lat, $order->pickup_lng)
            ->first();

        if (!$driver) {
            // কোনো driver নেই
            $order->update(['status' => 'no_driver_found']);
            // Customer-কে notify
            break;
        }

        $triedDrivers[] = $driver->id;

        // FCM + Pusher দিয়ে request পাঠাও
        $this->notificationService->sendOrderRequest($driver, $order);

        // ৩০ সেকেন্ড wait (cache দিয়ে response check)
        $accepted = $this->waitForResponse($driver->id, $order->id, $timeout);

        if ($accepted) {
            break;  // Driver accept করেছে
        }
        // Next driver try করো
    }
}

private function waitForResponse(int $driverId, int $orderId, int $seconds): bool
{
    $key = "order_response_{$orderId}_{$driverId}";
    $start = now();

    while (now()->diffInSeconds($start) < $seconds) {
        if (Cache::has($key)) {
            return Cache::get($key) === 'accepted';
        }
        sleep(1);
    }
    return false;
}
```

---

## ২. Notification Settings Page

```
Route: GET  /admin/settings/notifications
Route: POST /admin/settings/notifications
Permission: settings, write

Section: FCM (Firebase)
- Firebase Project ID
- Firebase Service Account JSON (file upload বা textarea paste)
- "Test FCM" button → test notification পাঠাবে admin-কে

Section: Pusher
- App ID
- Key
- Secret
- Cluster
- "Test Pusher" button → test event fire করবে

Section: SMS (optional)
- SMS Provider: None / Twilio / Custom
- API Key, Sender ID

Section: Notification Rules
Toggle on/off per event:
Event                    | Push | SMS
-------------------------|------|----
Order Accepted           |  ✓   |  ✓
Driver Arrived           |  ✓   |  ✓
Delivery Complete        |  ✓   |
Document Expiry (30d)    |  ✓   |  ✓
Document Expiry (7d)     |  ✓   |  ✓
Due Limit Reached        |  ✓   |
Withdrawal Approved      |  ✓   |
Driver Approval          |  ✓   |  ✓

Save → system_settings update
```

---

## ৩. Broadcast Notification

```
Route: GET  /admin/notifications/broadcast
Route: POST /admin/notifications/broadcast
Permission: settings, write

Form:
- Target: All Customers / All Drivers / Specific Zone (Customers) / Specific Zone (Drivers)
- Title (required, max 100)
- Message (required, max 500)
- Schedule: Now / Later (datetime picker)
- Preview panel (right side — shows how notification will look)

"Send" বা "Schedule" button

Validation:
- title, body required
- scheduled_at: future datetime if scheduled

Logic:
- "Now" → Queue job: BroadcastNotification
- "Later" → scheduled_notifications table-এ save, Scheduler চালাবে

BroadcastNotification Job:
- Target অনুযায়ী FCM tokens collect করো
- Batch-এ পাঠাও (FCM supports 500/batch)
- Progress track করো
- notifications table-এ save

Broadcast History:
Route: GET /admin/notifications/history

Table:
- Title
- Target (All Customers/etc.)
- Sent To (count)
- Status (sent/scheduled/failed)
- Scheduled At
- Sent At
```

---

## ৪. Dispute Management

```
Route: GET /admin/disputes
Permission: disputes, read

Tabs:
- Open (count badge — red)
- Under Review (count badge — yellow)
- Resolved
- All

Filters:
- Date Range
- Category
- Raised By: Customer / Driver
- Has Refund: Yes / No

Table:
- ID
- Order # (link)
- Raised By (Customer/Driver name + badge)
- Category (badge)
- Description (truncated)
- Order Stage at time of dispute
- Status badge
- Refund: ৳X / None
- Submitted At
- Actions: View

Dispute Detail Page:
Route: GET /admin/disputes/{id}
Permission: disputes, read

Layout:

Section 1 — Dispute Info:
- Dispute ID, Category, Status
- Raised by: [Customer/Driver name + contact]
- Description (full)
- Submitted: [datetime]

Section 2 — Related Order:
- Order # link
- Order Status at dispute time
- Order Timeline (mini version)
- Pickup → Drop
- Driver ও Customer info
- Total Amount, Payment Status

Section 3 — Order Stage Analysis:
Auto-analysis helper:
"Order-টি [status]-এ আছে।
 Last activity: [datetime]
 [Driver নাম] সর্বশেষ location: [lat, lng] at [time]"

Visual timeline showing where order got stuck

Section 4 — Admin Action:
- Internal Note (textarea — admin only দেখবে)
- Status Change: Open → Under Review → Resolved
- Refund Decision:
  toggle: Issue Refund? Yes/No
  Amount (auto-fill from order total, editable)
  Refund to: Customer Wallet (only option for now)

- "Update" button

Section 5 — Action History:
Who changed what and when (status changes + notes)

After Resolve:
- dispute.status = resolved
- dispute.resolved_by = admin
- dispute.resolved_at = now()
- If refund: WalletService.refundToCustomer()
- Customer notification: "আপনার dispute resolved হয়েছে"
```

---

## ৫. SOS Alert Management

```
Route: GET /admin/sos
Permission: sos, read

Page Header: Real-time — Active SOS alerts top-এ highlighted থাকবে

Active SOS Alerts (Pusher real-time update):
- Red banner: "X টি Active SOS Alert"
- প্রতিটি card:
  Driver/Customer নাম + ফোন
  Location (Map thumbnail)
  Trip info
  Triggered: X minutes ago
  [Acknowledge] button

Table (All SOS):
- ID
- Triggered By (Driver/Customer name)
- Order # (link, if any)
- Location (lat, lng — copy button)
- Status: Active (red) / Acknowledged (yellow) / Resolved (green)
- Triggered At
- Acknowledged By (admin name)
- Actions: View, Acknowledge, Resolve

SOS Detail:
Route: GET /admin/sos/{id}

- Full map with location pin
- Triggered by info
- Related order info
- Status timeline
- Admin note
- Action buttons

Real-time SOS Notification to Admin:
- Pusher channel: private-admin
- Event: SosAlert
- Admin browser tab-এ alert sound + badge update
- Dashboard-এ SOS count update

Acknowledge:
Route: POST /admin/sos/{id}/acknowledge
- sos.status = acknowledged
- sos.acknowledged_by, acknowledged_at
- Admin note optional

Resolve:
Route: POST /admin/sos/{id}/resolve
- sos.status = resolved
- Note required
```

---

## ৬. Admin Notification Bell

```
Admin layout navbar-এ notification bell:
- Unread count badge
- Dropdown: last 5 notifications
- "সব দেখুন" link

Route: GET /admin/notifications (JSON — AJAX)
Route: POST /admin/notifications/mark-read

Types admin দেখবে:
- New SOS Alert
- New Dispute
- New Driver Registration
- Document Expiry
- New Withdrawal Request

Real-time update:
Pusher channel: private-admin.{admin_id}
Events: NewSos, NewDispute, NewDriverRegistration
```

---

## File Structure

```
app/
  Services/
    NotificationService.php
  Jobs/
    DispatchOrderToDrivers.php
    BroadcastNotification.php
    AssignScheduledOrders.php (Phase 6 থেকে)
    SendDocumentExpiryAlerts.php
  Events/
    OrderRequestEvent.php
    SosAlertEvent.php
    NewDisputeEvent.php
  Listeners/
    (auto-generated)
  Http/Controllers/Admin/
    DisputeController.php
    SosController.php
    NotificationController.php
    Settings/
      NotificationSettingsController.php

resources/views/admin/
  disputes/
    index.blade.php
    show.blade.php
  sos/
    index.blade.php
    show.blade.php
  notifications/
    broadcast.blade.php
    history.blade.php
  settings/
    notifications.blade.php
  components/
    sos-alert-banner.blade.php
    notification-bell.blade.php
```

---

## Routes

```php
// Disputes
Route::prefix('disputes')->name('disputes.')->group(function () {
    Route::get('/', [DisputeController::class, 'index'])->name('index');
    Route::get('/{id}', [DisputeController::class, 'show'])->name('show');
    Route::post('/{id}/update', [DisputeController::class, 'update'])->name('update');
});

// SOS
Route::prefix('sos')->name('sos.')->group(function () {
    Route::get('/', [SosController::class, 'index'])->name('index');
    Route::get('/{id}', [SosController::class, 'show'])->name('show');
    Route::post('/{id}/acknowledge', [SosController::class, 'acknowledge'])
         ->name('acknowledge');
    Route::post('/{id}/resolve', [SosController::class, 'resolve'])->name('resolve');
});

// Notifications
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('broadcast', [NotificationController::class, 'broadcastForm'])
         ->name('broadcast');
    Route::post('broadcast', [NotificationController::class, 'sendBroadcast'])
         ->name('broadcast.send');
    Route::get('history', [NotificationController::class, 'history'])
         ->name('history');
    Route::get('/', [NotificationController::class, 'adminNotifications'])
         ->name('list');
    Route::post('mark-read', [NotificationController::class, 'markRead'])
         ->name('mark-read');
});

// Notification Settings
Route::get('settings/notifications', [NotificationSettingsController::class, 'index'])
     ->name('settings.notifications');
Route::post('settings/notifications', [NotificationSettingsController::class, 'update'])
     ->name('settings.notifications.update');
Route::post('settings/notifications/test-fcm', [NotificationSettingsController::class, 'testFcm'])
     ->name('settings.notifications.test-fcm');
Route::post('settings/notifications/test-pusher', [NotificationSettingsController::class, 'testPusher'])
     ->name('settings.notifications.test-pusher');
```

---

## Laravel Scheduler Update

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // প্রতি মিনিটে scheduled orders check
    $schedule->job(new AssignScheduledOrders)->everyMinute();

    // প্রতিদিন সকাল ৯টায় expiry alerts
    $schedule->job(new SendDocumentExpiryAlerts)->dailyAt('09:00');

    // Scheduled broadcast notifications
    $schedule->call(function () {
        ScheduledNotification::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->each(fn($n) => dispatch(new SendScheduledNotification($n)));
    })->everyMinute();
}
```

---

## গুরুত্বপূর্ণ নিয়ম

1. FCM/Pusher credentials না থাকলে graceful fail — error log করো, exception throw না
2. Broadcast large audience → Queue + Batch (500/chunk)
3. SOS real-time update Pusher দিয়ে — admin page refresh লাগবে না
4. Dispute-এ refund issue করার আগে double-check (already refunded কিনা)
5. DispatchOrderToDrivers Job → Queue:default, timeout: 300s

---

## শুরু করো এই order-এ

1. NotificationService বানাও
2. Events ও Jobs তৈরি করো
3. DisputeController + views
4. SosController + views
5. NotificationController + views
6. Settings controllers
7. Scheduler update
8. Pusher real-time SOS + Admin bell
9. Routes + Sidebar update
