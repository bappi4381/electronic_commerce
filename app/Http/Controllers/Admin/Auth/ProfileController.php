<?php
namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // Show profile edit form
    public function index()
    {
        // Use admin guard
        $admin = Auth::guard('admin')->user();
        return view('admin.auth.profile', compact('admin'));
    }

    // Handle profile update form submission
    public function update(Request $request)
    {
        
        $admin = Auth::guard('admin')->user();
        //dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $admin->id,
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // max 2MB
            'password' => 'nullable|confirmed|min:8',
        ]);

        $admin->name = $request->name;
        $admin->email = $request->email;

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($admin->avatar) {
                Storage::disk('public')->delete($admin->avatar);
            }
            // Store new avatar and save the path
            $path = $request->file('avatar')->store('avatars', 'public');
            $admin->avatar = $path;
        }

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

}
