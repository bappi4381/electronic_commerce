<?php

use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\AdminSeeder::class);
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

test('unauthenticated users cannot access administrative user management', function () {
    $this->get(route('admins.index'))
        ->assertRedirect(route('admin.login'));
});

test('unauthorized admins cannot access administrative user management', function () {
    $admin = Admin::create([
        'name' => 'Regular Admin',
        'email' => 'regular@example.com',
        'password' => bcrypt('password'),
    ]);
    
    $this->actingAs($admin, 'admin')
        ->get(route('admins.index'))
        ->assertStatus(403);
});

test('super admin can access administrative user list', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $response = $this->actingAs($superAdmin, 'admin')
        ->get(route('admins.index'));

    $response->assertStatus(200)
        ->assertSee('ADMINISTRATIVE USERS')
        ->assertSee($superAdmin->name);
});

test('super admin can view the create administrator form', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $response = $this->actingAs($superAdmin, 'admin')
        ->get(route('admins.create'));

    $response->assertStatus(200)
        ->assertSee('ADD PORTAL ADMINISTRATOR');
});

test('super admin can create a new administrator and assign roles', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();
    $managerRole = Role::where('name', 'Manager')->where('guard_name', 'admin')->first();

    $response = $this->actingAs($superAdmin, 'admin')
        ->post(route('admins.store'), [
            'name' => 'Sub Admin',
            'email' => 'subadmin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$managerRole->id],
        ]);

    $response->assertRedirect(route('admins.index'));
    $this->assertDatabaseHas('admins', [
        'name' => 'Sub Admin',
        'email' => 'subadmin@example.com',
    ]);

    $newAdmin = Admin::where('email', 'subadmin@example.com')->first();
    expect($newAdmin->hasRole('Manager'))->toBeTrue();
});

test('email must be unique for administrators', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $response = $this->actingAs($superAdmin, 'admin')
        ->post(route('admins.store'), [
            'name' => 'Duplicate Email Admin',
            'email' => 'admin@example.com', // Already taken
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response->assertSessionHasErrors(['email']);
});

test('super admin can view the edit administrator form', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();
    $otherAdmin = Admin::create([
        'name' => 'Other Admin',
        'email' => 'other@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->actingAs($superAdmin, 'admin')
        ->get(route('admins.edit', $otherAdmin));

    $response->assertStatus(200)
        ->assertSee('EDIT PORTAL ADMINISTRATOR')
        ->assertSee('other@example.com');
});

test('super admin can update an administrator and sync roles', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();
    $otherAdmin = Admin::create([
        'name' => 'Other Admin',
        'email' => 'other@example.com',
        'password' => bcrypt('password'),
    ]);
    $managerRole = Role::where('name', 'Manager')->where('guard_name', 'admin')->first();

    $response = $this->actingAs($superAdmin, 'admin')
        ->put(route('admins.update', $otherAdmin), [
            'name' => 'Updated Admin Name',
            'email' => 'updated@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'roles' => [$managerRole->id],
        ]);

    $response->assertRedirect(route('admins.index'));
    $this->assertDatabaseHas('admins', [
        'id' => $otherAdmin->id,
        'name' => 'Updated Admin Name',
        'email' => 'updated@example.com',
    ]);

    $otherAdmin->refresh();
    expect(Hash::check('newpassword123', $otherAdmin->password))->toBeTrue();
    expect($otherAdmin->hasRole('Manager'))->toBeTrue();
});

test('core super admin email cannot be changed', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $response = $this->actingAs($superAdmin, 'admin')
        ->put(route('admins.update', $superAdmin), [
            'name' => 'Super Admin Updated Name',
            'email' => 'changed-email@example.com', // Attempting to change core email
        ]);

    $response->assertSessionHasErrors(['email']);
});

test('core super admin must always retain Super Admin role', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();
    $managerRole = Role::where('name', 'Manager')->where('guard_name', 'admin')->first();

    // Try to update core admin to only have the Manager role, removing Super Admin
    $response = $this->actingAs($superAdmin, 'admin')
        ->put(route('admins.update', $superAdmin), [
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'roles' => [$managerRole->id],
        ]);

    $response->assertRedirect(route('admins.index'));
    $superAdmin->refresh();
    
    // It should have both or at least still have Super Admin
    expect($superAdmin->hasRole('Super Admin'))->toBeTrue();
});

test('administrator cannot delete their own account', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $response = $this->actingAs($superAdmin, 'admin')
        ->delete(route('admins.destroy', $superAdmin));

    $response->assertRedirect()
        ->assertSessionHas('error', 'You cannot delete your own administrative account.');

    $this->assertDatabaseHas('admins', [
        'email' => 'admin@example.com',
    ]);
});

test('core super admin cannot be deleted by another admin', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();
    
    // Create another admin with manage-roles permission
    $adminWithRolesPermission = Admin::create([
        'name' => 'Role manager admin',
        'email' => 'rolemanager@example.com',
        'password' => bcrypt('password'),
    ]);
    $adminWithRolesPermission->givePermissionTo('manage-roles');

    $response = $this->actingAs($adminWithRolesPermission, 'admin')
        ->delete(route('admins.destroy', $superAdmin));

    $response->assertRedirect()
        ->assertSessionHas('error', 'The default Super Admin account cannot be deleted.');

    $this->assertDatabaseHas('admins', [
        'email' => 'admin@example.com',
    ]);
});
