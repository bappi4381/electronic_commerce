<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out customers who were blocked while already logged in (including "remember me" sessions).
 * Only the web guard is logged out so an admin session in the same browser is untouched.
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (!$user instanceof User || !$user->isBlocked()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        if ($request->is('admin', 'admin/*', 'broadcasting/*')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => User::BLOCKED_MESSAGE], 403);
        }

        $locale = in_array($request->segment(1), ['en', 'bn'], true) ? $request->segment(1) : config('app.locale', 'en');

        return redirect()->route('user.auth.login', ['locale' => $locale])->with('error', User::BLOCKED_MESSAGE);
    }
}
