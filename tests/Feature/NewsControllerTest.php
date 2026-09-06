<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!file_exists(database_path('news.sqlite'))) {
            touch(database_path('news.sqlite'));
        }

        $this->artisan('migrate', [
            '--database' => 'news',
            '--path' => 'database/migrations/news',
            '--force' => true,
        ]);

        // Clean database before test
        NewsArticle::query()->truncate();
    }

    public function test_can_view_news_catalog(): void
    {
        NewsArticle::query()->create([
            'title' => 'Test News 1',
            'slug' => 'test-news-1',
            'content' => 'Content of test news 1',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        NewsArticle::query()->create([
            'title' => 'Test News 2',
            'slug' => 'test-news-2',
            'content' => 'Content of test news 2',
            'status' => 'draft',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('news.index'));

        $response->assertOk();
        $response->assertSee('Test News 1');
        $response->assertDontSee('Test News 2');
    }

    public function test_can_view_published_news_article(): void
    {
        $article = NewsArticle::query()->create([
            'title' => 'Single News Title',
            'slug' => 'single-news-slug',
            'content' => '<p>Beautiful paragraph</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
        ]);

        $response = $this->get(route('news.show', $article->slug));

        $response->assertOk();
        $response->assertSee('Single News Title');
        $response->assertSee('Beautiful paragraph');
        $response->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ');
    }

    public function test_cannot_view_draft_news_article(): void
    {
        $article = NewsArticle::query()->create([
            'title' => 'Draft News Title',
            'slug' => 'draft-news-slug',
            'content' => '<p>Beautiful draft paragraph</p>',
            'status' => 'draft',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('news.show', $article->slug));
        $response->assertStatus(404);
    }

    public function test_cannot_view_future_published_news_article(): void
    {
        $article = NewsArticle::query()->create([
            'title' => 'Future News Title',
            'slug' => 'future-news-slug',
            'content' => '<p>Future paragraph</p>',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $response = $this->get(route('news.show', $article->slug));
        $response->assertStatus(404);
    }
}
