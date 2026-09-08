<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends WhatsApp messages through the configured WhatsApp API provider
 * (OTP today, any text later).
 *
 * Credentials and the API base URL are admin-managed (Settings → Notifications),
 * never hardcoded — no provider is assumed. The provider must expose:
 *   POST {base_url}/messages/text
 *   Authorization: Bearer {api_key}
 *   { instance_id, to, message, client_reference_id } → { data: { message_id } }
 *
 * Recommended provider: https://wasendpilot.com (base URL https://api.wasendpilot.com/api).
 *
 * Two hard guards apply to every send:
 *   1. TEST_MODE=true  → nothing is sent (the OTP is still logged / returned in demo mode).
 *   2. Missing credentials → no HTTP call is attempted at all.
 *
 * Per project rule, a failure here never throws out of the caller: we log and
 * return null/false so OTP login keeps working even if WhatsApp is down.
 */
class WhatsAppService
{
    private const SEND_TEXT_PATH = '/messages/text';

    private const DEFAULT_OTP_TEMPLATE = 'Your {app_name} verification code is {otp}. It expires in {minutes} minutes. Please do not share this code with anyone.';

    public function __construct(private SystemSettingService $settings)
    {
    }

    // True only when every credential needed for an API call is present.
    public function isConfigured(): bool
    {
        return $this->apiKey() !== null
            && $this->instanceId() !== null
            && $this->baseUrl() !== '';
    }

    // Admin toggle + credentials. Sends are skipped when this is false.
    public function isEnabled(): bool
    {
        return $this->settings->getBool('whatsapp_enabled', false) && $this->isConfigured();
    }

    // TEST_MODE disables real WhatsApp traffic (see config/readyride.php).
    public function isTestMode(): bool
    {
        return (bool) config('readyride.test_mode', false);
    }

    /**
     * Send a login/verification OTP. Returns true only when the API accepted it.
     */
    public function sendOtp(string $phone, string $otp, string $type = 'user'): bool
    {
        $message = $this->renderOtpTemplate($otp);
        $reference = 'otp-' . $type . '-' . preg_replace('/\D+/', '', $phone) . '-' . now()->timestamp;

        return $this->sendText($phone, $message, $reference) !== null;
    }

    /**
     * Send a plain-text WhatsApp message.
     *
     * @param  bool  $force  Bypass the TEST_MODE guard (used by the admin "Send test" button only).
     * @return string|null   The provider message_id on success, null when skipped or failed.
     */
    public function sendText(string $to, string $message, ?string $reference = null, bool $force = false): ?string
    {
        if (! $this->isEnabled()) {
            Log::debug('WhatsApp send skipped: not enabled or credentials missing.', ['to' => $to]);

            return null;
        }

        if ($this->isTestMode() && ! $force) {
            Log::info('WhatsApp send skipped: TEST_MODE is on.', ['to' => $to]);

            return null;
        }

        try {
            $response = Http::withToken($this->apiKey())
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->post($this->endpoint(), array_filter([
                    'instance_id' => $this->instanceId(),
                    'to' => $this->normalizePhone($to),
                    'message' => $message,
                    'client_reference_id' => $reference,
                ], fn ($value) => $value !== null && $value !== ''));

            if (! $response->successful()) {
                Log::warning('WhatsApp send failed.', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                return null;
            }

            $messageId = $response->json('data.message_id');

            if (! $messageId) {
                Log::warning('WhatsApp send returned no message_id.', [
                    'to' => $to,
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                return null;
            }

            return (string) $messageId;
        } catch (\Throwable $e) {
            Log::error('WhatsApp send error: ' . $e->getMessage(), ['to' => $to]);

            return null;
        }
    }

    /**
     * Admin "Send test message" helper. Returns [ok, message] for the settings page.
     */
    public function sendTest(string $phone): array
    {
        if (! $this->settings->getBool('whatsapp_enabled', false)) {
            return [false, 'WhatsApp is turned off. Enable it and save the settings first.'];
        }

        if (! $this->isConfigured()) {
            return [false, 'API Base URL, API Key and Instance ID are all required. Save the credentials first.'];
        }

        // Forced: the admin explicitly asked for this send, so TEST_MODE does not block it.
        $messageId = $this->sendText(
            $phone,
            'Test message from ' . $this->appName() . '. Your WhatsApp integration is working.',
            'test-' . now()->timestamp,
            force: true,
        );

        if ($messageId === null) {
            return [false, 'Send failed. Check the credentials, the number, and storage/logs/laravel.log.'];
        }

        $note = $this->isTestMode() ? ' (TEST_MODE is on — real OTPs will NOT be sent.)' : '';

        return [true, 'Test message sent to ' . $this->normalizePhone($phone) . '. ID: ' . $messageId . $note];
    }

    // ── internals ────────────────────────────────────────────────────────────

    private function renderOtpTemplate(string $otp): string
    {
        $template = trim((string) $this->settings->get('whatsapp_otp_template', ''));

        if ($template === '') {
            $template = self::DEFAULT_OTP_TEMPLATE;
        }

        return strtr($template, [
            '{otp}' => $otp,
            '{app_name}' => $this->appName(),
            '{minutes}' => (string) OtpService::EXPIRY_MINUTES,
        ]);
    }

    private function appName(): string
    {
        return (string) $this->settings->get('app_name', config('app.name', 'ReadyRide'));
    }

    // Base URL without a trailing slash. Empty = not configured → nothing is sent.
    private function baseUrl(): string
    {
        return rtrim(trim((string) $this->settings->get('whatsapp_api_url', '')), '/');
    }

    // Tolerates admins pasting either the host or the full endpoint URL.
    private function endpoint(): string
    {
        $base = $this->baseUrl();

        if (str_ends_with($base, self::SEND_TEXT_PATH)) {
            return $base;
        }

        return $base . self::SEND_TEXT_PATH;
    }

    private function instanceId(): ?string
    {
        $value = trim((string) $this->settings->get('whatsapp_instance_id', ''));

        return $value !== '' ? $value : null;
    }

    // Stored encrypted; tolerates legacy plain-text values like the other secrets.
    private function apiKey(): ?string
    {
        $stored = $this->settings->get('whatsapp_api_key');

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

    /**
     * Providers want digits only with the country code (e.g. 8801XXXXXXXXX),
     * while the apps send local numbers (01XXXXXXXXX). The dialing code comes from
     * Settings → General (`phone_code`), so this works for any country.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);
        $code = (string) preg_replace('/\D+/', '', (string) $this->settings->get('phone_code', '+880'));

        if ($code === '' || $digits === '' || str_starts_with($digits, $code)) {
            return $digits;
        }

        return $code . ltrim($digits, '0');
    }
}
