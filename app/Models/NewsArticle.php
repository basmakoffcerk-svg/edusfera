<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsArticle extends Model
{
    use HasFactory;

    protected $connection = 'news';
    protected $table = 'news_articles';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'featured_image',
        'video_url',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (NewsArticle $article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    /**
     * Scope a query to only include published news articles.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Get embedded URL for YouTube or Vimeo video.
     */
    public function getEmbedVideoUrlAttribute(): ?string
    {
        if (!$this->video_url) {
            return null;
        }

        // YouTube
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $this->video_url, $match)) {
            return "https://www.youtube.com/embed/{$match[1]}";
        }

        // Vimeo
        if (preg_match('%vimeo\.com/(?:channels/(?:\w+/)?|groups/([^/]*)/videos/|album/(\d+)/video/|video/|)(\d+)(?:$|/|\?)%i', $this->video_url, $match)) {
            return "https://player.vimeo.com/video/{$match[3]}";
        }

        return null;
    }
}
