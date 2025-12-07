<?php

namespace App\Providers;

use App\Models\Article;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Article::saved(function (Article $article) {
            Cache::forget('articles_list');
            Cache::forget('global_stats');
        });

        Article::deleted(function (Article $article) {
            Cache::forget('articles_list');
            Cache::forget('global_stats');
        });
    }
}
