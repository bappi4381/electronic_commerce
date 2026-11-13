<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public const TYPES = ['hero' => 'Hero slider', 'promo' => 'Promo strip', 'sub_banner' => 'Sub banner'];

    /** Banners are large hero images, so they get a higher limit than other uploads. */
    private const IMAGE_RULE = 'image|mimes:jpg,jpeg,png,webp|max:5120';

    public function index()
    {
        $banners = Banner::orderBy('type')->orderBy('order', 'asc')->get();
        $types = self::TYPES;

        return view('admin.banners.index', compact('banners', 'types'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, 'required|' . self::IMAGE_RULE);
        $data['status'] = true;
        $data['image'] = $request->file('image')->store('banners', 'public');

        Banner::create($data);

        return back()->with('success', 'Banner uploaded successfully.');
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $this->validated($request, 'nullable|' . self::IMAGE_RULE);

        if ($request->hasFile('image')) {
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);

        return back()->with('success', 'Banner updated successfully.');
    }

    public function destroy(Banner $banner)
    {
        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }
        $banner->delete();
        return back()->with('success', 'Banner deleted successfully.');
    }

    public function toggleStatus(Banner $banner)
    {
        $banner->update(['status' => !$banner->status]);
        return back()->with('success', 'Banner status updated.');
    }

    private function validated(Request $request, string $imageRule): array
    {
        $data = $request->validate([
            'title'    => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link'     => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/)/i'],
            'type'     => 'required|in:' . implode(',', array_keys(self::TYPES)),
            'order'    => 'required|integer|min:0|max:1000',
            'image'    => $imageRule,
        ], [
            'link.regex' => 'The link must be a full URL (https://…) or a site path starting with "/".',
            'image.max'  => 'Banner images may be at most 5 MB.',
        ]);

        unset($data['image']);

        return $data;
    }
}
