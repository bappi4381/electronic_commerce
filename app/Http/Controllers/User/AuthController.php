<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\WelcomeMail;
use App\Services\CustomerPasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Failed logins allowed per email + IP before a lockout. */
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 300;

    public function showAccountPage()
    {
        return view('frontend.auth.login');
    }

    /**
     * Handle login submission
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $key = 'login:' . Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            Log::notice('Customer login locked out', ['ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'email' => __('Too many login attempts. Please try again in :minutes minute(s) or reset your password.', ['minutes' => $minutes]),
            ]);
        }

        if (Auth::guard('web')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::clear($key);

            if (Auth::guard('web')->user()->isBlocked()) {
                Auth::guard('web')->logout();
                return back()->withErrors(['email' => User::BLOCKED_MESSAGE])->onlyInput('email');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('home'))
                             ->with('success', 'Welcome back!');
        }

        RateLimiter::hit($key, self::LOCKOUT_SECONDS);

        return back()->withErrors(['email' => __('Invalid credentials. Please try again.')])->onlyInput('email', 'remember');
    }

    /**
     * Show registration form
     */
    public function showRegisterForm()
    {
        return view('frontend.auth.registration');
    }

    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'address'  => $request->address,
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Exception $e) {
            Log::error("Welcome mail failed: ".$e->getMessage());
        }

        return redirect()->intended(route('home'))->with('success', 'Registration successful!');
    }

    /**
     * Logout the user
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out.');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm()
    {
        return view('frontend.auth.forgot-password');
    }

    /**
     * Send reset link email. The answer is the same whether or not the email has an account,
     * so the form cannot be used to find out who shops here.
     */
    public function sendResetLinkEmail(Request $request, CustomerPasswordReset $passwordReset)
    {
        $request->validate(['email' => 'required|email|max:150']);

        $user = User::where('email', $request->email)->first();

        if ($user && !$user->isBlocked()) {
            try {
                $passwordReset->sendResetLink($user->email);
            } catch (\Exception $e) {
                report($e);
                return back()->with('error', __('Failed to send reset email. Please try again later.'));
            }
        }

        return back()->with('success', __('If an account exists for that email, we have sent a password reset link. Check your inbox and spam folder.'));
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm($token, Request $request)
    {
        return view('frontend.auth.reset-password')->with(['token' => $token, 'email' => $request->email]);
    }

    /**
     * Handle password reset
     */
    public function resetPassword(Request $request, CustomerPasswordReset $passwordReset)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'token' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !$passwordReset->tokenIsValid($request->email, $request->token)) {
            return back()->withInput($request->only('email'))->with('error', __('This reset link is invalid or has expired.'));
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        $passwordReset->forget($request->email);
        // Whoever had the old password is signed out everywhere.
        $user->endAllSessions();

        return redirect()->route('user.auth.login')->with('success', __('Your password has been changed. Please log in.'));
    }
}
