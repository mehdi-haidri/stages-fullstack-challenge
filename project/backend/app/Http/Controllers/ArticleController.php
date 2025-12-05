<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArticleController extends Controller
{
    /**
     * Display a listing of articles.
     */
    public function index(Request $request)
    {


        if (app()->environment('local')) {
            DB::listen(function ($query) {
                // 1. Log the query
                Log::info(
                    "Query executed: {$query->sql}",
                    [
                        'bindings' => $query->bindings,
                        'time_ms' => $query->time,
                        'connection' => $query->connectionName,
                    ]
                );

                // 2. Dump the query to the browser (only useful for development/debugging)
                // dump("SQL: " . $query->sql);
                // dump("Bindings: " . json_encode($query->bindings));
                // dump("Time: " . $query->time . "ms");
            });
        }

        //  [PERF-001] fix : Eager load the 'author' and 'comments' relationships
        $articles = Article::with(['author', 'comments'])->get();

        $articles = $articles->map(function ($article) use ($request) {
            if ($request->has('performance_test')) {
                usleep(30000); // 30ms par article pour simuler le coût du N+1
            }

            return [
                'id' => $article->id,
                'title' => $article->title,
                'content' => substr($article->content, 0, 200) . '...',
                'author' => $article->author->name,
                'comments_count' => $article->comments->count(),
                'published_at' => $article->published_at,
                'created_at' => $article->created_at,
            ];
        });

        return response()->json($articles);
    }

    /**
     * Display the specified article.
     */
    public function show($id)
    {
        $article = Article::with(['author', 'comments.user'])->findOrFail($id);

        return response()->json([
            'id' => $article->id,
            'title' => $article->title,
            'content' => $article->content,
            'author' => $article->author->name,
            'author_id' => $article->author->id,
            'image_path' => $article->image_path,
            'published_at' => $article->published_at,
            'created_at' => $article->created_at,
            'comments' => $article->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user' => $comment->user->name,
                    'created_at' => $comment->created_at,
                ];
            }),
        ]);
    }

    /**
     * Search articles.
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([]);
        }

        // [SEC-002] Security Fix: use elequant to handle querys insted of manually writing sql.
        $articles = Article::where('title', 'like', '%' . $query . '%')->get();

        // use the built-in function map in the returned  collection from elequant
        $results = $articles->map(function ($article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'content' => substr($article->content, 0, 200),
                'published_at' => $article->published_at,
            ];
        });

        return response()->json($results);
    }

    /**
     * Store a newly created article.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'author_id' => 'required|exists:users,id',
            'image_path' => 'nullable|string',
        ]);

        $article = Article::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'author_id' => $validated['author_id'],
            'image_path' => $validated['image_path'] ?? null,
            'published_at' => now(),
        ]);

        return response()->json($article, 201);
    }

    /**
     * Update the specified article.
     */
    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|max:255',
            'content' => 'sometimes|required',
        ]);

        $article->update($validated);

        return response()->json($article);
    }

    /**
     * Remove the specified article.
     */
    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        $article->delete();

        return response()->json(['message' => 'Article deleted successfully']);
    }
}

