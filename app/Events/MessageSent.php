<?php

namespace App\Events;

use App\Models\Message;
use App\Support\ChatChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
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
        return new PrivateChannel(ChatChannel::forMessage($this->message));
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
            'is_admin' => (bool) $this->message->is_admin,
            'message' => $this->message->message,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
