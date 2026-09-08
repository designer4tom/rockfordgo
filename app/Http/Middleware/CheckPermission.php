<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Verify the admin has the given ability on a module.
     *
     * Usage in routes: ->middleware('permission:orders,write')
     */
    public function handle(Request $request, Closure $next, string $module, string $ability = 'read'): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin || ! $admin->hasPermission($module, $ability)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
