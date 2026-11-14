<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;

use App\Models\BlogComment;
use App\Models\BlogReaction;
use Illuminate\Support\Facades\Auth;

class FrontendArticleController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category' => 'nullable|integer',
            'search' => 'nullable|string|max:100',
        ]);
        $search = trim((string) ($filters['search'] ?? ''));

        $published = fn () => Article::where('status', 'published')
            ->whereHas('category', fn ($q) => $q->where('type', 'blog'));

        // Topics that have at least one published story, with counts for the chips.
        $categories = Category::where('type', 'blog')
            ->withCount(['articles' => fn ($q) => $q->where('status', 'published')])
            ->get()
            ->filter(fn ($category) => $category->articles_count > 0)
            ->values();

        $articles = $published()
            ->with('category')
            ->withCount(['comments', 'reactions'])
            ->when($filters['category'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                // Titles and bodies are stored as JSON per language; match either language.
                $q->where(fn ($q) => $q->where('title->en', 'like', $like)->orWhere('title->bn', 'like', $like)->orWhere('content->en', 'like', $like));
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        // Big story on top of the unfiltered first page.
        $featured = !$request->hasAny(['category', 'search', 'page']) ? $articles->getCollection()->shift() : null;

        return view('frontend.pages.articles', [
            'articles' => $articles,
            'featured' => $featured,
            'categories' => $categories,
            'activeCategory' => $categories->firstWhere('id', (int) ($filters['category'] ?? 0)),
            'search' => $search,
            'totalPublished' => $published()->count(),
        ]);
    }

    public function show($slug)
    {
        $article = Article::where('slug', $slug)
            ->where('status', 'published')
            ->with(['category', 'comments.user', 'reactions'])
            ->firstOrFail();

        // Same topic first, then the newest stories.
        $otherArticles = Article::where('status', 'published')
            ->whereKeyNot($article->id)
            ->with('category')
            ->orderByRaw('category_id = ? desc', [$article->category_id])
            ->latest()
            ->take(3)
            ->get();

        return view('frontend.pages.singleArticle', compact('article', 'otherArticles'));
    }

    public function storeComment(Request $request, $id)
    {
        if (!Auth::check()) {
            return back()->with('error', 'You must be logged in to comment.');
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        BlogComment::create([
            'article_id' => $id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Your comment has been posted!');
    }

    public function toggleReaction(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $reaction = BlogReaction::where('article_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($reaction) {
            $reaction->delete();
            $liked = false;
        } else {
            BlogReaction::create([
                'article_id' => $id,
                'user_id' => Auth::id(),
                'type' => 'like',
            ]);
            $liked = true;
        }

        $count = BlogReaction::where('article_id', $id)->count();

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'count' => $count
        ]);
    }

    /**
     * Update an existing comment.
     */
    public function updateComment(Request $request, $id)
    {
        $comment = BlogComment::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $comment->update([
            'comment' => $request->comment
        ]);

        return back()->with('success', 'Comment updated successfully!');
    }

    /**
     * Delete an existing comment.
     */
    public function deleteComment($id)
    {
        \Log::info('Attempting to delete comment: ' . $id . ' by user: ' . Auth::id());
        
        $comment = BlogComment::withoutGlobalScopes()
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully!');
    }
}
