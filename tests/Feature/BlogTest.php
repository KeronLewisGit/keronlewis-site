<?php

namespace Tests\Feature;

use App\Support\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_blog_is_linked_from_the_footer_but_not_the_main_navigation(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $nav = str($html)->between('<nav class="nav-links"', '</nav>');
        $footer = str($html)->between('<footer class="footer">', '</footer>');

        $this->assertStringNotContainsString(route('blog.index'), $nav);
        $this->assertStringContainsString('href="'.route('blog.index').'"', $footer);
    }

    public function test_every_post_has_its_own_page_listed_on_the_index_sitemap_and_feed(): void
    {
        $posts = app(Blog::class)->posts();
        $this->assertNotEmpty($posts);

        $index = $this->get('/blog')->assertOk();
        $sitemap = $this->get('/sitemap.xml')->assertOk()->assertSee(route('blog.index'));
        $feed = $this->get('/blog/rss.xml')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

        foreach ($posts as $post) {
            $this->get("/blog/{$post['slug']}")
                ->assertOk()
                ->assertSee('<title>'.e($post['seo_title']).'</title>', false)
                ->assertSee('<meta name="description" content="'.e($post['description']).'">', false)
                ->assertSee('<h1>'.e($post['title']).'</h1>', false)
                ->assertSee('<link rel="canonical" href="'.$post['url'].'">', false)
                ->assertSee('"@type":"BlogPosting"', false);

            $index->assertSee('href="'.$post['url'].'"', false);
            $sitemap->assertSee($post['url']);
            $feed->assertSee($post['url']);
        }
    }

    public function test_each_post_has_search_ready_metadata_and_a_faq(): void
    {
        foreach (app(Blog::class)->posts() as $post) {
            $full = app(Blog::class)->find($post['slug']);

            $this->assertLessThanOrEqual(65, mb_strlen($post['seo_title']), "{$post['slug']}: search title is too long");
            $this->assertLessThanOrEqual(160, mb_strlen($post['description']), "{$post['slug']}: description is too long");
            $this->assertGreaterThanOrEqual(3, count($full['toc']), "{$post['slug']}: too few sections");
            $this->assertNotEmpty($full['faq'], "{$post['slug']}: no FAQ section");
        }
    }

    public function test_links_between_pages_of_the_site_all_resolve(): void
    {
        $targets = app(Blog::class)->posts()
            ->flatMap(function (array $post) {
                preg_match_all('/href="(\/[^"#]*)/', app(Blog::class)->find($post['slug'])['html'], $links);

                return $links[1];
            })
            ->map(fn (string $path) => $path === '' ? '/' : $path)
            ->unique();

        foreach ($targets as $path) {
            $this->assertContains($this->get($path)->status(), [200], "Broken internal link: {$path}");
        }
    }

    public function test_a_post_dated_in_the_future_stays_hidden_until_its_day(): void
    {
        $post = app(Blog::class)->posts()->first();

        $this->travelTo($post['date']->copy()->subDay());

        $this->get("/blog/{$post['slug']}")->assertNotFound();
        $this->get('/blog')->assertOk()->assertDontSee('href="'.$post['url'].'"', false);
        $this->get('/sitemap.xml')->assertDontSee($post['url']);
    }

    public function test_an_unknown_post_is_not_found(): void
    {
        $this->get('/blog/no-such-post')->assertNotFound();
    }
}
