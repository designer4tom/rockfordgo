<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverPerformanceController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $driver = $request->user();

        $breakdownRaw = DB::table('ratings')
            ->where('ratee_type', 'driver')->where('ratee_id', $driver->id)
            ->selectRaw('rating, COUNT(*) as c')->groupBy('rating')->pluck('c', 'rating');

        $breakdown = [];
        for ($i = 5; $i >= 1; $i--) {
            $breakdown[(string) $i] = (int) ($breakdownRaw[$i] ?? 0);
        }

        $recent = Rating::where('ratee_type', 'driver')->where('ratee_id', $driver->id)
            ->latest()->limit(10)->get();
        $customers = User::whereIn('id', $recent->pluck('rater_id'))->get(['id', 'name', 'avatar'])->keyBy('id');

        return $this->success([
            'average_rating' => number_format((float) $driver->average_rating, 2, '.', ''),
            'total_ratings' => array_sum($breakdown),
            'rating_breakdown' => $breakdown,
            'acceptance_rate' => number_format((float) $driver->acceptance_rate, 2, '.', ''),
            'completion_rate' => number_format((float) $driver->completion_rate, 2, '.', ''),
            'cancellation_rate' => number_format((float) $driver->cancellation_rate, 2, '.', ''),
            'recent_ratings' => $recent->map(function ($r) use ($customers) {
                $u = $customers[$r->rater_id] ?? null;

                return [
                    'rating' => $r->rating,
                    'comment' => $r->comment,
                    'customer_name' => $u->name ?? null,
                    'customer_image' => ($u && $u->avatar) ? asset(\Illuminate\Support\Facades\Storage::url($u->avatar)) : null,
                    'created_at' => $r->created_at->toDateString(),
                ];
            }),
        ], 'Performance fetched.');
    }
}
