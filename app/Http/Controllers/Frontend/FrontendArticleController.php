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
        // Get only blog categories
        $categories = Category::where('type', 'blog')->get();

        // Article query
        $articles = Article::where('status', 'published')
            ->whereHas('category', function ($q) {
                $q->where('type', 'blog');
            });

        //Filter by category if selected
        if ($request->category) {
            $articles->where('category_id', $request->category);
        }

        // Filter by search title if requested
        if ($request->search) {
            $articles->where('title', 'like', '%'.$request->search.'%');
        }

        //Pagination
        $articles = $articles->latest()->paginate(9)->withQueryString();

        return view('frontend.pages.articles', compact('articles', 'categories'));
    }
    public function show($slug)
    {
        // Find the article by slug and ensure it's published
        $article = Article::where('slug', $slug)
                        ->where('status', 'published')
                        ->with(['category', 'comments.user', 'reactions'])
                        ->firstOrFail();

        // Optional: get other articles for sidebar (exclude current article)
        $otherArticles = Article::where('status', 'published')
                                ->where('id', '!=', $article->id)
                                ->latest()
                                ->take(5)
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
