<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->segment(1);

        if (in_array($locale, ['en', 'bn'])) {
            App::setLocale($locale);
            // Default URLs to include the locale
            URL::defaults(['locale' => $locale]);
        } else {
            // Default to English if not provided or invalid
            App::setLocale('en');
            URL::defaults(['locale' => 'en']);
        }

        // Forget the locale parameter so it's not injected into every controller method
        if ($request->route()) {
            $request->route()->forgetParameter('locale');
        }

        return $next($request);
    }
}
