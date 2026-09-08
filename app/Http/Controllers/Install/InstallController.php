<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Guided web installer for buyers. Flow:
 *   requirements → database (test + save) → migrate (+seed) → admin → finished
 * Locked once storage/installed exists (see EnsureInstalled middleware).
 */
class InstallController extends Controller
{
    private const MODULES = ['dashboard', 'users', 'drivers', 'orders', 'payments', 'settings', 'reports', 'sos', 'disputes'];

    // Step 1 — requirements check.
    public function requirements()
    {
        if ($this->installed()) {
            return redirect()->route('admin.login');
        }

        $checks = [
            'PHP >= 8.3' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'OpenSSL' => extension_loaded('openssl'),
            'Mbstring' => extension_loaded('mbstring'),
            'cURL' => extension_loaded('curl'),
            'storage/ writable' => is_writable(storage_path()),
            'bootstrap/cache writable' => is_writable(base_path('bootstrap/cache')),
            '.env writable' => is_writable(base_path('.env')) || is_writable(base_path()),
        ];

        return view('install.requirements', ['checks' => $checks, 'ready' => ! in_array(false, $checks, true)]);
    }

    // Step 2 — database form.
    public function database()
    {
        if ($this->installed()) {
            return redirect()->route('admin.login');
        }

        return view('install.database');
    }

    public function saveDatabase(Request $request)
    {
        $data = $request->validate([
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'string'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
            'app_url' => ['required', 'string'],
        ]);

        // Test the connection on a throwaway config before saving.
        Config::set('database.connections.install', [
            'driver' => 'mysql',
            'host' => $data['db_host'],
            'port' => $data['db_port'],
            'database' => $data['db_database'],
            'username' => $data['db_username'],
            'password' => $data['db_password'] ?? '',
        ]);

        try {
            DB::connection('install')->getPdo();
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not connect: ' . $e->getMessage());
        }

        $this->writeEnv([
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'APP_URL' => rtrim($data['app_url'], '/'),
        ]);

        // Make sure an APP_KEY exists.
        if (! config('app.key')) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        return redirect()->route('install.migrate');
    }

    // Step 3 — run migrations + essential seeders.
    public function migrate()
    {
        if ($this->installed()) {
            return redirect()->route('admin.login');
        }

        return view('install.migrate');
    }

    public function runMigrate(Request $request)
    {
        if ($this->installed()) {
            return redirect()->route('admin.login');
        }

        try {
            Artisan::call('migrate', ['--force' => true]);

            // Essential content (no demo data unless requested).
            foreach (['SystemSettingsSeeder', 'ServiceSeeder', 'VehicleCategorySeeder', 'PageSeeder', 'HelpContentSeeder'] as $seeder) {
                if (class_exists('Database\\Seeders\\' . $seeder)) {
                    Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
                }
            }

            if ($request->boolean('demo_data') && class_exists('Database\\Seeders\\DemoDataSeeder')) {
                Artisan::call('db:seed', ['--class' => 'DemoDataSeeder', '--force' => true]);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Migration failed: ' . $e->getMessage());
        }

        return redirect()->route('install.admin');
    }

    // Step 4 — create the super admin.
    public function admin()
    {
        if ($this->installed()) {
            return redirect()->route('admin.login');
        }

        return view('install.admin');
    }

    public function saveAdmin(Request $request)
    {
        if ($this->installed()) {
            return redirect()->route('admin.login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = Admin::updateOrCreate(
            ['email' => $data['email']],
            ['name' => $data['name'], 'password' => Hash::make($data['password']), 'role' => 'super_admin', 'is_active' => true],
        );

        foreach (self::MODULES as $module) {
            AdminPermission::updateOrCreate(
                ['admin_id' => $admin->id, 'module' => $module],
                ['can_read' => true, 'can_write' => true, 'can_delete' => true],
            );
        }

        // Lock the installer + cache config/routes for production.
        \App\Support\InstallState::markInstalled();
        Artisan::call('optimize:clear');

        return redirect()->route('install.finished');
    }

    public function finished()
    {
        return view('install.finished');
    }

    // ---------------------------------------------------------------------

    private function installed(): bool
    {
        return \App\Support\InstallState::isInstalled();
    }

    // Update or append KEY=value pairs in the .env file.
    private function writeEnv(array $pairs): void
    {
        $path = base_path('.env');
        $env = is_file($path) ? file_get_contents($path) : '';

        foreach ($pairs as $key => $value) {
            $escaped = preg_match('/\s/', (string) $value) ? '"' . $value . '"' : $value;
            $line = $key . '=' . $escaped;
            if (preg_match("/^{$key}=.*$/m", $env)) {
                $env = preg_replace("/^{$key}=.*$/m", $line, $env);
            } else {
                $env .= PHP_EOL . $line;
            }
        }

        file_put_contents($path, $env);
    }
}
