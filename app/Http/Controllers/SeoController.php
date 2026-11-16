<?php

namespace App\Http\Controllers;

use App\Support\Blog;
use App\Support\BookingCalendar;
use App\Support\Portfolio;
use Illuminate\Support\Carbon;

class SeoController extends Controller
{
    public function sitemap(Portfolio $portfolio, BookingCalendar $calendar, Blog $blog)
    {
        $lastmod = Carbon::createFromTimestamp(filemtime(config_path('portfolio.php')))->toDateString();
        $urls = [
            route('home'),
            ...$portfolio->services()->pluck('url'),
            ...$portfolio->caseStudies()->pluck('case_url'),
            ...($calendar->isEnabled() ? [route('booking.show')] : []),
            route('resume'),
            route('privacy'),
        ];
        $posts = $blog->posts();

        // Posts carry their own dates; the blog index changes whenever a post does.
        $dated = $posts->isEmpty() ? [] : [
            route('blog.index') => $blog->lastUpdated()->toDateString(),
            ...$posts->mapWithKeys(fn (array $post) => [$post['url'] => ($post['updated'] ?? $post['date'])->toDateString()])->all(),
        ];

        return response()
            ->view('seo.sitemap', ['urls' => $urls, 'lastmod' => $lastmod, 'dated' => $dated])
            ->header('Content-Type', 'application/xml');
    }

    /** Plain-text facts for AI assistants and answer engines (llmstxt.org). */
    public function llms(Portfolio $portfolio, BookingCalendar $calendar, Blog $blog)
    {
        return response()
            ->view('seo.llms', [
                'profile' => $portfolio->profile(),
                'services' => $portfolio->services(),
                'projects' => $portfolio->projects(),
                'experience' => $portfolio->experience(),
                'skills' => $portfolio->skills(),
                'bookingOpen' => $calendar->isEnabled(),
                'posts' => $blog->posts(),
            ])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function robots()
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /testimonial\n\nSitemap: ".route('sitemap')."\n", 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
