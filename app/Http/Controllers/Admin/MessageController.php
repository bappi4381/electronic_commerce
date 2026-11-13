<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Support\ChatChannel;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        // One aggregate query per page, then the last messages and users are loaded in two batches.
        $conversations = Message::query()
            ->select('user_id', 'session_id')
            ->selectRaw('MAX(id) as last_id, MAX(created_at) as last_message_at')
            ->selectRaw('SUM(CASE WHEN is_admin = 0 AND is_read = 0 THEN 1 ELSE 0 END) as unread_count')
            ->groupBy('user_id', 'session_id')
            ->orderByDesc('last_message_at')
            ->paginate(30);

        $lastMessages = Message::whereIn('id', $conversations->pluck('last_id'))->get()->keyBy('id');
        $users = User::whereIn('id', $conversations->pluck('user_id')->filter()->unique())->pluck('name', 'id');

        $conversations->getCollection()->transform(function ($convo) use ($lastMessages, $users) {
            $convo->last_message_at = \Carbon\Carbon::parse($convo->last_message_at);
            $convo->last_msg = $lastMessages->get($convo->last_id);
            $convo->unread_count = (int) $convo->unread_count;

            if ($convo->user_id) {
                $convo->name = $users[$convo->user_id] ?? 'User #' . $convo->user_id;
                $convo->identifier = $convo->user_id;
                $convo->type = 'user';
                $convo->channel = ChatChannel::forUser($convo->user_id);
            } else {
                $convo->name = 'Guest #' . substr($convo->session_id, 0, 8);
                $convo->identifier = $convo->session_id;
                $convo->type = 'guest';
                $convo->channel = ChatChannel::forGuestSession($convo->session_id);
            }

            return $convo;
        });

        return view('admin.messages.index', compact('conversations'));
    }

    public function fetchMessages($identifier)
    {
        $messages = Message::where(function($q) use ($identifier) {
                if (is_numeric($identifier)) {
                    $q->where('user_id', $identifier);
                } else {
                    $q->where('session_id', $identifier);
                }
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request, $identifier)
    {
        $request->validate(['message' => 'required|string']);

        $message = Message::create([
            'user_id' => is_numeric($identifier) ? $identifier : null,
            'session_id' => is_numeric($identifier) ? null : $identifier,
            'is_admin' => true,
            'message' => $request->message,
            'is_read' => true, // Admin's own messages are "read"
        ]);

        // Broadcast to user (requires Pusher — safe to fail if not configured)
        try {
            \Illuminate\Support\Facades\Log::info('Broadcasting message from Admin to ' . $identifier);
            broadcast(new \App\Events\MessageSent($message));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Pusher Broadcast Failed: ' . $e->getMessage());
        }

        return response()->json(['status' => 'Message Sent!', 'message' => $message]);
    }

    public function markAsRead($identifier)
    {
        Message::where('is_admin', false)
            ->where('is_read', false)
            ->where(function($q) use ($identifier) {
                if (is_numeric($identifier)) {
                    $q->where('user_id', $identifier);
                } else {
                    $q->where('session_id', $identifier);
                }
            })
            ->update(['is_read' => true]);

        return response()->json(['status' => 'Success']);
    }
}
