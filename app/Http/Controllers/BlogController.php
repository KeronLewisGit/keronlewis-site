<?php

namespace App\Http\Controllers;

use App\Support\Blog;
use App\Support\Portfolio;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Blog $blog, Portfolio $portfolio): View
    {
        return view('blog.index', [
            'profile' => $portfolio->profile(),
            'categories' => $blog->categories(),
            'count' => $blog->posts()->count(),
        ]);
    }

    public function show(Blog $blog, Portfolio $portfolio, string $slug): View
    {
        $post = $blog->find($slug);

        abort_if($post === null, 404);

        $shareFile = "img/share/blog-{$post['slug']}.png";
        $image = is_file(public_path($shareFile)) ? asset($shareFile) : asset('og-cover.png');

        return view('blog.show', [
            'profile' => $portfolio->profile(),
            'post' => $post,
            'related' => $blog->related($post),
            'schema' => $blog->schema($post, $image),
        ]);
    }

    public function feed(Blog $blog, Portfolio $portfolio): Response
    {
        return response()
            ->view('blog.feed', ['profile' => $portfolio->profile(), 'posts' => $blog->posts()->take(20), 'updated' => $blog->lastUpdated()])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
