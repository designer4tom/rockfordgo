<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdvancedSettingsController extends Controller implements HasMiddleware
{
    public function __construct(private SystemSettingService $settings)
    {
    }

    public static function middleware(): array
    {
        // The whole advanced area is super-admin only.
        return [
            new Middleware('admin.super'),
        ];
    }

    public function index()
    {
        return view('admin.settings.advanced', [
            'lastCleared' => $this->settings->get('cache_last_cleared'),
            'queueDriver' => config('queue.default'),
            'failedJobs' => DB::table('failed_jobs')->count(),
            'logTail' => $this->logTail(50),
            'dbSize' => $this->dbSizeMb(),
            'tableCounts' => $this->tableCounts(),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
        ]);
    }

    public function clearCache(Request $request)
    {
        $scope = $request->input('scope', 'all');

        match ($scope) {
            'settings' => Cache::flush(),
            'dashboard' => Cache::forget('dashboard_stats'),
            default => Artisan::call('cache:clear'),
        };

        $this->settings->set('cache_last_cleared', now()->toDateTimeString(), 'advanced');

        return back()->with('success', ucfirst($scope) . ' cache cleared.');
    }

    public function retryJobs()
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('success', 'Failed jobs queued for retry.');
    }

    public function clearFailedJobs()
    {
        Artisan::call('queue:flush');

        return back()->with('success', 'Failed jobs cleared.');
    }

    public function clearLog()
    {
        $path = storage_path('logs/laravel.log');
        if (is_file($path)) {
            file_put_contents($path, '');
        }

        return back()->with('success', 'Log file cleared.');
    }

    // Danger zone — requires the typed-CONFIRM guard from the form.
    public function clearAllOrders(Request $request)
    {
        $request->validate(['confirm' => ['required', 'in:CONFIRM']]);

        $count = DB::table('orders')->count();
        DB::table('order_locations')->delete();
        DB::table('orders')->delete();
        Cache::flush();

        return back()->with('success', $count . ' order(s) deleted and caches flushed.');
    }

    public function resetStatistics(Request $request)
    {
        $request->validate(['confirm' => ['required', 'in:CONFIRM']]);

        Cache::flush();
        $this->settings->set('cache_last_cleared', now()->toDateTimeString(), 'advanced');

        return back()->with('success', 'Cached statistics reset.');
    }

    // ---------------------------------------------------------------------

    private function logTail(int $lines): string
    {
        $path = storage_path('logs/laravel.log');
        if (! is_file($path)) {
            return 'No log file found.';
        }

        $content = file($path, FILE_IGNORE_NEW_LINES);
        if (! $content) {
            return 'Log is empty.';
        }

        return implode("\n", array_slice($content, -$lines));
    }

    private function dbSizeMb(): float
    {
        try {
            $db = config('database.connections.' . config('database.default') . '.database');
            $row = DB::selectOne(
                'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS mb FROM information_schema.tables WHERE table_schema = ?',
                [$db]
            );

            return (float) ($row->mb ?? 0);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function tableCounts(): array
    {
        $tables = ['users', 'drivers', 'orders', 'wallet_transactions', 'disputes', 'sos_alerts'];
        $counts = [];
        foreach ($tables as $t) {
            try {
                $counts[$t] = DB::table($t)->count();
            } catch (\Throwable) {
                $counts[$t] = 0;
            }
        }

        return $counts;
    }
}
