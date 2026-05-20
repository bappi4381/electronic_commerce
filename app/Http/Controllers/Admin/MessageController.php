<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function index()
    {
        Log::info('Admin accessed message dashboard.');
        // Get all users/sessions who have sent messages
        $conversations = Message::select('user_id', 'session_id', DB::raw('MAX(created_at) as last_message_at'))
            ->groupBy('user_id', 'session_id')
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function($convo) {
                $convo->last_message_at = \Carbon\Carbon::parse($convo->last_message_at);
                if ($convo->user_id) {
                    $user = User::find($convo->user_id);
                    $convo->name = $user->name ?? 'User #'.$convo->user_id;
                    $convo->identifier = $convo->user_id;
                    $convo->type = 'user';
                } else {
                    $convo->name = 'Guest #'.substr($convo->session_id, 0, 8);
                    $convo->identifier = $convo->session_id;
                    $convo->type = 'guest';
                }
                $convo->last_msg = Message::where(function($q) use ($convo) {
                        if ($convo->user_id) $q->where('user_id', $convo->user_id);
                        else $q->where('session_id', $convo->session_id);
                    })->latest()->first();

                // Count unread messages from the user
                $convo->unread_count = Message::where('is_admin', false)
                    ->where('is_read', false)
                    ->where(function($q) use ($convo) {
                        if ($convo->user_id) $q->where('user_id', $convo->user_id);
                        else $q->where('session_id', $convo->session_id);
                    })->count();

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
