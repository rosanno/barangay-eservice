<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $locale = $request->user()?->locale
            ?? $request->getPreferredLanguage(['en', 'fil'])
            ?? 'en';
        app()->setLocale(in_array($locale, ['en', 'fil']) ? $locale : 'en');
        return $next($request);
    }
}
