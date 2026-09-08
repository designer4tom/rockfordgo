<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Services\SystemSettingService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    use ApiResponse;

    public function __construct(private SystemSettingService $settings)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $referrals = Referral::where('referrer_id', $user->id)
            ->with('referee:id,name,created_at')
            ->latest()
            ->get();

        $bonus = (float) $this->settings->get('referral_referee_bonus', 0);

        return $this->success([
            'referral_code' => $user->referral_code,
            'share_message' => "Use my code {$user->referral_code} on " . $this->settings->get('app_name', 'ReadyRide')
                . ' and get ' . $this->settings->get('currency_symbol', '৳') . number_format($bonus, 0) . ' bonus!',
            'total_referred' => $referrals->count(),
            'total_earned' => number_format((float) $referrals->where('status', 'rewarded')->sum('referrer_bonus'), 2, '.', ''),
            'referrals' => $referrals->map(fn ($r) => [
                'name' => $r->referee->name ?? '—',
                'joined_at' => $r->referee?->created_at?->toDateString(),
                'bonus_status' => $r->status,
                'bonus_amount' => number_format((float) $r->referrer_bonus, 2, '.', ''),
            ]),
        ], 'Referral info fetched.');
    }
}
