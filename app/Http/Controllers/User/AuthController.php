<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\WelcomeMail;
use App\Services\CustomerPasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
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

        if (Auth::guard('web')->attempt($request->only('email', 'password'))) {
            if (Auth::guard('web')->user()->isBlocked()) {
                Auth::guard('web')->logout();
                return back()->withErrors(['email' => User::BLOCKED_MESSAGE])->onlyInput('email');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('home'))
                             ->with('success', 'Welcome back!');
        }

        return back()->withErrors(['email' => 'Invalid credentials. Please try again.']);
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
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string|max:255',
        ]);

        // Generate a custom user_id like U-XXXX
        $userId = 'U-' . str_pad(User::count() + 1, 4, '0', STR_PAD_LEFT);

        $user = User::create([
            'user_id'  => $userId,
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'address'  => $request->address,
        ]);

        // Auto login
        Auth::guard('web')->login($user);

        // Optional: Send welcome email
        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Exception $e) {
            \Log::error("Welcome mail failed: ".$e->getMessage());
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
     * Send reset link email
     */
    public function sendResetLinkEmail(Request $request, CustomerPasswordReset $passwordReset)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        try {
            $passwordReset->sendResetLink($request->email);
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to send reset email. Please try again later.');
        }

        return back()->with('success', 'We have e-mailed your password reset link!');
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
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
            'token' => 'required'
        ]);

        $reset = DB::table('password_reset_tokens')
            ->where([
                'email' => $request->email,
                'token' => $request->token
            ])
            ->first();

        if (!$reset || $passwordReset->isExpired($reset->created_at)) {
            return back()->withInput()->with('error', 'This reset link is invalid or has expired.');
        }

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where(['email' => $request->email])->delete();

        return redirect()->route('user.auth.login')->with('success', 'Your password has been changed!');
    }
}
