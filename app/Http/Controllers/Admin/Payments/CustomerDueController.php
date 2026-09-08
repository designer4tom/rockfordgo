<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CustomerDueController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments,read'),
        ];
    }

    public function index(Request $request)
    {
        $dueLimit = (float) SystemSetting::get('sender_due_limit_amount', 500);
        $showAll = $request->query('filter') === 'all';

        $customers = User::query()
            ->when(! $showAll, fn ($q) => $q->where('due_amount', '>', 0))
            ->withMax('dueTransactions as last_due_at', 'created_at')
            ->orderByDesc('due_amount')
            ->paginate(20)
            ->withQueryString();

        $atLimit = User::where('due_amount', '>=', $dueLimit)->where('due_amount', '>', 0)->count();

        return view('admin.payments.customer-dues.index', [
            'customers' => $customers,
            'dueLimit' => $dueLimit,
            'showAll' => $showAll,
            'summary' => [
                'total_due' => (float) User::sum('due_amount'),
                'with_due' => User::where('due_amount', '>', 0)->count(),
                'at_limit' => $atLimit,
            ],
        ]);
    }
}
