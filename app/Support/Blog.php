<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use RuntimeException;

/**
 * The blog: one Markdown file per post in resources/content/blog, named after its slug,
 * opening with a block of "key: value" lines between two "---" lines.
 * A post dated in the future stays hidden until that day, so posts can be queued up.
 */
class Blog
{
    private ?Collection $all = null;

    public function __construct(private Portfolio $portfolio) {}

    public static function directory(): string
    {
        return resource_path('content/blog');
    }

    /** Published posts, newest first. */
    public function posts(): Collection
    {
        $today = now()->endOfDay();

        return $this->all()->filter(fn (array $post) => $post['date']->lte($today))->values();
    }

    /** One published post with its rendered body, table of contents and FAQ. */
    public function find(string $slug): ?array
    {
        $post = $this->posts()->firstWhere('slug', $slug);

        return $post ? $post + $this->render($post['markdown']) : null;
    }

    /** Category name => its published posts, in the order the categories first appear. */
    public function categories(): Collection
    {
        return $this->posts()->groupBy('category');
    }

    /** Other posts to read next: the same category first, then the newest of the rest. */
    public function related(array $post, int $count = 3): Collection
    {
        $others = $this->posts()->where('slug', '!==', $post['slug']);

        return $others->where('category', $post['category'])
            ->concat($others->where('category', '!==', $post['category']))
            ->take($count)
            ->values();
    }

    public function lastUpdated(): ?Carbon
    {
        return $this->posts()->map(fn (array $post) => $post['updated'] ?? $post['date'])->max();
    }

    /**
     * Structured data for one post: the article, where it sits in the site and,
     * when the post ends with a "Frequently asked questions" section, those questions.
     */
    public function schema(array $post, string $image): array
    {
        $profile = $this->portfolio->profile();
        $faq = collect($post['faq'])->map(fn (array $item) => [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
        ]);

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BlogPosting',
                    '@id' => $post['url'].'#article',
                    'headline' => $post['title'],
                    'description' => $post['description'],
                    'url' => $post['url'],
                    'mainEntityOfPage' => $post['url'],
                    'image' => $image,
                    'datePublished' => $post['date']->toIso8601String(),
                    'dateModified' => ($post['updated'] ?? $post['date'])->toIso8601String(),
                    'articleSection' => $post['category'],
                    'keywords' => $post['keyword'],
                    'wordCount' => $post['words'],
                    'inLanguage' => 'en',
                    'author' => [
                        '@type' => 'Person',
                        '@id' => url('/').'#person',
                        'name' => $profile['name'],
                        'jobTitle' => $profile['title'],
                        'url' => url('/'),
                    ],
                    'publisher' => ['@id' => url('/').'#person'],
                    'isPartOf' => ['@type' => 'Blog', '@id' => route('blog.index').'#blog', 'name' => "{$profile['name']}'s blog", 'url' => route('blog.index')],
                ],
                ...($faq->isEmpty() ? [] : [['@type' => 'FAQPage', 'mainEntity' => $faq->all()]]),
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $post['url']],
                    ],
                ],
            ],
        ];
    }

    /** Every post on disk, published or not, newest first (then by title). */
    private function all(): Collection
    {
        return $this->all ??= collect(File::glob(self::directory().'/*.md'))
            ->map(fn (string $path) => $this->read($path))
            ->sortBy([['date', 'desc'], ['title', 'asc']])
            ->values();
    }

    private function read(string $path): array
    {
        if (! preg_match('/\A---\R(.*?)\R---\R(.*)\z/s', File::get($path), $parts)) {
            throw new RuntimeException('Blog post '.basename($path).' has no front matter.');
        }

        $meta = [];
        foreach (preg_split('/\R/', $parts[1]) as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $meta[trim($key)] = trim($value);
            }
        }

        foreach (['title', 'description', 'category', 'date'] as $required) {
            if (blank($meta[$required] ?? null)) {
                throw new RuntimeException('Blog post '.basename($path)." is missing \"{$required}\".");
            }
        }

        $slug = basename($path, '.md');
        $markdown = trim($parts[2]);
        // Not strip_tags(): a "<?php" in a code sample would swallow the rest of the post.
        $words = str_word_count($markdown);

        return [
            'slug' => $slug,
            'url' => route('blog.show', $slug),
            'title' => $meta['title'],
            'seo_title' => $meta['seo_title'] ?? $meta['title'],
            'description' => $meta['description'],
            'category' => $meta['category'],
            'category_slug' => Str::slug($meta['category']),
            'keyword' => $meta['keyword'] ?? '',
            'date' => Carbon::parse($meta['date']),
            'updated' => filled($meta['updated'] ?? null) ? Carbon::parse($meta['updated']) : null,
            'words' => $words,
            // About 230 words a minute, rounded up.
            'minutes' => max(1, (int) ceil($words / 230)),
            'markdown' => $markdown,
        ];
    }

    /**
     * Turn the Markdown into HTML, give each section heading an id for the table of contents,
     * let wide tables scroll sideways on a phone, and pull out the closing FAQ for structured data.
     */
    private function render(string $markdown): array
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'external_link' => [
                'internal_hosts' => [parse_url(config('app.url'), PHP_URL_HOST)],
                'open_in_new_window' => true,
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new ExternalLinkExtension);

        $html = (string) (new MarkdownConverter($environment))->convert($markdown);

        $toc = [];
        $html = preg_replace_callback('#<h2>(.*?)</h2>#s', function (array $match) use (&$toc) {
            $text = html_entity_decode(strip_tags($match[1]), ENT_QUOTES);
            $id = Str::slug(preg_replace('/^\d+\.\s*/', '', $text)) ?: 'section';
            $id = in_array($id, array_column($toc, 'id'), true) ? $id.'-'.(count($toc) + 1) : $id;
            $toc[] = ['id' => $id, 'text' => $text];

            return "<h2 id=\"{$id}\">{$match[1]}</h2>";
        }, $html);

        $html = str_replace(['<table>', '</table>'], ['<div class="table-scroll"><table>', '</table></div>'], $html);

        return ['html' => $html, 'toc' => $toc, 'faq' => $this->faq($markdown)];
    }

    /** The questions and answers under a closing "## Frequently asked questions" heading. */
    private function faq(string $markdown): array
    {
        if (! preg_match('/^##\s+(?:Frequently asked questions|FAQs?)\s*$(.*)\z/ims', $markdown, $section)) {
            return [];
        }

        $faq = [];
        foreach (preg_split('/^###\s+/m', $section[1]) as $block) {
            [$question, $answer] = array_pad(explode("\n", trim($block), 2), 2, '');
            $answer = trim(html_entity_decode(strip_tags(Str::markdown($answer)), ENT_QUOTES));

            if ($question !== '' && $answer !== '') {
                $faq[] = ['question' => trim($question), 'answer' => preg_replace('/\s+/', ' ', $answer)];
            }
        }

        return $faq;
    }
}
