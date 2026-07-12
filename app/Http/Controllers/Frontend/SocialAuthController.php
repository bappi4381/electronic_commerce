<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the Provider authentication page.
     */
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from Provider.
     */
    public function callback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            
            // Check if a user with this email already exists
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // If user exists, update their provider and provider_id if they aren't set
                if (!$user->provider_id) {
                    $user->update([
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                    ]);
                }
            } else {
                // Create a new user
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                    'email' => $socialUser->getEmail(),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'password' => null, // No password for social login users initially
                    'is_email_verified' => true, // We trust social providers to have verified emails
                ]);
            }

            Auth::login($user, true);

            return redirect()->route('user.dashboard')->with('success', 'Successfully logged in with ' . ucfirst($provider));

        } catch (Exception $e) {
            return redirect()->route('user.auth.login')->with('error', 'Authentication failed. Please try again.');
        }
    }
}
