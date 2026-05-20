<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_address' => 'nullable|string',
            'facebook_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'footer_text' => 'nullable|string',
            'shipping_charge_dhaka' => 'nullable|numeric|min:0',
            'shipping_charge_outside' => 'nullable|numeric|min:0',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            $oldLogo = Setting::get('logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $data['logo'] = $request->file('logo')->store('settings', 'public');
        }

        foreach ($data as $key => $value) {
            if ($value !== null) {
                Setting::set($key, $value);
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }
}
