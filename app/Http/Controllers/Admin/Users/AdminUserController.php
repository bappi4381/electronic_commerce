<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    /**
     * Display a listing of administrators.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $admins = Admin::when($search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        })
        ->with('roles')
        ->orderBy('id', 'desc')
        ->paginate(10)
        ->appends(['search' => $search]);

        return view('admin.admins.index', compact('admins', 'search'));
    }

    /**
     * Show the form for creating a new administrator.
     */
    public function create()
    {
        $roles = Role::where('guard_name', 'admin')->get();
        return view('admin.admins.create', compact('roles'));
    }

    /**
     * Store a newly created administrator in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:6|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->has('roles')) {
            $roles = Role::whereIn('id', $request->roles)->get();
            $admin->syncRoles($roles);
        }

        return redirect()->route('admins.index')->with('success', 'Administrator created successfully!');
    }

    /**
     * Show the form for editing the specified administrator.
     */
    public function edit(Admin $admin)
    {
        $roles = Role::where('guard_name', 'admin')->get();
        $adminRoles = $admin->roles->pluck('id')->toArray();

        return view('admin.admins.edit', compact('admin', 'roles', 'adminRoles'));
    }

    /**
     * Update the specified administrator in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:admins,email,' . $admin->id,
            'password' => 'nullable|string|min:6|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        // Prevent modifying email of the core Super Admin
        if ($admin->email === 'admin@example.com' && $request->email !== 'admin@example.com') {
            return redirect()->back()->withErrors(['email' => 'The email for the default Super Admin cannot be changed.']);
        }

        $admin->name = $request->name;
        $admin->email = $request->email;

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();

        // Core Super Admin must retain the "Super Admin" role
        if ($admin->email === 'admin@example.com') {
            $superAdminRole = Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first();
            if ($superAdminRole) {
                // Ensure Super Admin ID is in the roles list or add it back
                $rolesInput = $request->roles ?? [];
                if (!in_array($superAdminRole->id, $rolesInput)) {
                    $rolesInput[] = $superAdminRole->id;
                    $request->merge(['roles' => $rolesInput]);
                }
            }
        }

        if ($request->has('roles')) {
            $roles = Role::whereIn('id', $request->roles)->get();
            $admin->syncRoles($roles);
        } else {
            $admin->syncRoles([]);
        }

        return redirect()->route('admins.index')->with('success', 'Administrator updated successfully!');
    }

    /**
     * Remove the specified administrator from storage.
     */
    public function destroy(Admin $admin)
    {
        if ($admin->id === Auth::guard('admin')->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own administrative account.');
        }

        if ($admin->email === 'admin@example.com') {
            return redirect()->back()->with('error', 'The default Super Admin account cannot be deleted.');
        }

        $admin->delete();

        return redirect()->route('admins.index')->with('success', 'Administrator deleted successfully!');
    }
}
