<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    /**
     * Display a listing of administrators.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $admins = Admin::when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->with('roles')
        ->orderBy('id', 'desc')
        ->paginate(10)
        ->appends(['search' => $search]);

        return view('admin.admins.index', [
            'admins' => $admins,
            'search' => $search,
            'superAdminCount' => $this->superAdminCount(),
            'actorIsSuperAdmin' => $this->actorIsSuperAdmin(),
        ]);
    }

    /**
     * Show the form for creating a new administrator.
     */
    public function create()
    {
        $roles = $this->assignableRoles();
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
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $roles = $this->rolesFromRequest($request);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $admin->syncRoles($roles);
        $this->logRoleChange($admin, collect(), $roles);

        return redirect()->route('admins.index')->with('success', 'Administrator created successfully!');
    }

    /**
     * Show the form for editing the specified administrator.
     */
    public function edit(Admin $admin)
    {
        $this->authorizeManage($admin);

        // Include roles the admin already holds so saving the form does not silently drop them.
        $roles = $this->assignableRoles()->merge($admin->roles)->unique('id')->sortBy('name')->values();
        $adminRoles = $admin->roles->pluck('id')->toArray();
        $isLastSuperAdmin = $this->isLastSuperAdmin($admin);

        return view('admin.admins.edit', compact('admin', 'roles', 'adminRoles', 'isLastSuperAdmin'));
    }

    /**
     * Update the specified administrator in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        $this->authorizeManage($admin);

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:admins,email,' . $admin->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $roles = $this->rolesFromRequest($request, $admin);
        $before = $admin->roles()->pluck('name');

        if ($this->isLastSuperAdmin($admin) && !$roles->contains('name', AdminPermissions::SUPER_ADMIN_ROLE)) {
            return redirect()->back()->withInput()->withErrors(['roles' => 'This is the last Super Admin; assign the role to someone else before removing it here.']);
        }

        $admin->name = $request->name;
        $admin->email = $request->email;

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
            $admin->setRememberToken(\Illuminate\Support\Str::random(60));
        }

        $admin->save();
        $admin->syncRoles($roles);
        $this->logRoleChange($admin, $before, $roles);

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

        $this->authorizeManage($admin);

        if ($this->isLastSuperAdmin($admin)) {
            return redirect()->back()->with('error', 'The last Super Admin account cannot be deleted.');
        }

        $admin->delete();

        return redirect()->route('admins.index')->with('success', 'Administrator deleted successfully!');
    }

    private function actorIsSuperAdmin(): bool
    {
        return (bool) Auth::guard('admin')->user()?->hasRole(AdminPermissions::SUPER_ADMIN_ROLE);
    }

    /**
     * Only Super Admins may modify Super Admin accounts.
     */
    private function authorizeManage(Admin $admin): void
    {
        abort_if($admin->hasRole(AdminPermissions::SUPER_ADMIN_ROLE) && !$this->actorIsSuperAdmin(), 403, 'Only a Super Admin can manage Super Admin accounts.');
    }

    private function superAdminCount(): int
    {
        return Admin::role(AdminPermissions::SUPER_ADMIN_ROLE, AdminPermissions::GUARD)->count();
    }

    private function isLastSuperAdmin(Admin $admin): bool
    {
        return $admin->hasRole(AdminPermissions::SUPER_ADMIN_ROLE) && $this->superAdminCount() <= 1;
    }

    /**
     * Roles the current administrator is allowed to hand out: Super Admins can assign any role;
     * others only roles whose permissions they hold themselves (never Super Admin).
     */
    private function assignableRoles(): Collection
    {
        $roles = Role::where('guard_name', AdminPermissions::GUARD)->with('permissions')->orderBy('name')->get();

        if ($this->actorIsSuperAdmin()) {
            return $roles;
        }

        $actor = Auth::guard('admin')->user();

        return $roles->filter(fn (Role $role) => $role->name !== AdminPermissions::SUPER_ADMIN_ROLE
            && $role->permissions->every(fn ($permission) => $actor->can($permission->name)))->values();
    }

    /**
     * Roles the target already has may be kept even if the actor could not grant them.
     */
    private function rolesFromRequest(Request $request, ?Admin $target = null): Collection
    {
        $requested = Role::where('guard_name', AdminPermissions::GUARD)->whereIn('id', $request->input('roles', []))->get();
        $allowed = $this->assignableRoles()->pluck('id')->merge($target?->roles->pluck('id') ?? []);
        $denied = $requested->reject(fn (Role $role) => $allowed->contains($role->id));

        if ($denied->isNotEmpty()) {
            throw ValidationException::withMessages([
                'roles' => 'You are not allowed to assign: ' . $denied->pluck('name')->implode(', ') . '.',
            ]);
        }

        return $requested;
    }

    private function logRoleChange(Admin $admin, Collection $before, Collection $after): void
    {
        $afterNames = $after->pluck('name');

        if ($afterNames->sort()->values()->all() === $before->sort()->values()->all()) {
            return;
        }

        activity('roles')
            ->performedOn($admin)
            ->withProperties(['before' => $before->values(), 'after' => $afterNames->values()])
            ->event('roles_synced')
            ->log("Roles updated for administrator {$admin->email}");
    }
}
