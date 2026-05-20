<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        $id = $this->message->user_id ?? $this->message->session_id;
        $channelName = 'chat.' . (string)$id;
        return new \Illuminate\Broadcasting\Channel($channelName);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'user_id' => $this->message->user_id,
            'session_id' => $this->message->session_id,
            'is_admin' => (bool) $this->message->is_admin,
            'message' => $this->message->message,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
