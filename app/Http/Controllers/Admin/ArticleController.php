<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    
    /**
     * Display all articles.
     */
    public function index( Request $request)
    {
        $query = Article::with('category'); // eager load category

        if ($search = $request->search) {
            $query->where('title', 'like', "%{$search}%")
                ->orWhereHas('category', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
        }

        $articles = $query->orderBy('id', 'desc')->paginate(10); // ✅ paginate instead of get()
        
        return view('admin.article.index', compact('articles'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $categories = Category::where('type', 'blog')->get();
        return view('admin.article.create', compact('categories'));
    }

    /**
     * Store new article.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => 'required|string|unique:articles,slug',
            'content'      => 'required|string',
            'category_id'  => 'required|exists:categories,id',
            'published_at' => 'nullable|date',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status'       => 'nullable|in:draft,published',
        ]);

        // Image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('articles', 'public');
        }
        try {
            Article::create($validated);
            return redirect()->route('articles.index')->with('success', 'Article created successfully!');
        } catch (\Exception $e) {
            dd($e->getMessage(), $validated);
        }
    }

    /**
     * Show single article.
     */
    public function show(Article $article)
    {
        return view('admin.article.show', compact('article'));
    }

    /**
     * Edit article.
     */
    public function edit(Article $article)
    {
        $categories = Category::where('type', 'blog')->get();
        return view('admin.article.edit', compact('article', 'categories'));
    }

    /**
     * Update article.
     */
    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => 'required|string|unique:articles,slug,' . $article->id,
            'content'      => 'required|string',
            'category_id'  => 'required|exists:categories,id',
            'published_at' => 'nullable|date',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status'       => 'nullable|in:draft,published',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('articles', 'public');
        }
        // dd($validated);
        $article->update($validated);

        return redirect()->route('articles.index')->with('success', 'Article updated successfully!');
    }

    /**
     * Delete article.
     */
    public function destroy(Article $article)
    {
        if ($article->image && file_exists(public_path('storage/' . $article->image))) {
            unlink(public_path('storage/' . $article->image));
        }

        $article->delete();

        return redirect()->route('articles.index')->with('success', '🗑️ Article deleted successfully!');
    }

    /**
     * Delete a blog comment.
     */
    public function destroyComment($id)
    {
        $comment = \App\Models\BlogComment::withoutGlobalScope('active_user')->findOrFail($id);
        $comment->delete();

        return back()->with('success', 'Comment deleted successfully!');
    }
}
