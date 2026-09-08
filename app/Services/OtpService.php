<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Generates, sends, and verifies one-time passwords for phone login.
 * Delivery goes over WhatsApp when it is configured in Settings → Notifications;
 * the code is always logged as a fallback (a real SMS gateway is still TODO).
 */
class OtpService
{
    public const EXPIRY_MINUTES = 2;

    public function __construct(private WhatsAppService $whatsapp)
    {
    }

    // Generate an OTP, cache it, send it over WhatsApp (when enabled), and return the code.
    public function send(string $phone, string $type = 'user'): string
    {
        $otp = (string) random_int(100000, 999999);
        Cache::put($this->key($phone, $type), $otp, now()->addMinutes(self::EXPIRY_MINUTES));

        // TODO: integrate an SMS gateway. For now we log it as a fallback.
        Log::info("OTP for {$type} {$phone}: {$otp}");

        // No-op when WhatsApp is off/unconfigured or TEST_MODE is on — never throws.
        $this->whatsapp->sendOtp($phone, $otp, $type);

        $this->setCooldown($phone, $type);

        return $otp;
    }

    // Verify a submitted OTP; consume it on success.
    public function verify(string $phone, string $otp, string $type = 'user'): bool
    {
        $key = $this->key($phone, $type);
        $stored = Cache::get($key);

        if ($stored && (string) $stored === (string) $otp) {
            Cache::forget($key);
            return true;
        }

        return false;
    }

    public function canResend(string $phone, string $type = 'user'): bool
    {
        return ! Cache::has("otp_cooldown_{$type}_{$phone}");
    }

    public function setCooldown(string $phone, string $type = 'user'): void
    {
        Cache::put("otp_cooldown_{$type}_{$phone}", true, now()->addSeconds(30));
    }

    private function key(string $phone, string $type): string
    {
        return "otp_{$type}_{$phone}";
    }
}
