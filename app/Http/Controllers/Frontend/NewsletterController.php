<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:150',
        ]);

        $subscriber = Subscriber::firstOrNew(['email' => strtolower(trim($data['email']))]);

        if ($subscriber->exists && $subscriber->status) {
            return response()->json(['success' => false, 'message' => 'You are already subscribed.'], 422);
        }

        // Someone who unsubscribed earlier can opt back in.
        $subscriber->status = true;
        $subscriber->save();

        return response()->json(['success' => true, 'message' => 'Thank you for subscribing!']);
    }
}
