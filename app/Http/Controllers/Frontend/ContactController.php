<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Page;
use App\Models\Setting;
use App\Rules\BangladeshiMobile;
use App\Services\AdminNotifier;
use App\Support\BdPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public const TOPICS = ['order', 'product', 'warranty', 'return', 'business', 'other'];

    public function show()
    {
        $user = auth()->user();

        return view('frontend.pages.contact', [
            'contact' => [
                'address' => Setting::get('contact_address'),
                'phone' => Setting::get('contact_phone'),
                'email' => Setting::get('contact_email'),
            ],
            'socials' => collect(['facebook_url' => ['bi-facebook', 'Facebook'], 'instagram_url' => ['bi-instagram', 'Instagram'], 'twitter_url' => ['bi-twitter-x', 'X']])
                ->map(fn ($meta, $key) => ['url' => Setting::get($key), 'icon' => $meta[0], 'label' => $meta[1]])
                ->filter(fn ($social) => $social['url'])
                ->values(),
            'helpPages' => Page::footerLinks(),
            'prefill' => ['name' => $user?->name, 'email' => $user?->email, 'phone' => $user?->phone],
        ]);
    }

    /**
     * The message lands in Admin › Messages (same thread as the customer's live chat) and rings the admin bell.
     */
    public function send(Request $request, AdminNotifier $notifier)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => ['nullable', 'required_without:email', 'string', 'max:20', new BangladeshiMobile()],
            'email' => 'nullable|required_without:phone|email|max:150',
            'topic' => 'required|in:' . implode(',', self::TOPICS),
            'order_id' => 'nullable|string|max:40',
            'message' => 'required|string|min:10|max:2000',
            'website' => 'prohibited',
        ], [
            'phone.required_without' => __('Give us a phone number or an email address so we can reply.'),
            'email.required_without' => __('Give us a phone number or an email address so we can reply.'),
            'website.prohibited' => __('Something went wrong. Please try again.'),
        ]);

        $userId = auth()->id();
        $lines = array_filter([
            '📨 ' . __('Contact form') . ' — ' . __(self::topicLabel($data['topic'])),
            __('Name') . ': ' . $data['name'],
            !empty($data['phone']) ? __('Phone') . ': ' . BdPhone::normalize($data['phone']) : null,
            !empty($data['email']) ? __('Email') . ': ' . $data['email'] : null,
            !empty($data['order_id']) ? __('Order') . ': ' . $data['order_id'] : null,
            '',
            trim($data['message']),
        ], fn ($line) => $line !== null);

        $message = Message::create([
            'user_id' => $userId,
            'session_id' => $userId ? null : session()->getId(),
            'is_admin' => false,
            'message' => implode("\n", $lines),
        ]);

        try {
            broadcast(new \App\Events\MessageSent($message));
        } catch (\Throwable $e) {
            Log::warning('Contact form broadcast failed: ' . $e->getMessage());
        }
        $notifier->newChatMessage($message);

        return redirect()->route('contact')->with('contact_sent', true);
    }

    public static function topicLabel(string $topic): string
    {
        return [
            'order' => 'An order I placed',
            'product' => 'A product question',
            'warranty' => 'Warranty or repair',
            'return' => 'Return or refund',
            'business' => 'Corporate or bulk order',
            'other' => 'Something else',
        ][$topic] ?? 'Something else';
    }
}
