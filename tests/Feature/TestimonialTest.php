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
        'quote' => 'Delivered on time and explained every step along the way.',
        'consent' => '1',
    ];

    public function test_only_someone_with_the_link_can_open_the_form(): void
    {
        $testimonial = Testimonial::invite('Jane Client', 'Code Canvas Consultants');

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
        $testimonial = Testimonial::invite('Jane Client');

        $this->post($testimonial->link(), $this->valid)->assertRedirect($testimonial->link());

        $testimonial->refresh();
        $this->assertSame('Delivered on time and explained every step along the way.', $testimonial->quote);
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

        $this->post($testimonial->link(), ['quote' => 'Too short'])->assertSessionHasErrors(['quote', 'consent']);

        $this->assertNull($testimonial->fresh()->submitted_at);
    }

    public function test_guests_cannot_manage_testimonials(): void
    {
        $testimonial = Testimonial::factory()->submitted()->create(['sent_to' => 'Jane Client']);

        $this->get('/admin/testimonials')->assertRedirect(route('admin.login'));
        $this->post('/admin/testimonials', ['sent_to' => 'Someone'])->assertRedirect(route('admin.login'));
        $this->get("/admin/testimonials/{$testimonial->id}/edit")->assertRedirect(route('admin.login'));
        $this->put("/admin/testimonials/{$testimonial->id}", ['sent_to' => 'Changed'])->assertRedirect(route('admin.login'));
        $this->patch("/admin/testimonials/{$testimonial->id}/approve")->assertRedirect(route('admin.login'));

        $this->assertNull($testimonial->fresh()->approved_at);
        $this->assertSame('Jane Client', $testimonial->fresh()->sent_to);
        $this->assertDatabaseCount('testimonials', 1);
    }

    public function test_the_admin_creates_a_private_link_for_a_client(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post('/admin/testimonials', ['sent_to' => 'Jane Client', 'project' => 'code canvas consultants'])
            ->assertRedirect(route('admin.testimonials'));
        $this->actingAs($admin)->post('/admin/testimonials', ['sent_to' => ''])->assertSessionHasErrors('sent_to');

        $testimonial = Testimonial::sole();
        $this->assertSame(40, strlen($testimonial->token));
        // A name that matches a portfolio project ties the testimonial to it.
        $this->assertSame(['code-canvas', 'Code Canvas Consultants'], [$testimonial->project_slug, $testimonial->project]);

        $this->actingAs($admin)->get('/admin/testimonials')->assertOk()->assertSee($testimonial->link())->assertSee('Jane Client');
    }

    public function test_the_name_and_project_shown_are_the_ones_the_admin_entered(): void
    {
        $testimonial = Testimonial::invite('Dr. Jane Client', 'Quantum Global Institute');
        $this->post($testimonial->link(), $this->valid);
        $testimonial->update(['approved_at' => now()]);

        // Not one of the portfolio projects, so it isn't tied to a card.
        $this->assertNull($testimonial->project_slug);

        $this->get('/')->assertSeeInOrder(['What clients say', 'Delivered on time and explained every step', 'Dr. Jane Client', 'Quantum Global Institute']);
    }

    public function test_an_approved_testimonial_shows_on_its_project_and_can_be_taken_down(): void
    {
        $admin = User::factory()->create();
        $testimonial = Testimonial::factory()->submitted()->create([
            'sent_to' => 'Jane Client',
            'project_slug' => 'code-canvas',
            'quote' => 'Delivered on time and explained every step along the way.',
        ]);

        $this->actingAs($admin)->get('/admin/testimonials')->assertSee('aria-label="1 waiting for approval"', false);
        $this->actingAs($admin)->patch("/admin/testimonials/{$testimonial->id}/approve")->assertRedirect(route('admin.testimonials'));

        // Tied to a portfolio project, it is still listed with the rest on the home page.
        $this->get('/')->assertSeeInOrder(['What clients say', 'Delivered on time and explained every step', 'Jane Client', 'Code Canvas Consultants']);
        $this->get('/work/code-canvas')->assertSeeInOrder(['What the client said', 'Delivered on time and explained every step', 'Jane Client']);
        $this->get('/services/website-development')->assertSeeInOrder(['What clients say', 'Delivered on time and explained every step']);

        $this->actingAs($admin)->patch("/admin/testimonials/{$testimonial->id}/unpublish")->assertRedirect(route('admin.testimonials'));
        $this->get('/')->assertDontSee('Delivered on time and explained every step');
    }

    public function test_the_admin_can_correct_a_testimonial_and_pick_phrases_to_highlight(): void
    {
        $admin = User::factory()->create();
        $testimonial = Testimonial::factory()->approved()->create([
            'sent_to' => 'Caron Louis',
            'quote' => 'Working with Caron was great. We grew from 100 to over 300 students <b>fast</b>.',
        ]);

        $this->actingAs($admin)->get("/admin/testimonials/{$testimonial->id}/edit")->assertOk()->assertSee('Working with Caron was great.');

        $this->actingAs($admin)->put("/admin/testimonials/{$testimonial->id}", [
            'sent_to' => 'Jane Client',
            'project' => 'Quantum Global Institute',
            'quote' => 'Working with Keron was great. We grew from 100 to over 300 students <b>fast</b>.',
            'highlights' => "from 100 to over 300 students\n",
        ])->assertRedirect(route('admin.testimonials'));

        $this->assertSame(['from 100 to over 300 students'], $testimonial->fresh()->highlights);

        $this->get('/')
            // The first highlight is set large above the words, and marked within them.
            ->assertSee('<p class="quote-pull">From 100 to over 300 students</p>', false)
            ->assertSee('We grew <mark>from 100 to over 300 students</mark> &lt;b&gt;fast&lt;/b&gt;.', false)
            ->assertSeeInOrder(['Working with Keron was great.', 'Jane Client', 'Quantum Global Institute'])
            ->assertDontSee('Caron');
    }

    public function test_a_highlight_has_to_be_a_phrase_from_the_testimonial(): void
    {
        $testimonial = Testimonial::factory()->submitted()->create(['quote' => 'Delivered on time and explained every step along the way.']);

        $this->actingAs(User::factory()->create())->put("/admin/testimonials/{$testimonial->id}", [
            'sent_to' => 'Jane Client',
            'quote' => 'Delivered on time and explained every step along the way.',
            'highlights' => 'best developer in the world',
        ])->assertSessionHasErrors('highlights');

        $this->assertNull($testimonial->fresh()->highlights);
    }

    public function test_every_approved_testimonial_is_listed_under_what_clients_say(): void
    {
        Testimonial::factory()->approved()->create(['sent_to' => 'Jane Client', 'quote' => 'A pleasure to work with from the first call to launch.', 'approved_at' => now()->subDay()]);
        Testimonial::factory()->approved()->create(['sent_to' => 'Rhea Ward', 'project_slug' => 'for-the-culture', 'quote' => 'Patient and thorough with the technical side of my business.']);
        // Something a client typed on an older form is not shown; only the name and project entered in the admin are.
        Testimonial::factory()->approved()->create(['sent_to' => 'Sam Owner', 'role' => 'Education and Research', 'approved_at' => now()->subDays(2)]);

        $this->get('/')
            ->assertSeeInOrder(['What clients say', 'Patient and thorough', 'Rhea Ward', 'For The Culture', 'A pleasure to work with', 'Jane Client', 'Sam Owner'])
            ->assertDontSee('Education and Research');
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
