<?php

namespace Tests\Unit;

use App\Models\NewsArticle;
use PHPUnit\Framework\TestCase;

class NewsArticleVideoParsingTest extends TestCase
{
    /**
     * Test parsing YouTube URL.
     */
    public function test_it_parses_youtube_urls(): void
    {
        $article = new NewsArticle([
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
        ]);

        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $article->embed_video_url);

        $article->video_url = 'https://youtu.be/dQw4w9WgXcQ';
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $article->embed_video_url);

        $article->video_url = 'https://www.youtube.com/embed/dQw4w9WgXcQ';
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $article->embed_video_url);
    }

    /**
     * Test parsing Vimeo URL.
     */
    public function test_it_parses_vimeo_urls(): void
    {
        $article = new NewsArticle([
            'video_url' => 'https://vimeo.com/47123456'
        ]);

        $this->assertEquals('https://player.vimeo.com/video/47123456', $article->embed_video_url);
    }

    /**
     * Test empty/invalid URLs.
     */
    public function test_it_returns_null_for_invalid_urls(): void
    {
        $article = new NewsArticle([
            'video_url' => ''
        ]);

        $this->assertNull($article->embed_video_url);

        $article->video_url = 'https://google.com';
        $this->assertNull($article->embed_video_url);
    }
}
