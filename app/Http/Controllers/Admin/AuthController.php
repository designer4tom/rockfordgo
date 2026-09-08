<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminLoginLog;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    // Max failed attempts before a temporary lockout.
    private const MAX_ATTEMPTS = 5;

    // Lockout duration in seconds (15 minutes).
    private const LOCKOUT_SECONDS = 900;

    // ---------------------------------------------------------------------
    // Login
    // ---------------------------------------------------------------------

    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minute(s).',
            ]);
        }

        // Validate credentials WITHOUT starting a session yet (so we can
        // interrupt for 2FA when required).
        $admin = Admin::where('email', $credentials['email'])->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            if ($admin) {
                $this->logAttempt($admin->id, $request, 'failed');
            }

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (! $admin->is_active) {
            $this->logAttempt($admin->id, $request, 'failed');

            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $remember = $request->boolean('remember');

        // Two-factor enabled: hold the login and challenge for a code.
        if ($admin->two_factor_enabled && $admin->two_factor_secret) {
            $request->session()->put('pending_2fa_admin_id', $admin->id);
            $request->session()->put('pending_2fa_remember', $remember);

            return redirect()->route('admin.two-factor');
        }

        return $this->completeLogin($request, $admin, $remember);
    }

    // ---------------------------------------------------------------------
    // Two-factor challenge (during login)
    // ---------------------------------------------------------------------

    public function showTwoFactor(Request $request)
    {
        if (! $request->session()->has('pending_2fa_admin_id')) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.two-factor');
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $adminId = $request->session()->get('pending_2fa_admin_id');

        if (! $adminId) {
            return redirect()->route('admin.login');
        }

        $admin = Admin::find($adminId);

        if (! $admin || ! $admin->is_active) {
            $request->session()->forget(['pending_2fa_admin_id', 'pending_2fa_remember']);

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Unable to verify your account.']);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($admin->two_factor_secret, preg_replace('/\s+/', '', $request->code));

        if (! $valid) {
            $this->logAttempt($admin->id, $request, 'failed');

            throw ValidationException::withMessages([
                'code' => 'The verification code is invalid.',
            ]);
        }

        $remember = (bool) $request->session()->get('pending_2fa_remember', false);
        $request->session()->forget(['pending_2fa_admin_id', 'pending_2fa_remember']);

        return $this->completeLogin($request, $admin, $remember);
    }

    // ---------------------------------------------------------------------
    // Logout
    // ---------------------------------------------------------------------

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'You have been logged out.');
    }

    // ---------------------------------------------------------------------
    // Login history
    // ---------------------------------------------------------------------

    public function loginHistory(Request $request)
    {
        $logs = AdminLoginLog::with('admin')
            ->where('admin_id', Auth::guard('admin')->id())
            ->latest('created_at')
            ->paginate(20);

        return view('admin.login-history.index', compact('logs'));
    }

    // ---------------------------------------------------------------------
    // Two-factor setup (enable / disable)
    // ---------------------------------------------------------------------

    public function showTwoFactorSetup(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $google2fa = new Google2FA();

        $qrSvg = null;
        $secret = null;

        if (! $admin->two_factor_enabled) {
            // Reuse a pending secret across reloads so the QR stays stable.
            $secret = $request->session()->get('2fa_setup_secret');

            if (! $secret) {
                $secret = $google2fa->generateSecretKey();
                $request->session()->put('2fa_setup_secret', $secret);
            }

            $qrUrl = $google2fa->getQRCodeUrl(
                config('app.name', 'ReadyRide'),
                $admin->email,
                $secret
            );

            $qrSvg = $this->qrCodeSvg($qrUrl);
        }

        return view('admin.auth.two-factor-setup', compact('admin', 'qrSvg', 'secret'));
    }

    public function enableTwoFactor(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $admin = Auth::guard('admin')->user();
        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret) {
            return redirect()->route('admin.two-factor.setup')
                ->withErrors(['code' => 'Your setup session expired. Please scan the code again.']);
        }

        $google2fa = new Google2FA();

        if (! $google2fa->verifyKey($secret, preg_replace('/\s+/', '', $request->code))) {
            throw ValidationException::withMessages([
                'code' => 'The verification code is invalid. Try again.',
            ]);
        }

        $admin->forceFill([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
        ])->save();

        $request->session()->forget('2fa_setup_secret');

        return redirect()->route('admin.two-factor.setup')
            ->with('success', 'Two-factor authentication has been enabled.');
    }

    public function disableTwoFactor(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        $admin = Auth::guard('admin')->user();

        if (! Hash::check($request->current_password, $admin->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The password is incorrect.',
            ]);
        }

        $admin->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ])->save();

        return redirect()->route('admin.two-factor.setup')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    // Finish authentication: start session, record metadata, log success.
    private function completeLogin(Request $request, Admin $admin, bool $remember)
    {
        Auth::guard('admin')->login($admin, $remember);
        $request->session()->regenerate();

        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->logAttempt($admin->id, $request, 'success');

        return redirect()->intended(route('admin.dashboard'));
    }

    private function logAttempt(int $adminId, Request $request, string $status): void
    {
        AdminLoginLog::create([
            'admin_id' => $adminId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return strtolower((string) $request->input('email')) . '|' . $request->ip();
    }

    // Render an otpauth URL as an inline SVG QR code.
    private function qrCodeSvg(string $data): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220, 1),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($data);
    }
}
