<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Gate fresh deployments behind the web installer.
        $middleware->web(prepend: [
            \App\Http\Middleware\EnsureInstalled::class,
        ]);

        // Apply the admin's language preference on every web request.
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Preference cookies are client-readable (JS theme toggle) — keep them unencrypted.
        $middleware->encryptCookies(except: ['admin_locale', 'admin_theme']);

        // Payment gateways POST back to /payment/* without a CSRF token (MultiPay + legacy).
        $middleware->validateCsrfTokens(except: ['payment/*']);

        // Admin panel + mobile API middleware aliases.
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.super' => \App\Http\Middleware\SuperAdminOnly::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'auth.user' => \App\Http\Middleware\AuthenticateUser::class,
            'auth.driver' => \App\Http\Middleware\AuthenticateDriver::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
