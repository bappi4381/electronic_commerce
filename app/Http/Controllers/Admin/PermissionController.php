<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $permissions = Permission::where('guard_name', 'admin')
            ->when($search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->withCount('roles')
            ->orderBy('name')
            ->paginate(15)
            ->appends(['search' => $search]);

        return view('admin.permissions.index', compact('permissions', 'search'));
    }

    /**
     * Show the form for creating a new permission.
     */
    public function create()
    {
        return view('admin.permissions.create');
    }

    /**
     * Store a newly created permission.
     */
    public function store(StorePermissionRequest $request)
    {
        Permission::create([
            'name'       => $request->name,
            'guard_name' => 'admin',
        ]);

        return redirect()->route('permissions.index')
            ->with('success', 'Permission "' . $request->name . '" created successfully!');
    }

    /**
     * Show the form for editing the specified permission.
     */
    public function edit(Permission $permission)
    {
        // Fetch all admin roles with pre-check for which ones have this permission
        $roles = Role::where('guard_name', 'admin')
            ->with('permissions')
            ->get()
            ->map(function ($role) use ($permission) {
                $role->has_permission = $role->hasPermissionTo($permission);
                return $role;
            });

        return view('admin.permissions.edit', compact('permission', 'roles'));
    }

    /**
     * Update the specified permission.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        // Prevent renaming core seeded permissions
        $corePermissions = ['manage-products', 'manage-orders', 'manage-users', 'manage-roles'];
        if (in_array($permission->name, $corePermissions) && $request->name !== $permission->name) {
            return redirect()->back()->withErrors([
                'name' => 'The permission "' . $permission->name . '" is a core system permission and cannot be renamed.',
            ]);
        }

        $permission->update(['name' => $request->name]);

        return redirect()->route('permissions.index')
            ->with('success', 'Permission updated successfully!');
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission)
    {
        $corePermissions = ['manage-products', 'manage-orders', 'manage-users', 'manage-roles'];
        if (in_array($permission->name, $corePermissions)) {
            return redirect()->back()
                ->with('error', 'Core system permission "' . $permission->name . '" cannot be deleted.');
        }

        if ($permission->roles()->count() > 0) {
            return redirect()->back()
                ->with('error', 'This permission is assigned to ' . $permission->roles()->count() . ' role(s). Remove it from all roles first.');
        }

        $permission->delete();

        return redirect()->route('permissions.index')
            ->with('success', 'Permission deleted successfully!');
    }
}
