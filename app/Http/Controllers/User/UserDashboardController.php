<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 


use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    use AuthorizesRequests;
    public function userDashboard() {
        $user = Auth::user();
        $stats = [
            'total_orders' => $user->orders()->count(),
            'pending_orders' => $user->orders()->where('status', 'pending')->count(),
            'delivered_orders' => $user->orders()->where('status', 'delivered')->count(),
            'wishlist_count' => $user->wishlist()->count(),
        ];
        $recentOrders = $user->orders()->latest()->take(5)->get();
        return view('user.dashboard.index', compact('stats', 'recentOrders'));
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
            'is_email_verified' => 'sometimes|boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'nullable|string|min:6|confirmed',
            
        ]);

        // Update user fields
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? $user->phone;
        $user->address = $validated['address'] ?? $user->address;
        $user->city = $validated['city'] ?? $user->city;
        $user->state = $validated['state'] ?? $user->state;
        $user->postal_code = $validated['postal_code'] ?? $user->postal_code;
        $user->country = $validated['country'] ?? $user->country;
        $user->is_email_verified = $request->has('is_email_verified');

        // Update avatar if uploaded
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && file_exists(storage_path('app/public/' . $user->avatar))) {
                unlink(storage_path('app/public/' . $user->avatar));
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('user.profile')->with('success', 'Profile updated successfully.');
    }

    public function userOrders() {
       $user = Auth::user();

        // Fetch orders and make sure it returns a Collection
        $orders = $user->orders()->latest()->paginate(10); // or ->get() for non-paginated

        return view('user.orders.index', compact('orders'));
    }

    public function userOrderDetails(Order $order) {
        $this->authorize('view', $order); // ensure user owns order
        return view('user.orders.details', compact('order'));
    }

    public function cancelOrder(Order $order)
    {
        $this->authorize('update', $order); // make sure policy exists

        if ($order->status === 'cancelled') {
            return redirect()->back()->with('info', 'Order is already cancelled.');
        }

        $order->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Order cancelled successfully!');
    }

    public function trackOrderForm() {
        return view('user.orders.track');
    }

    public function trackOrder(Request $request) {
        $request->validate(['order_id' => 'required|string']);
        $order = Order::where('order_id', $request->order_id)->first();
        return $order 
            ? view('user.orders.track', compact('order'))
            : redirect()->back()->with('error', 'Order not found!');
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

        $message = \App\Models\Message::create([
            'user_id' => $user->id,
            'is_admin' => false,
            'message' => $request->message,
            'is_read' => true, // Sender's own message is read
        ]);

        try {
            broadcast(new \App\Events\MessageSent($message));
        } catch (\Exception $e) {
            // Pusher not configured; message still saved to DB
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
