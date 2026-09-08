<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Services\SystemSettingService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class NotificationSettingsController extends Controller implements HasMiddleware
{
    public function __construct(
        private SystemSettingService $settings,
        private NotificationService $notifications,
        private WhatsAppService $whatsapp,
        private SmsService $sms,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index']),
            new Middleware('permission:settings,write', only: ['update', 'testFcm', 'testPusher', 'testWhatsapp', 'testSms']),
        ];
    }

    // Per-event push/SMS toggle keys.
    private const RULE_EVENTS = [
        'order_accepted', 'driver_arrived', 'delivery_complete',
        'doc_expiry_30', 'doc_expiry_7', 'due_limit', 'withdrawal_approved', 'driver_approval',
    ];

    public function index()
    {
        $s = fn ($key, $default = null) => $this->settings->get($key, $default);
        $bool = fn ($key, $default = true) => $this->settings->getBool($key, $default);

        $rules = [];
        foreach (self::RULE_EVENTS as $event) {
            $rules[$event] = [
                'push' => $bool("notify_{$event}_push", true),
                'sms' => $bool("notify_{$event}_sms", false),
            ];
        }

        return view('admin.settings.notifications', [
            'settings' => [
                'firebase_credentials_set' => is_file(storage_path('app/firebase/firebase.json')),
                'pusher_app_id' => $s('pusher_app_id', ''),
                'pusher_key' => $s('pusher_key', ''),
                'pusher_secret_set' => (bool) $s('pusher_secret'),
                'pusher_cluster' => $s('pusher_cluster', 'ap2'),
                'sms_provider' => $s('sms_provider', 'none'),
                'sms_api_key' => $s('sms_api_key', ''),
                'sms_api_secret_set' => (bool) $s('sms_api_secret'),
                'sms_sender_id' => $s('sms_sender_id', ''),
                'sms_api_url' => $s('sms_api_url', ''),
                'whatsapp_enabled' => $bool('whatsapp_enabled', false),
                'whatsapp_api_url' => $s('whatsapp_api_url', ''),
                'whatsapp_api_key_set' => (bool) $s('whatsapp_api_key'),
                'whatsapp_instance_id' => $s('whatsapp_instance_id', ''),
                'whatsapp_otp_template' => $s('whatsapp_otp_template', ''),
                'whatsapp_test_mode' => $this->whatsapp->isTestMode(),
            ],
            'rules' => $rules,
            'ruleLabels' => [
                'order_accepted' => 'Order Accepted',
                'driver_arrived' => 'Driver Arrived',
                'delivery_complete' => 'Delivery Complete',
                'doc_expiry_30' => 'Document Expiry (30d)',
                'doc_expiry_7' => 'Document Expiry (7d)',
                'due_limit' => 'Due Limit Reached',
                'withdrawal_approved' => 'Withdrawal Approved',
                'driver_approval' => 'Driver Approval',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $g = 'notification';

        $request->validate([
            'whatsapp_api_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_instance_id' => ['nullable', 'string', 'max:100'],
            'whatsapp_api_key' => ['nullable', 'string', 'max:255'],
            'whatsapp_otp_template' => ['nullable', 'string', 'max:1000'],
            'sms_provider' => ['nullable', 'in:none,twilio,custom'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_api_secret' => ['nullable', 'string', 'max:255'],
            'sms_sender_id' => ['nullable', 'string', 'max:50'],
            'sms_api_url' => ['nullable', 'url', 'max:255'],
        ]);

        // FCM service-account JSON upload → storage/app/firebase/firebase.json
        // (the path config/firebase.php reads). Replaces the need for the .env file.
        if ($request->hasFile('firebase_credentials')) {
            $request->validate([
                'firebase_credentials' => ['file', 'mimetypes:application/json,text/plain', 'max:64'],
            ]);
            $json = json_decode(file_get_contents($request->file('firebase_credentials')->getRealPath()), true);
            if (! is_array($json) || ($json['type'] ?? null) !== 'service_account' || empty($json['private_key'])) {
                return back()->with('error', 'Invalid Firebase service-account JSON.');
            }
            $request->file('firebase_credentials')->move(storage_path('app/firebase'), 'firebase.json');
        }

        // Pusher
        foreach (['pusher_app_id', 'pusher_key', 'pusher_cluster'] as $key) {
            if ($request->has($key)) {
                $this->settings->set($key, (string) $request->input($key), $g);
            }
        }
        // Secret encrypted at rest (rule #3). Empty input = keep existing.
        if ($request->filled('pusher_secret')) {
            $this->settings->set('pusher_secret', \Illuminate\Support\Facades\Crypt::encryptString($request->input('pusher_secret')), $g);
        }

        // SMS
        foreach (['sms_provider', 'sms_api_key', 'sms_sender_id', 'sms_api_url'] as $key) {
            if ($request->has($key)) {
                $this->settings->set($key, trim((string) $request->input($key)), $g);
            }
        }
        // Auth token / secret encrypted at rest (rule #3). Empty input = keep existing.
        if ($request->filled('sms_api_secret')) {
            $this->settings->set('sms_api_secret', \Illuminate\Support\Facades\Crypt::encryptString(trim($request->input('sms_api_secret'))), $g);
        }

        // WhatsApp — OTP delivery.
        $this->settings->set('whatsapp_enabled', $request->boolean('whatsapp_enabled') ? 'true' : 'false', $g);
        foreach (['whatsapp_api_url', 'whatsapp_instance_id', 'whatsapp_otp_template'] as $key) {
            if ($request->has($key)) {
                $this->settings->set($key, trim((string) $request->input($key)), $g);
            }
        }
        // API key encrypted at rest (rule #3). Empty input = keep existing.
        if ($request->filled('whatsapp_api_key')) {
            $this->settings->set('whatsapp_api_key', \Illuminate\Support\Facades\Crypt::encryptString(trim($request->input('whatsapp_api_key'))), $g);
        }

        // Notification rules (push/sms per event).
        foreach (self::RULE_EVENTS as $event) {
            $this->settings->set("notify_{$event}_push", $request->boolean("rules.{$event}.push") ? 'true' : 'false', $g);
            $this->settings->set("notify_{$event}_sms", $request->boolean("rules.{$event}.sms") ? 'true' : 'false', $g);
        }

        // Apps read pusher_key/cluster from /config — refresh that cache.
        \Illuminate\Support\Facades\Cache::forget('api_config');

        return back()->with('success', 'Notification settings saved.');
    }

    public function testFcm()
    {
        $configured = is_file(storage_path('app/firebase/firebase.json'));

        return response()->json([
            'ok' => $configured,
            'message' => $configured
                ? 'Firebase service-account JSON is uploaded. Push will be attempted on real device tokens.'
                : 'No Firebase service-account JSON uploaded yet.',
        ], $configured ? 200 : 422);
    }

    // Sends a real WhatsApp message to the given number to verify the credentials.
    public function testWhatsapp(Request $request)
    {
        $request->validate(['phone' => ['required', 'string', 'max:20']]);

        [$ok, $message] = $this->whatsapp->sendTest($request->input('phone'));

        return response()->json(['ok' => $ok, 'message' => $message], $ok ? 200 : 422);
    }

    // Sends a real SMS to the given number to verify the provider credentials.
    public function testSms(Request $request)
    {
        $request->validate(['phone' => ['required', 'string', 'max:20']]);

        $result = $this->sms->sendTest($request->input('phone'));

        return response()->json(['ok' => $result['ok'], 'message' => $result['message']], $result['ok'] ? 200 : 422);
    }

    public function testPusher()
    {
        $ok = $this->notifications->sendPusher('private-admin', 'TestEvent', ['message' => 'Pusher test from admin']);

        return response()->json([
            'ok' => $ok,
            'message' => $ok ? 'Test event sent successfully.' : 'Pusher is not configured or the request failed.',
        ], $ok ? 200 : 422);
    }
}
