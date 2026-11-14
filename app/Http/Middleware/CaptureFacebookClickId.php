<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Visitors arriving from a Facebook ad carry ?fbclid=… . Store it as the _fbc cookie
 * (fb.1.{ms timestamp}.{fbclid}) so Conversions API events can be attributed to the ad click,
 * even when the browser Pixel is blocked. The cookie is excluded from encryption in bootstrap/app.php.
 */
class CaptureFacebookClickId
{
    private const LIFETIME_DAYS = 90;

    public function handle(Request $request, Closure $next): Response
    {
        $fbclid = $request->query('fbclid');

        if (!is_string($fbclid) || $fbclid === '' || strlen($fbclid) > 500 || !preg_match('/^[\w\-.]+$/', $fbclid)) {
            return $next($request);
        }

        $current = (string) $request->cookie('_fbc');
        if (str_ends_with($current, '.' . $fbclid)) {
            return $next($request);
        }

        $fbc = 'fb.1.' . (int) floor(microtime(true) * 1000) . '.' . $fbclid;
        $request->cookies->set('_fbc', $fbc);

        $response = $next($request);
        $response->headers->setCookie(Cookie::create('_fbc', $fbc, now()->addDays(self::LIFETIME_DAYS), '/', null, $request->isSecure(), false, false, Cookie::SAMESITE_LAX));

        return $response;
    }
}
