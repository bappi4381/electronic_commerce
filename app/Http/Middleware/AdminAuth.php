<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        // Make @can, Gate and auth()->user() resolve the signed-in admin on admin pages.
        Auth::shouldUse('admin');

        return $next($request);
    }
}
