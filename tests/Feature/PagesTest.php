<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use App\Support\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_profile_projects_and_experience(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Keron Lewis')
            ->assertSee('Selected work')
            ->assertSee('Web development services')
            ->assertSee('<title>'.e(config('portfolio.profile.seo.home_title')).'</title>', false)
            ->assertSee('<a href="mailto:'.config('portfolio.profile.email').'">', false)
            ->assertSeeInOrder(['<!--email_off-->', 'mailto:', '<!--/email_off-->'], false)
            ->assertSee('"@type":"ProfilePage"', false)
            ->assertSee('"@type":"Occupation"', false);

        foreach (config('portfolio.projects') as $project) {
            $response->assertSee($project['name']);
        }
        foreach (app(Portfolio::class)->experience() as $role) {
            $response->assertSee($role['org']);
        }
    }

    public function test_resume_page_lists_every_role_with_its_bullets(): void
    {
        $response = $this->get('/resume');

        $response->assertOk()->assertSee('Timeline')->assertSee('Jan 2024 – Present · Part-time');

        foreach (app(Portfolio::class)->experience() as $role) {
            $response->assertSee($role['role'])->assertSee($role['bullets'][0]);
        }
    }

    public function test_a_scheduled_role_end_takes_effect_on_its_date(): void
    {
        $this->travelTo('2026-11-15 12:00:00');
        $this->get('/resume')
            ->assertSee('Apr 2025 – Present · Full-time')
            ->assertSee('Design and deliver internal web applications');
        $this->assertNull(app(Portfolio::class)->experience()->firstWhere('id', 'label-house')['end']);

        $this->travelTo('2026-11-16 12:00:00');
        $this->get('/resume')
            ->assertSee('Apr 2025 – Nov 2026 · Full-time')
            ->assertSee('Designed and delivered internal web applications')
            ->assertDontSee('Apr 2025 – Present');
        $this->get('/')->assertDontSee('Full-time · Current');
        $this->getJson('/resume.json')->assertJsonPath('work.0.endDate', '2026-11');

        // The timeline bar stops at today rather than running past it.
        $chart = app(Portfolio::class)->careerChart();
        $bar = collect($chart['bars'])->firstWhere('id', 'label-house');
        $this->assertFalse($bar['current']);
        $this->assertLessThanOrEqual($chart['today'] + 0.01, $bar['left'] + $bar['width']);
    }

    public function test_titles_and_descriptions_fit_what_search_engines_display(): void
    {
        foreach (config('portfolio.profile.seo') as $key => $text) {
            $limit = str_ends_with($key, '_title') ? 65 : 160;
            $this->assertLessThanOrEqual($limit, mb_strlen($text), "{$key} is too long to show in full.");
        }

        $this->get('/resume')
            ->assertSee('<title>Keron Lewis Résumé (CV) | Full-Stack PHP &amp; WordPress Developer</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('resume').'">', false);
        $this->get('/resume?skill=PHP')
            ->assertSee('<link rel="canonical" href="'.route('resume').'">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="'.route('resume').'">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="'.route('resume').'">', false);
    }

    public function test_resume_downloads_as_a_pdf(): void
    {
        $response = $this->get('/resume.pdf');

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_resume_json_follows_the_json_resume_shape(): void
    {
        $this->getJson('/resume.json')
            ->assertOk()
            ->assertJsonPath('basics.name', 'Keron Lewis')
            ->assertJsonPath('basics.email', config('portfolio.profile.email'))
            ->assertJsonCount(count(config('portfolio.experience')), 'work')
            ->assertJsonMissingPath('work.1.endDate');
    }

    public function test_vcard_downloads_with_contact_details(): void
    {
        $response = $this->get('/keron-lewis.vcf');

        $response->assertOk()->assertHeader('Content-Type', 'text/vcard; charset=utf-8');
        $this->assertStringContainsString('FN:Keron Lewis', $response->getContent());
        $this->assertStringContainsString('TEL;TYPE=CELL:+18682753268', $response->getContent());
    }

    public function test_sitemap_and_robots_use_the_configured_url(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('resume'));

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_public_pages_have_alt_text_and_a_clean_heading_outline(): void
    {
        $paths = [
            '/', '/resume', '/privacy', '/book',
            ...app(Portfolio::class)->caseStudies()->map(fn (array $project) => "/work/{$project['slug']}"),
            ...app(Portfolio::class)->services()->map(fn (array $service) => "/services/{$service['slug']}"),
        ];

        foreach ($paths as $path) {
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$this->get($path)->assertOk()->getContent());
            $xpath = new \DOMXPath($document);

            foreach ($xpath->query('//img') as $image) {
                $this->assertNotSame('', trim($image->getAttribute('alt')), "{$path} has an image without alt text.");
            }

            $previous = 0;
            $levels = [];
            foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
                $level = (int) substr($heading->nodeName, 1);
                $this->assertNotSame('', trim($heading->textContent), "{$path} has an empty <{$heading->nodeName}>.");
                $this->assertLessThanOrEqual($previous + 1, $level, "{$path} skips a heading level before \"".trim($heading->textContent).'".');
                $previous = $level;
                $levels[] = $level;
            }
            $this->assertSame(1, count(array_keys($levels, 1)), "{$path} should have exactly one <h1>.");
        }
    }

    public function test_public_pages_are_sent_without_template_indentation(): void
    {
        $html = $this->withSession(['_old_input' => ['message' => "First line\n    indented line"]])->get('/')->assertOk()->getContent();

        // What a visitor typed into the form comes back exactly as typed.
        $this->assertStringContainsString("First line\n    indented line</textarea>", $html);

        $outsideKeptBlocks = preg_replace('#<(textarea|script|style)\b.*?</\1>#is', '', $html);
        $this->assertDoesNotMatchRegularExpression('/\n[ \t]/', $outsideKeptBlocks);
    }

    public function test_admin_pages_keep_a_message_exactly_as_typed(): void
    {
        $message = ContactMessage::factory()->create(['message' => "First line\n    indented line"]);

        $this->actingAs(User::factory()->create())->get(route('admin.messages.show', $message))
            ->assertSee("First line\n    indented line", false);
    }

    public function test_project_cards_load_the_small_screenshot_and_offer_the_full_one(): void
    {
        $response = $this->get('/');

        foreach (app(Portfolio::class)->projects() as $project) {
            $this->assertNotNull($project['thumb'], "{$project['slug']} has no thumbnail; run `php artisan portfolio:screenshots`.");
            $this->assertLessThan($project['image_width'], $project['thumb_width']);

            $response->assertSee('<img src="'.e($project['thumb']).'"', false)
                ->assertSee(e("{$project['thumb']} {$project['thumb_width']}w, {$project['image']} {$project['image_width']}w"), false);
        }
    }

    public function test_pages_with_their_own_share_image_use_it(): void
    {
        $this->get('/')->assertSee('<meta property="og:image" content="'.asset('og-cover.png').'">', false);
        $this->get('/services/website-development')->assertSee('img/share/service-website-development.png?v=');
        $this->get('/work/code-canvas')->assertSee('img/share/work-code-canvas.png?v=');
    }

    public function test_unknown_pages_get_the_styled_404(): void
    {
        $this->get('/nope')->assertNotFound()->assertSee("That page isn't here.", false);
    }

    public function test_every_role_stack_entry_is_a_listed_skill(): void
    {
        $skills = collect(config('portfolio.skills'))->flatten();

        foreach (config('portfolio.experience') as $role) {
            $this->assertEmpty(
                collect($role['stack'])->diff($skills)->all(),
                "{$role['org']} lists stack items missing from the skills config.",
            );
        }
    }
}
