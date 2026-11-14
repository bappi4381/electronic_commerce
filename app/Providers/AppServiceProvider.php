<?php

namespace App\Providers;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use App\Models\Admin;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Collects the Pixel events of one request; must not leak between requests (queues, Octane).
        $this->app->scoped(\App\Services\FacebookTracking::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The live MySQL defaults to MyISAM, whose 1000-byte index limit rejects utf8mb4 varchar(255) keys.
        Schema::defaultStringLength(191);

        \App\Services\Courier\CourierSettings::apply();

        Gate::before(function ($user, $ability) {
            if ($user instanceof Admin && $user->hasRole('Super Admin')) {
                return true;
            }
            return null;
        });

        // Lets guests authenticate private broadcast channels; access is decided in routes/channels.php.
        Auth::viaRequest('chat-guest', function (Request $request) {
            if (! $request->hasSession() || ! $request->session()->getId()) {
                return null;
            }

            return new GenericUser(['id' => 'guest']);
        });
    }
}
