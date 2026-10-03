<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;

class SeoController extends Controller
{
    public function sitemap()
    {
        $lastmod = Carbon::createFromTimestamp(filemtime(config_path('portfolio.php')))->toDateString();

        return response()
            ->view('seo.sitemap', ['urls' => [route('home'), route('resume'), route('privacy')], 'lastmod' => $lastmod])
            ->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\n\nSitemap: ".route('sitemap')."\n", 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
