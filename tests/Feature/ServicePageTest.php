<?php

namespace Tests\Feature;

use App\Support\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_service_has_a_page_linked_from_the_home_page_and_sitemap(): void
    {
        $services = app(Portfolio::class)->services();
        $this->assertNotEmpty($services);

        $home = $this->get('/');
        $sitemap = $this->get('/sitemap.xml');

        foreach ($services as $service) {
            $response = $this->get("/services/{$service['slug']}")
                ->assertOk()
                ->assertSee('<title>'.e($service['page']['seo_title']).'</title>', false)
                ->assertSee('<meta name="description" content="'.e($service['page']['summary']).'">', false)
                ->assertSee('<h1>'.e($service['page']['heading']).'</h1>', false)
                ->assertSee('<link rel="canonical" href="'.$service['url'].'">', false);

            foreach ($service['page']['sections'] as $section) {
                $response->assertSee($section['heading']);
            }

            // Each page links on to the other services, and every page of the site links to each service.
            foreach ($services->where('slug', '!==', $service['slug']) as $other) {
                $response->assertSee('href="'.$other['url'].'"', false);
            }

            $home->assertSee('href="'.$service['url'].'"', false)->assertSee($service['title']);
            $sitemap->assertSee($service['url']);
        }
    }

    public function test_an_unknown_service_has_no_page(): void
    {
        $this->get('/services/not-a-service')->assertNotFound();
    }

    public function test_service_titles_and_summaries_fit_what_search_engines_display(): void
    {
        foreach (config('portfolio.services') as $service) {
            $this->assertLessThanOrEqual(65, mb_strlen($service['page']['seo_title']), "{$service['slug']} title is too long.");
            $this->assertLessThanOrEqual(160, mb_strlen($service['page']['summary']), "{$service['slug']} summary is too long.");
        }
    }

    public function test_related_work_on_a_service_page_names_real_projects(): void
    {
        $projects = collect(config('portfolio.projects'))->keyBy('slug');

        foreach (config('portfolio.services') as $service) {
            $response = $this->get("/services/{$service['slug']}");

            foreach ($service['page']['projects'] as $slug) {
                $this->assertTrue($projects->has($slug), "{$service['slug']} lists a project that doesn't exist: {$slug}.");
                $response->assertSee($projects[$slug]['name']);
            }
        }
    }

    public function test_structured_data_describes_a_local_business_and_its_services(): void
    {
        $service = app(Portfolio::class)->services()->first();

        $this->get('/')
            ->assertSee('"@type":"ProfessionalService"', false)
            ->assertSee('"@type":"OfferCatalog"', false)
            ->assertSee('"areaServed":[{"@type":"Country","name":"Trinidad and Tobago"}', false);

        $this->get("/services/{$service['slug']}")
            ->assertSee('"@type":"Service"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"addressLocality":"Port of Spain"', false);
    }
}
