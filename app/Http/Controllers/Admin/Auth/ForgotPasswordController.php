<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('admin.auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::broker('admins')->sendResetLink($request->only('email'));

            if ($status !== Password::RESET_LINK_SENT) {
                Log::info('Admin password reset link not sent', ['status' => $status, 'ip' => $request->ip()]);
            }
        } catch (\Throwable $e) {
            Log::error('Admin password reset mail failed: ' . $e->getMessage());
        }

        // Same response whether or not the account exists, to avoid revealing admin emails.
        return back()->with('status', 'If that email belongs to an administrator, a password reset link has been sent.');
    }
}
