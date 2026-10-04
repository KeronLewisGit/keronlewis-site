<?php

namespace App\Http\Controllers;

use App\Support\BookingCalendar;
use App\Support\Portfolio;
use Illuminate\Support\Carbon;

class SeoController extends Controller
{
    public function sitemap(Portfolio $portfolio, BookingCalendar $calendar)
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

        return response()
            ->view('seo.sitemap', ['urls' => $urls, 'lastmod' => $lastmod])
            ->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /testimonial\n\nSitemap: ".route('sitemap')."\n", 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
