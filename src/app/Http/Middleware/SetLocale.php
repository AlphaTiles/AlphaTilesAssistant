<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the locale from the session, or the browser's Accept-Language
     * header on first visit, and store it in the session for future requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = array_keys(config('app.available_locales', []));

        $locale = $request->session()->get('locale');

        if (!is_string($locale) || !in_array($locale, $availableLocales, true)) {
            $locale = $request->getPreferredLanguage($availableLocales) ?? config('app.fallback_locale');
            $request->session()->put('locale', $locale);
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
