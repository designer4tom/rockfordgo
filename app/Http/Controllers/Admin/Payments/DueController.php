<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DueController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments,read'),
        ];
    }

    public function index(Request $request)
    {
        $dueLimit = (float) SystemSetting::get('due_limit_amount', 500);
        $showAll = $request->query('filter') === 'all';

        $drivers = Driver::query()
            ->when(! $showAll, fn ($q) => $q->where('due_amount', '>', 0))
            ->withMax('dueTransactions as last_due_at', 'created_at')
            ->orderByDesc('due_amount')
            ->paginate(20)
            ->withQueryString();

        $atLimit = Driver::where('due_amount', '>=', $dueLimit)->where('due_amount', '>', 0)->count();

        return view('admin.payments.driver-dues.index', [
            'drivers' => $drivers,
            'dueLimit' => $dueLimit,
            'showAll' => $showAll,
            'summary' => [
                'total_due' => (float) Driver::sum('due_amount'),
                'with_due' => Driver::where('due_amount', '>', 0)->count(),
                'at_limit' => $atLimit,
            ],
        ]);
    }
}
