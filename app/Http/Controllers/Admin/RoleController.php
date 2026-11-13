<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Support\AdminPermissions;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $roles = Role::where('guard_name', AdminPermissions::GUARD)
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->withCount('permissions')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->appends(['search' => $search]);

        return view('admin.roles.index', compact('roles', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::where('guard_name', AdminPermissions::GUARD)->orderBy('name')->get();
        $groupedPermissions = AdminPermissions::grouped($permissions);

        return view('admin.roles.create', compact('permissions', 'groupedPermissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        $permissions = $this->grantablePermissions($request->permissions ?? []);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => AdminPermissions::GUARD,
        ]);

        $role->syncPermissions($permissions);
        $this->logPermissionSync($role, collect(), $permissions);

        return redirect()->route('roles.index')->with('success', 'Role created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::where('guard_name', AdminPermissions::GUARD)->orderBy('name')->get();
        $groupedPermissions = AdminPermissions::grouped($permissions);
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'groupedPermissions', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        if ($role->name === AdminPermissions::SUPER_ADMIN_ROLE) {
            return redirect()->back()->withErrors(['name' => 'The Super Admin role always has every permission and cannot be edited.']);
        }

        $before = $role->permissions()->pluck('name');
        $permissions = $this->grantablePermissions($request->permissions ?? [], $role);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($permissions);
        $this->logPermissionSync($role, $before, $permissions);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        if ($role->name === AdminPermissions::SUPER_ADMIN_ROLE) {
            return redirect()->back()->with('error', 'The Super Admin role cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete this role as it is currently assigned to one or more administrators.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully!');
    }

    /**
     * Administrators can only hand out permissions they hold themselves (Super Admins hold all),
     * which prevents escalating a role beyond the editor's own access.
     *
     * @param  array<int|string>  $ids
     * @return Collection<int, Permission>
     */
    private function grantablePermissions(array $ids, ?Role $role = null): Collection
    {
        $requested = Permission::where('guard_name', AdminPermissions::GUARD)->whereIn('id', $ids)->get();
        $editor = auth('admin')->user();

        if ($editor->hasRole(AdminPermissions::SUPER_ADMIN_ROLE)) {
            return $requested;
        }

        $alreadyOnRole = $role ? $role->permissions->pluck('id') : collect();
        $denied = $requested->reject(fn (Permission $p) => $editor->can($p->name) || $alreadyOnRole->contains($p->id));

        if ($denied->isNotEmpty()) {
            throw ValidationException::withMessages([
                'permissions' => 'You cannot grant permissions you do not have yourself: ' . $denied->pluck('name')->implode(', ') . '.',
            ]);
        }

        return $requested;
    }

    private function logPermissionSync(Role $role, Collection $before, Collection $after): void
    {
        $afterNames = $after->pluck('name');

        activity('roles')
            ->performedOn($role)
            ->withProperties([
                'granted' => $afterNames->diff($before)->values(),
                'revoked' => $before->diff($afterNames)->values(),
            ])
            ->event('permissions_synced')
            ->log("Permissions updated for role \"{$role->name}\"");
    }
}
