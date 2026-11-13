<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CustomerPasswordReset
{
    public const EXPIRE_MINUTES = 60;

    /**
     * Store a fresh reset token for the customer and email them the reset link.
     * $newAccount switches the copy to "set your password" for accounts created on the customer's behalf.
     */
    public function sendResetLink(string $email, bool $newAccount = false): void
    {
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        $resetUrl = route('password.reset', [
            'locale' => app()->getLocale(),
            'token' => $token,
            'email' => $email,
        ]);

        Mail::send('frontend.auth.emails.password-reset', [
            'resetUrl' => $resetUrl,
            'newAccount' => $newAccount,
            'expireMinutes' => self::EXPIRE_MINUTES,
        ], function ($message) use ($email, $newAccount) {
            $message->to($email);
            $message->subject($newAccount ? 'Set Your Account Password' : 'Reset Password Notification');
        });
    }

    public function isExpired(?string $createdAt): bool
    {
        return !$createdAt || Carbon::parse($createdAt)->addMinutes(self::EXPIRE_MINUTES)->isPast();
    }
}
