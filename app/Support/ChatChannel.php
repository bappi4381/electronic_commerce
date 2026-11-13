<?php

namespace App\Support;

use App\Models\Message;

class ChatChannel
{
    public static function forUser(int|string $userId): string
    {
        return 'chat.user.' . $userId;
    }

    /**
     * Guests are addressed by an HMAC of their session id so the raw id never reaches the browser or Pusher.
     */
    public static function forGuestSession(string $sessionId): string
    {
        return 'chat.guest.' . self::guestToken($sessionId);
    }

    public static function guestToken(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, (string) config('app.key'));
    }

    public static function forMessage(Message $message): string
    {
        return $message->user_id
            ? self::forUser($message->user_id)
            : self::forGuestSession((string) $message->session_id);
    }
}
