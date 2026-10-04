<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    private array $valid = [
        'name' => 'Jane Client',
        'role' => 'Owner, Example Ltd.',
        'quote' => 'Delivered on time and explained every step along the way.',
        'consent' => '1',
    ];

    public function test_only_someone_with_the_link_can_open_the_form(): void
    {
        $testimonial = Testimonial::factory()->create(['sent_to' => 'Jane Client', 'project_slug' => 'code-canvas']);

        $this->get($testimonial->link())
            ->assertOk()
            ->assertSee('Thanks for taking the time, Jane Client.')
            ->assertSee('Code Canvas Consultants')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->get('/testimonial/not-a-real-token')->assertNotFound();
        $this->get('/testimonial')->assertNotFound();
        $this->get('/robots.txt')->assertSee('Disallow: /testimonial');
        $this->get('/sitemap.xml')->assertDontSee('testimonial');
    }

    public function test_a_submitted_testimonial_waits_for_approval_before_it_shows(): void
    {
        $testimonial = Testimonial::factory()->create();

        $this->post($testimonial->link(), $this->valid)->assertRedirect($testimonial->link());

        $testimonial->refresh();
        $this->assertSame('Jane Client', $testimonial->name);
        $this->assertNotNull($testimonial->submitted_at);
        $this->assertNull($testimonial->approved_at);

        $this->get($testimonial->link())->assertSee('Thank you, Jane Client.')->assertDontSee('Send testimonial');
        $this->get('/')->assertDontSee('Delivered on time and explained every step');
    }

    public function test_a_link_takes_one_testimonial_only(): void
    {
        $testimonial = Testimonial::factory()->submitted()->create(['quote' => 'The original words, which should stay as they are.']);

        $this->post($testimonial->link(), $this->valid)->assertRedirect($testimonial->link());

        $this->assertSame('The original words, which should stay as they are.', $testimonial->fresh()->quote);
    }

    public function test_a_testimonial_needs_enough_words_and_permission_to_publish(): void
    {
        $testimonial = Testimonial::factory()->create();

        $this->post($testimonial->link(), ['name' => '', 'quote' => 'Too short'])
            ->assertSessionHasErrors(['name', 'quote', 'consent']);

        $this->assertNull($testimonial->fresh()->submitted_at);
    }

    public function test_guests_cannot_manage_testimonials(): void
    {
        $testimonial = Testimonial::factory()->submitted()->create();

        $this->get('/admin/testimonials')->assertRedirect(route('admin.login'));
        $this->post('/admin/testimonials', ['sent_to' => 'Someone'])->assertRedirect(route('admin.login'));
        $this->patch("/admin/testimonials/{$testimonial->id}/approve")->assertRedirect(route('admin.login'));

        $this->assertNull($testimonial->fresh()->approved_at);
        $this->assertDatabaseCount('testimonials', 1);
    }

    public function test_the_admin_creates_a_private_link_for_a_client(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post('/admin/testimonials', ['sent_to' => 'Jane Client', 'project_slug' => 'code-canvas'])
            ->assertRedirect(route('admin.testimonials'));
        $this->actingAs($admin)->post('/admin/testimonials', ['sent_to' => 'Jane Client', 'project_slug' => 'not-a-project'])
            ->assertSessionHasErrors('project_slug');

        $testimonial = Testimonial::sole();
        $this->assertSame(40, strlen($testimonial->token));

        $this->actingAs($admin)->get('/admin/testimonials')->assertOk()->assertSee($testimonial->link())->assertSee('Jane Client');
    }

    public function test_an_approved_testimonial_shows_on_its_project_and_can_be_taken_down(): void
    {
        $admin = User::factory()->create();
        $testimonial = Testimonial::factory()->submitted()->create([
            'project_slug' => 'code-canvas',
            'name' => 'Jane Client',
            'quote' => 'Delivered on time and explained every step along the way.',
        ]);

        $this->actingAs($admin)->get('/admin/testimonials')->assertSee('aria-label="1 waiting for approval"', false);
        $this->actingAs($admin)->patch("/admin/testimonials/{$testimonial->id}/approve")->assertRedirect(route('admin.testimonials'));

        $this->get('/')->assertSee('Delivered on time and explained every step')->assertDontSee('What clients say');
        $this->get('/work/code-canvas')->assertSeeInOrder(['What the client said', 'Delivered on time and explained every step', 'Jane Client']);
        $this->get('/services/website-development')->assertSeeInOrder(['What clients say', 'Delivered on time and explained every step']);

        $this->actingAs($admin)->patch("/admin/testimonials/{$testimonial->id}/unpublish")->assertRedirect(route('admin.testimonials'));
        $this->get('/')->assertDontSee('Delivered on time and explained every step');
    }

    public function test_an_approved_testimonial_without_a_project_shows_under_what_clients_say(): void
    {
        Testimonial::factory()->approved()->create(['name' => 'Jane Client', 'quote' => 'A pleasure to work with from the first call to launch.']);

        $this->get('/')->assertSeeInOrder(['What clients say', 'A pleasure to work with from the first call to launch.', 'Jane Client']);
    }

    public function test_an_unanswered_link_cannot_be_approved_and_any_testimonial_can_be_deleted(): void
    {
        $admin = User::factory()->create();
        $testimonial = Testimonial::factory()->create();

        $this->actingAs($admin)->patch("/admin/testimonials/{$testimonial->id}/approve")->assertNotFound();
        $this->actingAs($admin)->delete("/admin/testimonials/{$testimonial->id}")->assertRedirect(route('admin.testimonials'));

        $this->assertModelMissing($testimonial);
        $this->get($testimonial->link())->assertNotFound();
    }
}
