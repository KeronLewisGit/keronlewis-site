<?php

namespace Tests\Feature;

use App\Mail\InboxDigest;
use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InboxDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_email_is_sent_when_nothing_is_waiting(): void
    {
        Mail::fake();
        ContactMessage::factory()->read()->create(['created_at' => now()->subDays(3)]);
        // Unread, but it only arrived an hour ago.
        ContactMessage::factory()->create(['created_at' => now()->subHour()]);
        Testimonial::factory()->create();
        Booking::factory()->create(['starts_at' => now()->addDays(3)]);

        $this->artisan('portfolio:digest')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_the_reminder_lists_what_is_waiting(): void
    {
        Mail::fake();
        ContactMessage::factory()->create(['name' => 'Ada Lovelace', 'created_at' => now()->subDays(2)]);
        Testimonial::factory()->submitted()->create();
        Booking::factory()->create(['name' => 'Grace Hopper', 'starts_at' => now()->addHours(5)]);

        $this->artisan('portfolio:digest')->assertSuccessful();

        Mail::assertSent(InboxDigest::class, function (InboxDigest $mail) {
            $mail->assertHasSubject('Waiting on your site: 1 unread message, 1 testimonial to approve, 1 call coming up')
                ->assertSeeInHtml('Ada Lovelace')
                ->assertSeeInHtml('Grace Hopper')
                ->assertSeeInHtml(route('admin.testimonials'));

            return $mail->hasTo(config('portfolio.contact_to'));
        });
    }
}
