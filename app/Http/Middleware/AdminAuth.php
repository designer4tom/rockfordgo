<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * TEMPORARY ROCKFORDGO DEVELOPMENT ACCESS
     *
     * Automatically logs in the first active administrator.
     * REMOVE THIS BYPASS BEFORE PRODUCTION.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {

            $admin = Admin::where('is_active', true)->first();

            if (! $admin) {
                abort(503, 'No active admin account exists.');
            }

            Auth::guard('admin')->login($admin);

            $request->session()->regenerate();
        }

        return $next($request);
    }
}
