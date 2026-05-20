<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for user-admin chat
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    // Only the user themselves or an admin can access this channel
    // We assume an admin guard is used for admins, but if user model is used:
    return (int) $user->id === (int) $userId; 
});
