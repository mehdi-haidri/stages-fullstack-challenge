<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Display global statistics, utilizing Redis caching (PERF-003).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {

        $cacheKey = 'global_stats';
        $cacheDuration = 300; // 5 minutes

        $stats = Cache::remember($cacheKey, $cacheDuration, function () {


            $totalArticles = Article::count();
            $totalComments = Comment::count();
            $totalUsers = User::count();


            $mostCommented = Article::select('articles.id', 'articles.title', DB::raw('COUNT(comments.id) as comments_count'))
                ->leftJoin('comments', 'articles.id', '=', 'comments.article_id')
                ->groupBy('articles.id', 'articles.title') // Group by required columns only
                ->orderBy('comments_count', 'desc')
                ->limit(5)
                ->get();



            $recentArticles = Article::with('author')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();


            return [
                'total_articles' => $totalArticles,
                'total_comments' => $totalComments,
                'total_users' => $totalUsers,
                'most_commented' => $mostCommented->map(function ($article) {
                    return [
                        'id' => $article->id,
                        'title' => $article->title,
                        'comments_count' => $article->comments_count,
                    ];
                }),
                'recent_articles' => $recentArticles->map(function ($article) {
                    return [
                        'id' => $article->id,
                        'title' => $article->title,
                        'author' => $article->author->name,
                        'created_at' => $article->created_at,
                    ];
                }),
            ];
        });

        return response()->json($stats);
    }
}

