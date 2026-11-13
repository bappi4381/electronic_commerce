<?php

namespace App\Notifications\Admin;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Str;

class NewChatMessage extends AdminNotification
{
    public function __construct(Message $message)
    {
        $sender = $message->user_id
            ? (User::find($message->user_id)?->name ?? 'User #' . $message->user_id)
            : 'Guest #' . substr((string) $message->session_id, 0, 8);

        $this->data = [
            'kind'  => 'chat',
            'title' => 'New message from ' . $sender,
            'body'  => Str::limit($message->message, 90),
            'url'   => route('admin.messages.index', [], false),
            'icon'  => 'bi-chat-dots-fill',
            'color' => 'indigo',
        ];
    }
}
