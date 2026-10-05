<?php

namespace Tests\Feature;

use App\Support\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseStudyTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_case_study_has_a_page_linked_from_the_home_page_and_sitemap(): void
    {
        $studies = app(Portfolio::class)->caseStudies();
        $this->assertNotEmpty($studies);

        $home = $this->get('/');
        $sitemap = $this->get('/sitemap.xml');

        foreach ($studies as $project) {
            $response = $this->get("/work/{$project['slug']}")
                ->assertOk()
                ->assertSee($project['case_study']['title'])
                ->assertSee("<title>{$project['name']} case study | Keron Lewis</title>", false)
                ->assertSee('href="'.$project['url'].'"', false);

            foreach ($project['case_study']['sections'] as $section) {
                $response->assertSee($section['heading']);
            }

            $home->assertSee('href="'.$project['case_url'].'"', false);
            $sitemap->assertSee($project['case_url']);
        }
    }

    public function test_a_project_without_a_case_study_has_no_page(): void
    {
        $project = collect(config('portfolio.projects'))->first(fn (array $project) => ! isset($project['case_study']));

        $this->get("/work/{$project['slug']}")->assertNotFound();
        $this->get('/work/not-a-project')->assertNotFound();
    }

    public function test_case_study_titles_and_summaries_fit_what_search_engines_display(): void
    {
        foreach (app(Portfolio::class)->caseStudies() as $project) {
            $this->assertLessThanOrEqual(65, mb_strlen("{$project['name']} case study | Keron Lewis"), "{$project['slug']} title is too long.");
            $this->assertLessThanOrEqual(160, mb_strlen($project['case_study']['summary']), "{$project['slug']} summary is too long.");
        }
    }

    public function test_a_testimonial_written_into_the_config_shows_on_the_home_page_and_its_case_study(): void
    {
        $this->get('/')->assertDontSee('class="quote"', false);

        config(['portfolio.projects.0.testimonial' => [
            'quote' => 'Delivered on time and explained every step.',
            'name' => 'Jane Client',
            'role' => 'Owner, Example Ltd.',
        ]]);
        $slug = config('portfolio.projects.0.slug');

        $this->get('/')->assertSeeInOrder(['Delivered on time and explained every step.', 'Jane Client', 'Owner, Example Ltd.']);
        $this->get("/work/{$slug}")->assertSeeInOrder(['What the client said', 'Delivered on time and explained every step.', 'Jane Client']);
    }

    public function test_the_preview_data_leaves_out_case_study_text(): void
    {
        $study = app(Portfolio::class)->caseStudies()->first()['case_study'];

        $this->get('/')
            ->assertSee('"case_url":', false)
            ->assertDontSee($study['sections'][0]['body'][0] ?? $study['sections'][0]['points'][0]);
    }
}
