<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReferralController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:users,read'),
        ];
    }

    public function index(Request $request)
    {
        $referrals = Referral::with(['referrer:id,name,phone', 'referee:id,name,phone'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Referral::count(),
            'rewarded' => Referral::where('status', 'rewarded')->count(),
            'pending' => Referral::where('status', 'pending')->count(),
            'bonus_paid' => (float) Referral::where('status', 'rewarded')
                ->selectRaw('COALESCE(SUM(referrer_bonus + referee_bonus), 0) as total')
                ->value('total'),
        ];

        return view('admin.referrals.index', [
            'referrals' => $referrals,
            'stats' => $stats,
            'filters' => $request->only(['status', 'from', 'to']),
        ]);
    }
}
