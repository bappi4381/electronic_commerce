<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 


use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    use AuthorizesRequests;
    /** Order list tabs: query value => statuses it covers (null = every order). */
    private const ORDER_TABS = [
        'all' => null,
        'active' => [OrderStatus::Pending, OrderStatus::OnHold, OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped],
        'delivered' => [OrderStatus::Delivered],
        'closed' => [OrderStatus::Cancelled, OrderStatus::Returned, OrderStatus::Refunded],
    ];

    private const ORDER_CARD_RELATIONS = ['orderItems.product.images'];

    public function userDashboard() {
        $user = Auth::user();
        $active = array_map(fn (OrderStatus $s) => $s->value, self::ORDER_TABS['active']);

        $stats = [
            'total_orders' => $user->orders()->count(),
            'active_orders' => $user->orders()->whereIn('status', $active)->count(),
            'delivered_orders' => $user->orders()->where('status', OrderStatus::Delivered->value)->count(),
            'wishlist_count' => $user->wishlist()->count(),
        ];
        $recentOrders = $user->orders()->with(self::ORDER_CARD_RELATIONS)->latest()->take(3)->get();
        $activeOrder = $user->orders()->whereIn('status', $active)->with(['statusHistories', 'orderItems'])->latest()->first();

        return view('user.dashboard.index', compact('user', 'stats', 'recentOrders', 'activeOrder'));
    }

    public function profileIndex() {
        $user = Auth::user();
        return view('user.dashboard.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required','email','max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'password' => ['nullable', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()],
            'current_password' => 'nullable|string',
        ]);

        $emailChanged = strcasecmp($validated['email'], (string) $user->email) !== 0;
        $passwordChanged = filled($validated['password'] ?? null);

        // A borrowed or stolen session must not be enough to take the account over.
        if (($emailChanged || $passwordChanged) && $user->hasPassword()
            && !Hash::check((string) $request->input('current_password'), $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'current_password' => __('Enter your current password to change your email or password.'),
            ]);
        }

        // Update user fields
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? $user->phone;
        $user->address = $validated['address'] ?? $user->address;
        $user->city = $validated['city'] ?? $user->city;
        $user->state = $validated['state'] ?? $user->state;
        $user->postal_code = $validated['postal_code'] ?? $user->postal_code;
        $user->country = $validated['country'] ?? $user->country;
        // Verification is never taken from the form; a new address has to be verified again.
        if ($emailChanged) {
            $user->is_email_verified = false;
        }

        // Update avatar if uploaded
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        if ($passwordChanged) {
            $user->password = Hash::make($validated['password']);
            $user->setRememberToken(\Illuminate\Support\Str::random(60));
        }

        $user->save();

        if ($passwordChanged) {
            // Other devices signed in with the old password are signed out; this one stays.
            $user->endAllSessions($request->session()->getId());
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
        }

        return redirect()->route('user.profile')->with('success', $passwordChanged ? __('Profile and password updated. Other devices have been signed out.') : __('Profile updated successfully.'));
    }

    public function userOrders(Request $request) {
        $user = Auth::user();
        $tab = array_key_exists($request->query('status'), self::ORDER_TABS) ? $request->query('status') : 'all';
        $statusesOf = fn (string $key) => self::ORDER_TABS[$key] ? array_map(fn (OrderStatus $s) => $s->value, self::ORDER_TABS[$key]) : null;

        $orders = $user->orders()
            ->when($statusesOf($tab), fn ($q, $statuses) => $q->whereIn('status', $statuses))
            ->with(self::ORDER_CARD_RELATIONS)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $counts = collect(self::ORDER_TABS)->keys()->mapWithKeys(fn ($key) => [
            $key => $user->orders()->when($statusesOf($key), fn ($q, $statuses) => $q->whereIn('status', $statuses))->count(),
        ]);

        return view('user.orders.index', compact('orders', 'tab', 'counts'));
    }

    public function userOrderDetails(Order $order) {
        $this->authorize('view', $order); // ensure user owns order
        $order->load(['orderItems.product.images', 'orderItems.variant.attributeValues.attribute', 'statusHistories', 'orderCourier', 'courierLogs' => fn ($q) => $q->customerVisible()]);

        $checkout = app(\App\Services\CheckoutOptions::class);
        $deliveryEta = $checkout->deliveryOptions()[$order->city]['eta'] ?? $checkout->defaultEta();

        return view('user.orders.details', compact('order', 'deliveryEta'));
    }

    public function cancelOrder(Order $order, OrderStatusService $orderStatus)
    {
        $this->authorize('update', $order); // make sure policy exists

        if ($order->status === OrderStatus::Cancelled->value) {
            return redirect()->back()->with('info', 'Order is already cancelled.');
        }

        // Paid, shipped or fulfilled orders need a refund/return handled by support.
        if (!$order->statusEnum()?->holdsStock() || $order->paymentStatusEnum()?->isCollected()) {
            return redirect()->back()->with('error', 'This order can no longer be cancelled online. Please contact support.');
        }

        $orderStatus->transition($order, OrderStatus::Cancelled, null, 'Cancelled by customer');

        return redirect()->back()->with('success', 'Order cancelled successfully!');
    }

    public function trackOrderForm() {
        return view('user.orders.track', ['activeOrders' => $this->activeOrdersForTracking()]);
    }

    public function trackOrder(Request $request) {
        $request->validate(['order_id' => 'required|string']);
        $order = Auth::user()->orders()->with(['statusHistories', 'orderCourier', 'courierLogs' => fn ($q) => $q->customerVisible()])->where('order_id', trim($request->order_id))->first();
        return $order
            ? view('user.orders.track', ['order' => $order, 'activeOrders' => $this->activeOrdersForTracking()])
            : redirect()->back()->withInput()->with('error', 'Order not found!');
    }

    private function activeOrdersForTracking()
    {
        return Auth::user()->orders()
            ->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, self::ORDER_TABS['active']))
            ->with('orderItems')
            ->latest()
            ->take(5)
            ->get();
    }

    public function userMessages() {
        return view('user.messages.index');
    }

    public function fetchMessages() {
        $user = Auth::user();
        $messages = \App\Models\Message::where('user_id', $user->id)->orderBy('created_at', 'asc')->get();
        return response()->json($messages);
    }

    public function sendMessage(Request $request) {
        $request->validate(['message' => 'required|string']);
        $user = Auth::user();

        $hadUnread = \App\Models\Message::where('user_id', $user->id)->where('is_admin', false)->where('is_read', false)->exists();

        // is_read on a customer message tracks whether an admin has read it.
        $message = \App\Models\Message::create([
            'user_id' => $user->id,
            'is_admin' => false,
            'message' => $request->message,
        ]);

        try {
            broadcast(new \App\Events\MessageSent($message));
        } catch (\Exception $e) {
            // Pusher not configured; message still saved to DB
        }

        if (!$hadUnread) {
            app(\App\Services\AdminNotifier::class)->newChatMessage($message);
        }

        return response()->json(['status' => 'Message Sent!', 'message' => $message]);
    }

    public function markAsRead() {
        $user = Auth::user();
        \App\Models\Message::where('user_id', $user->id)
            ->where('is_admin', true)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json(['status' => 'Success']);
    }
}
