<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Outbound SMS via the admin-configured provider (Settings → Notifications → SMS).
 *
 * Providers:
 *  - twilio : Account SID (sms_api_key) + Auth Token (sms_api_secret, encrypted) + From number (sms_sender_id).
 *  - custom : generic JSON POST to sms_api_url with {api_key, sender, to, message}.
 *  - none   : disabled (no-op).
 *
 * Per project rule: never throw out of a send — log and return a result array.
 */
class SmsService
{
    public function __construct(private SystemSettingService $settings)
    {
    }

    public function provider(): string
    {
        return (string) $this->settings->get('sms_provider', 'none');
    }

    // True when the selected provider has all the credentials it needs.
    public function isConfigured(): bool
    {
        return match ($this->provider()) {
            'twilio' => $this->apiKey() && $this->apiSecret() && $this->senderId(),
            'custom' => (bool) $this->apiUrl(),
            default => false,
        };
    }

    /**
     * Send an SMS. Returns ['ok' => bool, 'message' => string].
     * Silently a no-op (ok=false) when no provider is configured.
     */
    public function send(string $phone, string $message): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'SMS provider is not configured.'];
        }

        $to = $this->normalizePhone($phone);
        if ($to === '') {
            return ['ok' => false, 'message' => 'Recipient phone number is empty.'];
        }

        try {
            return match ($this->provider()) {
                'twilio' => $this->sendViaTwilio($to, $message),
                'custom' => $this->sendViaCustom($to, $message),
                default => ['ok' => false, 'message' => 'Unknown SMS provider.'],
            };
        } catch (\Throwable $e) {
            Log::error('SMS send error: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'SMS provider error: ' . $e->getMessage()];
        }
    }

    // Send a verification message to confirm the credentials from the admin panel.
    public function sendTest(string $phone): array
    {
        if ($this->provider() === 'none') {
            return ['ok' => false, 'message' => 'Select an SMS provider first.'];
        }
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Fill in the provider credentials and save before testing.'];
        }

        return $this->send($phone, 'Test SMS from ' . $this->appName() . '. Your SMS settings are working.');
    }

    // ---------------------------------------------------------------------

    private function sendViaTwilio(string $to, string $message): array
    {
        $sid = $this->apiKey();
        $response = Http::asForm()
            ->withBasicAuth($sid, $this->apiSecret())
            ->timeout(20)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $this->senderId(),
                'To' => '+' . $to,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            return ['ok' => true, 'message' => 'SMS sent (SID ' . $response->json('sid') . ').'];
        }

        $err = $response->json('message') ?? ('HTTP ' . $response->status());
        Log::warning('Twilio SMS failed: ' . $err);

        return ['ok' => false, 'message' => 'Twilio error: ' . $err];
    }

    private function sendViaCustom(string $to, string $message): array
    {
        $response = Http::timeout(20)->post($this->apiUrl(), [
            'api_key' => $this->apiKey(),
            'sender' => $this->senderId(),
            'to' => $to,
            'message' => $message,
        ]);

        if ($response->successful()) {
            return ['ok' => true, 'message' => 'SMS sent.'];
        }

        Log::warning('Custom SMS gateway failed: HTTP ' . $response->status());

        return ['ok' => false, 'message' => 'SMS gateway responded HTTP ' . $response->status() . '.'];
    }

    private function apiKey(): ?string
    {
        $value = trim((string) $this->settings->get('sms_api_key', ''));

        return $value !== '' ? $value : null;
    }

    // Auth token / secret — stored encrypted at rest.
    private function apiSecret(): ?string
    {
        $stored = $this->settings->get('sms_api_secret');
        if (! $stored) {
            return null;
        }

        try {
            $value = Crypt::decryptString($stored);
        } catch (\Throwable) {
            $value = (string) $stored;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function senderId(): ?string
    {
        $value = trim((string) $this->settings->get('sms_sender_id', ''));

        return $value !== '' ? $value : null;
    }

    private function apiUrl(): ?string
    {
        $value = trim((string) $this->settings->get('sms_api_url', ''));

        return $value !== '' ? $value : null;
    }

    private function appName(): string
    {
        return (string) ($this->settings->get('app_name') ?: config('app.name', 'ReadyRide'));
    }

    /**
     * Digits with country code (e.g. 8801XXXXXXXXX). Apps send local numbers
     * (01XXXXXXXXX); the dialing code comes from Settings → General (phone_code),
     * so this works for any country. Twilio adds the leading '+'.
     *
     * A number entered in international form (with a leading '+', e.g. a test to
     * a foreign/Twilio number) is treated as already-complete and left as-is —
     * the local code is NOT prepended.
     */
    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $digits = (string) preg_replace('/\D+/', '', $phone);

        // Already international (entered with '+') → use exactly as given.
        if (str_starts_with($phone, '+')) {
            return $digits;
        }

        $code = (string) preg_replace('/\D+/', '', (string) $this->settings->get('phone_code', '+880'));

        if ($code === '' || $digits === '' || str_starts_with($digits, $code)) {
            return $digits;
        }

        return $code . ltrim($digits, '0');
    }
}
