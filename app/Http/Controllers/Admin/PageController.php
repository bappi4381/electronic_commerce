<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.form', ['page' => new Page(['is_published' => false, 'sort_order' => Page::max('sort_order') + 1])]);
    }

    public function store(Request $request)
    {
        $page = Page::create($this->validated($request));

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page created.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $page->update($this->validated($request, $page));

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page saved.');
    }

    public function destroy(Page $page)
    {
        if ($page->isCore()) {
            return back()->with('error', "\"{$page->getTranslation('title', 'en')}\" is linked from the storefront footer and cannot be deleted. Unpublish it instead.");
        }

        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'slug'                => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'title_en'            => 'required|string|max:255',
            'title_bn'            => 'nullable|string|max:255',
            'content_en'          => 'nullable|string|max:100000',
            'content_bn'          => 'nullable|string|max:100000',
            'meta_description_en' => 'nullable|string|max:300',
            'meta_description_bn' => 'nullable|string|max:300',
            'sort_order'          => 'nullable|integer|min:0|max:10000',
            'is_published'        => 'nullable|boolean',
        ], ['slug.regex' => 'Use lowercase letters, numbers and single dashes (e.g. shipping-policy).']);

        // Core pages keep their slug so the footer links never break.
        $slug = $page?->isCore() ? $page->slug : $data['slug'];

        return [
            'slug'             => $slug,
            'title'            => ['en' => $data['title_en'], 'bn' => $data['title_bn'] ?? ''],
            'content'          => ['en' => clean((string) ($data['content_en'] ?? '')), 'bn' => clean((string) ($data['content_bn'] ?? ''))],
            'meta_description' => ['en' => $data['meta_description_en'] ?? '', 'bn' => $data['meta_description_bn'] ?? ''],
            'sort_order'       => $data['sort_order'] ?? 0,
            'is_published'     => $request->boolean('is_published'),
        ];
    }
}
