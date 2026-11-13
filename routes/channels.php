<?php

use App\Support\ChatChannel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

$adminCanReadChats = fn (): bool => (bool) Auth::guard('admin')->user()?->can('messages.view');

// Private channel for a logged-in customer's support chat
Broadcast::channel('chat.user.{userId}', function ($user, $userId) use ($adminCanReadChats) {
    return $adminCanReadChats() || (int) Auth::guard('web')->id() === (int) $userId;
}, ['guards' => ['web', 'admin']]);

// Private channel for a guest's support chat, keyed by an HMAC of their session id
Broadcast::channel('chat.guest.{token}', function ($user, $token) use ($adminCanReadChats) {
    return $adminCanReadChats()
        || (request()->hasSession() && hash_equals(ChatChannel::guestToken(request()->session()->getId()), (string) $token));
}, ['guards' => ['admin', 'web', 'chat_guest']]);
