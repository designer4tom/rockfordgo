<?php

namespace App\Http\Middleware;

use App\Support\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends fresh deployments to the web installer (/install) until setup is done.
 * "Installed" is verified against the database (reachable + an admin exists),
 * not a bare lock file — so a stray/mis-shipped `installed` file can never make
 * the app skip setup and 500 with no admin to log in with (see InstallState).
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // Never gate the installer itself, health check, or API requests.
        if ($request->is('install', 'install/*', 'up', 'api/*')) {
            return $next($request);
        }

        if (InstallState::isInstalled()) {
            return $next($request);
        }

        return redirect('/install');
    }
}
