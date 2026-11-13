<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // Display list of users
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::when($search, function ($query, $search) {
            $query->where('user_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        })
        ->orderBy('id', 'desc')
        ->paginate(10)
        ->appends(['search' => $search]);

        return view('admin.users.index', compact('users', 'search'));
    }

    // Show create user form
    public function create()
    {
        return view('admin.users.create');
    }

    // Store new user
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }
    public function show(User $user)
    {
        // Load user orders
        $orders = $user->orders()->latest()->paginate(10);

        return view('admin.users.show', compact('user', 'orders'));
    }
    // Show edit form
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    // Update user
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->fill($request->only([
            'name', 'email', 'phone', 'address', 'city', 'state', 'postal_code', 'country'
        ]));

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
            $user->remember_token = Str::random(60);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    // List a customer's orders
    public function orders(User $user)
    {
        $orders = $user->orders()->withCount('orderItems')->latest()->paginate(15);

        return view('admin.users.orders', compact('user', 'orders'));
    }

    // Delete user (only customers without order history; block the account otherwise)
    public function destroy(User $user)
    {
        $orderCount = $user->orders()->count();

        if ($orderCount > 0) {
            return redirect()->back()->with('error', "{$user->name} has {$orderCount} order(s) and cannot be deleted. Block the account instead.");
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    // Toggle user status (Block/Unblock)
    public function toggleStatus(User $user)
    {
        $user->status = ($user->status == 'active') ? 'blocked' : 'active';
        $user->save();

        $message = $user->status == 'active' ? 'User unblocked successfully.' : 'User blocked successfully.';
        return redirect()->back()->with('success', $message);
    }
}
