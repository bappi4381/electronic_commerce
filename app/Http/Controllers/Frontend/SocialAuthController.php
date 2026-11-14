<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class SocialAuthController extends Controller
{
    /** Only providers that are configured in config/services.php. */
    public const PROVIDERS = ['google', 'facebook'];

    /**
     * Redirect the user to the Provider authentication page.
     */
    public function redirect($provider)
    {
        abort_unless($this->enabled($provider), 404);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from Provider.
     */
    public function callback($provider)
    {
        abort_unless($this->enabled($provider), 404);
        $login = fn () => redirect()->route('user.auth.login', ['locale' => app()->getLocale()]);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Exception $e) {
            Log::notice('Social login failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return $login()->with('error', __('Authentication failed. Please try again.'));
        }

        // Facebook accounts registered with a phone number have no email.
        if (!$socialUser->getEmail()) {
            return $login()->with('error', __('Your email address does not come with this login method. Please register with your email instead.'));
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user && $user->isBlocked()) {
            return $login()->with('error', User::BLOCKED_MESSAGE);
        }

        if ($user) {
            if (!$user->provider_id) {
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            }
        } else {
            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $socialUser->getEmail(),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'password' => null,
                // The provider has confirmed the address.
                'is_email_verified' => true,
            ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->route('user.dashboard', ['locale' => app()->getLocale()])->with('success', 'Successfully logged in with ' . ucfirst($provider));
    }

    private function enabled(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true) && filled(config("services.{$provider}.client_id"));
    }
}
