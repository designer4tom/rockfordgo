<?php

namespace App\Http\Middleware;

use App\Services\TranslationManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the admin's language preference from the `admin_locale` cookie,
 * falling back to the manager's default locale, then loads that locale's
 * editable JSON translations into the translator (admin.* namespace).
 */
class SetLocale
{
    public function __construct(private TranslationManager $translations)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('admin_locale');

        if (! $locale || ! $this->translations->exists($locale)) {
            $locale = $this->translations->defaultLocale();
        }

        App::setLocale($locale);
        $this->translations->boot($locale);

        return $next($request);
    }
}
