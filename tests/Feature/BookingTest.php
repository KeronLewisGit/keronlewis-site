<?php

namespace Tests\Feature;

use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmation;
use App\Mail\BookingReceived;
use App\Models\Booking;
use App\Models\User;
use App\Support\BookingCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private array $valid = [
        'slot' => '2026-10-06T13:30:00Z',
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => '+1 (868) 555-0100',
        'notes' => 'A new website for my shop.',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Monday 5 October 2026, 8:00 AM in Trinidad.
        $this->travelTo('2026-10-05 12:00:00');
    }

    /** Weekdays 9:00 to 11:00, half-hour calls, twelve hours' notice, two days ahead. */
    private function openBooking(array $overrides = []): void
    {
        $hours = collect(BookingCalendar::DAYS)->map(fn ($name, $day) => [
            'on' => ! in_array($day, ['sat', 'sun']), 'from' => '09:00', 'to' => '11:00',
        ])->all();

        app(BookingCalendar::class)->save($overrides + [
            'enabled' => true, 'minutes' => 30, 'notice_hours' => 12, 'days_ahead' => 2, 'hours' => $hours, 'blocked' => [],
        ]);
    }

    public function test_booking_is_closed_until_the_admin_switches_it_on(): void
    {
        $this->get('/book')->assertOk()->assertSee("Online booking isn't open at the moment")->assertSee('noindex');
        $this->post('/book', $this->valid)->assertNotFound();
        $this->get('/')->assertDontSee('Book a call')->assertSee('Read my résumé');
        $this->get('/sitemap.xml')->assertDontSee(route('booking.show'));

        $this->openBooking();

        // Once booking is open it takes the résumé's place beside "Get in touch".
        $this->get('/')->assertSee('href="'.route('booking.show').'"', false)->assertDontSee('Read my résumé');
        $this->get('/sitemap.xml')->assertSee(route('booking.show'));
    }

    public function test_the_page_offers_the_times_the_settings_allow(): void
    {
        $this->openBooking(['blocked' => ['2026-10-07']]);
        Booking::factory()->create(['starts_at' => '2026-10-06 14:00:00', 'minutes' => 30]);
        Booking::factory()->cancelled()->create(['starts_at' => '2026-10-06 14:30:00', 'minutes' => 30]);

        $this->get('/book')
            ->assertOk()
            ->assertSee('Tuesday 6 October')
            ->assertSee('value="2026-10-06T13:00:00Z"', false)
            ->assertSee('value="2026-10-06T13:30:00Z"', false)
            ->assertSeeInOrder(['9:00 AM', '9:30 AM', '10:30 AM'])
            // Already booked.
            ->assertDontSee('value="2026-10-06T14:00:00Z"', false)
            // A cancelled call frees its time again.
            ->assertSee('value="2026-10-06T14:30:00Z"', false)
            // Today is inside the notice period, and Wednesday is a day off.
            ->assertDontSee('Monday 5 October')
            ->assertDontSee('Wednesday 7 October');
    }

    public function test_booking_a_call_saves_it_and_emails_both_sides(): void
    {
        Mail::fake();
        $this->openBooking();

        $this->post('/book', $this->valid)->assertRedirect(route('booking.show'));

        $booking = Booking::sole();
        $this->assertSame('2026-10-06 13:30:00', $booking->starts_at->toDateTimeString());
        $this->assertSame(30, $booking->minutes);
        $this->assertSame('Tuesday 6 October 2026, 9:30 AM', $booking->when());

        Mail::assertSent(BookingReceived::class, fn ($mail) => $mail->hasTo(config('portfolio.contact_to')) && $mail->hasReplyTo('ada@example.com'));
        Mail::assertSent(BookingConfirmation::class, fn ($mail) => $mail->hasTo('ada@example.com'));

        $this->from('/book')->followingRedirects()->post('/book', $this->valid)->assertSee('That time is no longer available.');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_the_confirmation_names_the_time_and_repeats_nothing_free_form(): void
    {
        $booking = Booking::factory()->create(['starts_at' => '2026-10-06 13:30:00', 'phone' => '+1 868 555 0100', 'name' => 'Ada Lovelace', 'notes' => 'Private notes.']);

        (new BookingConfirmation($booking))
            ->assertSeeInHtml('Tuesday 6 October 2026, 9:30 AM')
            ->assertSeeInHtml('+1 868 555 0100')
            ->assertDontSeeInHtml('Private notes.')
            ->assertDontSeeInHtml('Ada Lovelace');
    }

    public function test_a_booking_needs_a_free_time_and_real_contact_details(): void
    {
        $this->openBooking();

        $this->post('/book', ['slot' => '2026-10-10T13:00:00Z', 'phone' => 'call me maybe http://spam.example'] + $this->valid)
            ->assertSessionHasErrors(['phone']);
        // A Saturday, which isn't offered.
        $this->post('/book', ['slot' => '2026-10-10T13:00:00Z'] + $this->valid)->assertSessionHasErrors(['slot']);
        $this->post('/book', ['email' => 'not-an-email'] + $this->valid)->assertSessionHasErrors(['email']);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_honeypot_bookings_are_dropped_quietly(): void
    {
        Mail::fake();
        $this->openBooking();

        $this->post('/book', $this->valid + ['website' => 'https://spam.example'])->assertRedirect(route('booking.show'));

        $this->assertDatabaseCount('bookings', 0);
        Mail::assertNothingSent();
    }

    public function test_guests_cannot_see_or_change_bookings(): void
    {
        $booking = Booking::factory()->create();

        $this->get('/admin/bookings')->assertRedirect(route('admin.login'));
        $this->put('/admin/bookings/settings', [])->assertRedirect(route('admin.login'));
        $this->patch("/admin/bookings/{$booking->id}/cancel")->assertRedirect(route('admin.login'));

        $this->assertNull($booking->fresh()->cancelled_at);
    }

    public function test_the_admin_sets_the_hours_and_days_off(): void
    {
        $admin = User::factory()->create();
        $hours = collect(BookingCalendar::DAYS)->map(fn () => ['on' => '0', 'from' => '09:00', 'to' => '17:00'])->all();
        $hours['tue'] = ['on' => '1', 'from' => '14:00', 'to' => '15:00'];
        $form = ['enabled' => '1', 'minutes' => '60', 'notice_hours' => '0', 'days_ahead' => '7', 'hours' => $hours, 'blocked' => "2026-12-25\n2026-12-26"];

        $this->actingAs($admin)->put('/admin/bookings/settings', $form)->assertRedirect(route('admin.bookings'));

        $settings = app(BookingCalendar::class)->settings();
        $this->assertTrue($settings['enabled']);
        $this->assertSame(60, $settings['minutes']);
        $this->assertSame(['2026-12-25', '2026-12-26'], $settings['blocked']);

        // One hour-long call on Tuesday afternoon, and nothing else this week.
        $this->get('/book')->assertSee('value="2026-10-06T18:00:00Z"', false)->assertDontSee('Wednesday');
        $this->actingAs($admin)->get('/admin/bookings')->assertOk()->assertSee('1 time on offer');

        $this->actingAs($admin)->put('/admin/bookings/settings', ['blocked' => 'Christmas'] + $form)->assertSessionHasErrors('blocked');
        $hours['tue'] = ['on' => '1', 'from' => '15:00', 'to' => '14:00'];
        $this->actingAs($admin)->put('/admin/bookings/settings', ['hours' => $hours] + $form)->assertSessionHasErrors('hours.tue.to');
    }

    public function test_cancelling_a_call_emails_the_person_and_frees_the_time(): void
    {
        Mail::fake();
        $this->openBooking();
        $booking = Booking::factory()->create(['starts_at' => '2026-10-06 13:30:00', 'email' => 'ada@example.com']);

        $this->get('/book')->assertDontSee('value="2026-10-06T13:30:00Z"', false);

        $this->actingAs(User::factory()->create())->patch("/admin/bookings/{$booking->id}/cancel")->assertRedirect(route('admin.bookings'));

        $this->assertNotNull($booking->fresh()->cancelled_at);
        Mail::assertSent(BookingCancelled::class, fn ($mail) => $mail->hasTo('ada@example.com'));
        $this->get('/book')->assertSee('value="2026-10-06T13:30:00Z"', false);
    }
}
