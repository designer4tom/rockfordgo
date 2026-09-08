<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Central notification dispatcher. Always records an in-app notification row,
 * and additionally attempts an FCM push and/or a Pusher real-time event when
 * the corresponding credentials are configured.
 *
 * Per project rule: if credentials are missing or a provider errors, we log
 * and continue — we never throw out of a notification call.
 */
class NotificationService
{
    public function __construct(
        private SystemSettingService $settings,
        private SmsService $sms,
    ) {
    }

    // Whether a per-event rule (Settings → Notifications → Notification Rules)
    // allows a channel. Push defaults ON, SMS defaults OFF — matching the UI.
    public function ruleAllows(string $event, string $channel): bool
    {
        return $this->settings->getBool("notify_{$event}_{$channel}", $channel === 'push');
    }

    /**
     * Rule-aware notification for the events listed in the Notification Rules
     * table. Always records the in-app row; sends an FCM push only when the
     * event's Push rule is on, and an SMS only when its SMS rule is on, the SMS
     * provider is configured, and a phone number is available.
     */
    public function notifyEvent(
        string $event,
        string $targetType,
        int $targetId,
        string $title,
        string $body,
        string $type,
        array $data = [],
        ?string $phone = null,
        ?string $smsMessage = null,
    ): void {
        if ($this->ruleAllows($event, 'push')) {
            $this->sendPush($targetType, $targetId, $title, $body, $type, $data);
        } else {
            // Push suppressed by the rule — still keep the in-app record.
            $this->saveNotification($targetType, $targetId, $title, $body, $type, $data);
        }

        if ($phone && $this->ruleAllows($event, 'sms') && $this->sms->isConfigured()) {
            $this->sms->send($phone, $smsMessage ?: trim($title . ': ' . $body));
        }
    }

    // Send a push to a user/driver across ALL their devices (and persist it in-app).
    public function sendPush(string $targetType, int $targetId, string $title, string $body, string $type, array $data = []): bool
    {
        $this->saveNotification($targetType, $targetId, $title, $body, $type, $data);

        $tokens = $this->tokensFor($targetType, $targetId);
        if (empty($tokens)) {
            // Silent until now, which made "row saved but no push" impossible to
            // diagnose from the outside. The in-app row above still exists, so the
            // notification list looks healthy while the device gets nothing.
            Log::warning(sprintf(
                'Push skipped: no FCM token for %s #%d (type: %s). The in-app row was still saved.',
                $targetType,
                $targetId,
                $type,
            ));

            return false;
        }

        $payload = ['title' => $title, 'body' => $body, 'data' => array_merge($data, ['type' => $type])];

        // Preferred path: Firebase Admin SDK (kreait) multicast.
        if ($this->firebaseMessaging()) {
            return $this->sendViaFirebase($tokens, $payload);
        }

        // Fallback: legacy FCM HTTP (one request per token).
        $ok = false;
        foreach ($tokens as $token) {
            $ok = $this->sendFcmNotification($token, $payload) || $ok;
        }

        return $ok;
    }

    // Bulk push. Returns ['success' => x, 'fail' => y]. In-app rows are always written.
    public function sendPushToMany(string $targetType, array $targetIds, string $title, string $body, string $type, array $data = []): array
    {
        $success = 0;
        $fail = 0;

        foreach (array_chunk($targetIds, 500) as $chunk) {
            foreach ($chunk as $id) {
                $this->sendPush($targetType, (int) $id, $title, $body, $type, $data) ? $success++ : $fail++;
            }
        }

        return ['success' => $success, 'fail' => $fail];
    }

    // Fire a Pusher event on a channel (graceful no-op without credentials).
    public function sendPusher(string $channel, string $event, array $data): bool
    {
        $cfg = $this->pusherConfig();
        if (! $cfg) {
            Log::info('Pusher not configured; skipping event ' . $event . ' on ' . $channel);
            return false;
        }

        try {
            $body = json_encode([
                'name' => $event,
                'channel' => $channel,
                'data' => json_encode($data),
            ]);

            $path = "/apps/{$cfg['app_id']}/events";
            $params = [
                'auth_key' => $cfg['key'],
                'auth_timestamp' => now()->timestamp,
                'auth_version' => '1.0',
                'body_md5' => md5($body),
            ];
            ksort($params);
            $queryString = urldecode(http_build_query($params));
            $signature = hash_hmac('sha256', "POST\n{$path}\n{$queryString}", $cfg['secret']);

            $response = Http::withBody($body, 'application/json')
                ->timeout(10)
                ->post("https://api-{$cfg['cluster']}.pusher.com{$path}?{$queryString}&auth_signature={$signature}");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Pusher error: ' . $e->getMessage());
            return false;
        }
    }

    // Send an order request to one driver via FCM + Pusher.
    public function sendOrderRequest(Driver $driver, Order $order): bool
    {
        $order->loadMissing('user:id,name,phone');

        // Everything an "incoming order" screen needs, so the app can render it
        // straight from the FCM data payload (all values sent as strings).
        $data = [
            'order_id' => (string) $order->id,
            'order_number' => (string) $order->order_number,
            'order_type' => (string) $order->type,
            'pickup' => (string) $order->pickup_address,
            'pickup_lat' => (string) $order->pickup_lat,
            'pickup_lng' => (string) $order->pickup_lng,
            'drop' => (string) $order->drop_address,
            'drop_lat' => (string) $order->drop_lat,
            'drop_lng' => (string) $order->drop_lng,
            'distance_km' => (string) ($order->distance_km ?? ''),
            'total_amount' => number_format((float) $order->total_amount, 2, '.', ''),
            // Driver's take-home — what the popup should actually show (matches
            // the Pusher NewOrderRequestEvent payload).
            'estimated_earning' => number_format((float) $order->driver_earning, 2, '.', ''),
            'payment_method' => (string) $order->payment_method,
            'is_cod' => $order->is_cod ? '1' : '0',
            'cod_amount' => number_format((float) ($order->cod_amount ?? 0), 2, '.', ''),
            'customer_name' => (string) ($order->user->name ?? ''),
            'customer_phone' => (string) ($order->user->phone ?? ''),
            'timeout_seconds' => (string) \App\Models\SystemSetting::get('request_timeout_seconds', 30),
            // Epoch seconds the offer was sent — lets a terminated→tap launch
            // compute the real remaining time instead of restarting the countdown.
            'sent_at' => (string) now()->timestamp,
        ];

        $push = $this->sendPush('driver', $driver->id, 'New ride request',
            'A new order ' . $order->order_number . ' has arrived.', 'order_request', $data);

        // Real-time push to the driver's private channel.
        broadcast(new \App\Events\NewOrderRequestEvent($driver, $order));

        return $push;
    }

    // Kick off the queued driver-dispatch loop for an order.
    public function dispatchOrderToDrivers(Order $order): bool
    {
        \App\Jobs\DispatchOrderToDrivers::dispatch($order->id);

        return true;
    }

    // Notify the admin channel of an event (SOS, dispute, etc.).
    public function notifyAdmins(string $event, array $data): bool
    {
        return $this->sendPusher('admin-sos', $event, $data);
    }

    // Persist an in-app notification row.
    public function saveNotification(string $notifiableType, int $id, string $title, string $body, string $notifType, array $data = []): void
    {
        Notification::create([
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $id,
            'title' => $title,
            'body' => $body,
            'type' => $notifType,
            'data' => $data ?: null,
        ]);
    }

    // ---------------------------------------------------------------------

    // Legacy FCM send via HTTP. Graceful when no server key / token present.
    private function sendFcmNotification(string $fcmToken, array $payload): bool
    {
        $serverKey = $this->settings->get('fcm_server_key');
        if (! $serverKey) {
            Log::info('FCM not configured; in-app notification saved only.');
            return false;
        }

        try {
            $response = Http::withToken($serverKey, 'key=')
                ->timeout(15)
                ->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $fcmToken,
                    'notification' => [
                        'title' => $payload['title'],
                        'body' => $payload['body'],
                    ],
                    'data' => (object) ($payload['data'] ?? []),
                ]);

            if (! $response->successful()) {
                Log::warning('FCM responded HTTP ' . $response->status());
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('FCM error: ' . $e->getMessage());
            return false;
        }
    }

    // All FCM tokens for an owner (multiple devices). Falls back to the legacy
    // single fcm_token column when no device_tokens rows exist yet.
    private function tokensFor(string $targetType, int $targetId): array
    {
        $tokens = \App\Models\DeviceToken::where('owner_type', $targetType)
            ->where('owner_id', $targetId)
            ->pluck('token')
            ->all();

        if (! empty($tokens)) {
            return array_values(array_unique($tokens));
        }

        $legacy = $targetType === 'driver'
            ? Driver::whereKey($targetId)->value('fcm_token')
            : User::whereKey($targetId)->value('fcm_token');

        return $legacy ? [$legacy] : [];
    }

    // Resolve the kreait Messaging instance, or null if Firebase isn't configured.
    private function firebaseMessaging(): ?\Kreait\Firebase\Contract\Messaging
    {
        if (! class_exists(\Kreait\Laravel\Firebase\Facades\Firebase::class)) {
            return null;
        }

        $credentials = config('firebase.projects.app.credentials');
        if (! $credentials || (is_string($credentials) && ! is_file($credentials))) {
            return null; // no service-account JSON uploaded yet
        }

        try {
            return \Kreait\Laravel\Firebase\Facades\Firebase::messaging();
        } catch (\Throwable $e) {
            Log::error('Firebase init error: ' . $e->getMessage());
            return null;
        }
    }

    // Send one multicast message to many tokens; prune tokens FCM reports invalid.
    private function sendViaFirebase(array $tokens, array $payload): bool
    {
        try {
            $messaging = $this->firebaseMessaging();
            $data = [];
            foreach (($payload['data'] ?? []) as $k => $v) {
                $data[$k] = is_scalar($v) ? (string) $v : json_encode($v);
            }

            $message = \Kreait\Firebase\Messaging\CloudMessage::new()
                ->withNotification(\Kreait\Firebase\Messaging\Notification::create($payload['title'], $payload['body']))
                ->withData($data);

            $report = $messaging->sendMulticast($message, $tokens);

            // A rejected token throws nothing — it just comes back as a failure in
            // the report. Without this the send looks fine while the device gets
            // nothing, which is indistinguishable from "no token". The reason here
            // is what tells you which it is: SenderIdMismatch means the app was
            // built against a different Firebase project than this server's
            // service account; Unregistered means the token is simply dead.
            if ($report->failures()->count() > 0) {
                foreach ($report->failures()->getItems() as $failure) {
                    $target = method_exists($failure, 'target') ? (string) $failure->target() : 'unknown';
                    Log::warning(sprintf(
                        'FCM rejected a token (%s): %s',
                        $target,
                        $failure->error()?->getMessage() ?? 'unknown reason',
                    ));
                }
            }

            // Remove tokens that are unknown/unregistered so we stop targeting dead devices.
            $invalid = array_merge($report->invalidTokens(), $report->unknownTokens());
            if (! empty($invalid)) {
                // Only device_tokens rows are pruned here; the legacy column would
                // otherwise keep feeding the same dead token back in via tokensFor().
                \App\Models\DeviceToken::whereIn('token', $invalid)->delete();
                Driver::whereIn('fcm_token', $invalid)->update(['fcm_token' => null]);
                User::whereIn('fcm_token', $invalid)->update(['fcm_token' => null]);
                Log::warning('Pruned ' . count($invalid) . ' dead FCM token(s).');
            }

            return $report->successes()->count() > 0;
        } catch (\Throwable $e) {
            Log::error('Firebase push error: ' . $e->getMessage());
            return false;
        }
    }

    // Single source of truth: the broadcasting config, which AppServiceProvider
    // overrides from the admin Notification Settings (secret already decrypted).
    // This keeps every Pusher usage — events, /config, SOS, test — in sync.
    private function pusherConfig(): ?array
    {
        $p = config('broadcasting.connections.pusher');

        $appId = $p['app_id'] ?? null;
        $key = $p['key'] ?? null;
        $secret = $p['secret'] ?? null;
        $cluster = $p['options']['cluster'] ?? 'mt1';

        if (! $appId || ! $key || ! $secret) {
            return null;
        }

        return ['app_id' => $appId, 'key' => $key, 'secret' => $secret, 'cluster' => $cluster];
    }
}
