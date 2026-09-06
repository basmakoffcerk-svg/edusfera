<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Display a listing of the news articles.
     */
    public function index(Request $request)
    {
        $articles = NewsArticle::query()
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate(9);

        return view('news.index', compact('articles'));
    }

    /**
     * Display the specified news article.
     */
    public function show(string $slug)
    {
        $article = NewsArticle::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        // Fetch recent news excluding the current one
        $recentArticles = NewsArticle::query()
            ->published()
            ->where('id', '!=', $article->id)
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();

        return view('news.show', compact('article', 'recentArticles'));
    }
}
