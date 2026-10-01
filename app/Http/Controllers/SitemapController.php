<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Models\TutorProfile;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $baseUrl = 'https://edusfera.by';

        $staticPages = [
            ['url' => '/', 'changefreq' => 'daily', 'priority' => '1.0', 'lastmod' => now()->toAtomString()],
            ['url' => '/tutors', 'changefreq' => 'daily', 'priority' => '0.9', 'lastmod' => now()->toAtomString()],
            ['url' => '/for-tutors', 'changefreq' => 'weekly', 'priority' => '0.8', 'lastmod' => now()->subDays(2)->toAtomString()],
            ['url' => '/diagnostic', 'changefreq' => 'weekly', 'priority' => '0.8', 'lastmod' => now()->subDay()->toAtomString()],
            ['url' => '/news', 'changefreq' => 'daily', 'priority' => '0.7', 'lastmod' => now()->toAtomString()],
            ['url' => '/about', 'changefreq' => 'monthly', 'priority' => '0.8', 'lastmod' => now()->toAtomString()],
            ['url' => '/offer', 'changefreq' => 'monthly', 'priority' => '0.5', 'lastmod' => Carbon::parse('2026-08-03')->toAtomString()],
            ['url' => '/privacy-policy', 'changefreq' => 'monthly', 'priority' => '0.5', 'lastmod' => Carbon::parse('2026-09-02')->toAtomString()],
            ['url' => '/payment-security', 'changefreq' => 'monthly', 'priority' => '0.5', 'lastmod' => Carbon::parse('2026-09-06')->toAtomString()],
            ['url' => '/refund-policy', 'changefreq' => 'monthly', 'priority' => '0.5', 'lastmod' => Carbon::parse('2026-09-06')->toAtomString()],
            ['url' => '/contacts', 'changefreq' => 'monthly', 'priority' => '0.6', 'lastmod' => Carbon::parse('2026-09-13')->toAtomString()],
        ];

        // Tutors dynamic URLs
        $tutors = TutorProfile::query()
            ->where('verification_status', 'approved')
            ->with('user')
            ->get();

        $tutorPages = [];
        foreach ($tutors as $tutor) {
            $lastmod = ($tutor->updated_at ?? now())->toAtomString();
            $tutorPages[] = [
                'url' => '/tutors/'.$tutor->id,
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'lastmod' => $lastmod,
            ];
        }

        // News articles dynamic URLs
        $articles = NewsArticle::query()
            ->where('status', 'published')
            ->get();

        $articlePages = [];
        foreach ($articles as $article) {
            $lastmod = ($article->updated_at ?? now())->toAtomString();
            $slugOrId = $article->slug ?? (string) $article->id;
            $articlePages[] = [
                'url' => '/news/'.$slugOrId,
                'changefreq' => 'monthly',
                'priority' => '0.7',
                'lastmod' => $lastmod,
            ];
        }

        $allUrls = array_merge($staticPages, $tutorPages, $articlePages);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($allUrls as $item) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($baseUrl.$item['url'])."</loc>\n";
            $xml .= '    <lastmod>'.$item['lastmod']."</lastmod>\n";
            $xml .= '    <changefreq>'.$item['changefreq']."</changefreq>\n";
            $xml .= '    <priority>'.$item['priority']."</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
