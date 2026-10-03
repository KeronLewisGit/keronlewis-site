<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    private array $valid = [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'topic' => 'role',
        'message' => 'We have a full-stack role you might like.',
    ];

    public function test_a_valid_message_is_saved_and_emailed(): void
    {
        Mail::fake();

        $this->postJson('/contact', $this->valid)->assertOk()->assertJsonStructure(['message']);

        $this->assertDatabaseHas('contact_messages', ['email' => 'ada@example.com', 'topic' => 'role']);
        $this->assertNotNull(ContactMessage::first()->emailed_at);

        Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasTo(config('portfolio.contact_to'))
            && $mail->hasReplyTo('ada@example.com'));
    }

    public function test_the_message_is_kept_even_when_mail_fails(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP is down'));

        $this->postJson('/contact', $this->valid)->assertOk();

        $this->assertDatabaseCount('contact_messages', 1);
        $this->assertNull(ContactMessage::first()->emailed_at);
    }

    public function test_invalid_input_is_rejected_with_field_errors(): void
    {
        $this->postJson('/contact', ['name' => '', 'email' => 'not-an-email', 'topic' => 'spam', 'message' => 'hi'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'topic', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_honeypot_submissions_are_dropped_quietly(): void
    {
        Mail::fake();

        $this->postJson('/contact', $this->valid + ['website' => 'https://spam.example'])->assertOk();

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    public function test_a_plain_form_post_redirects_back_to_the_contact_section(): void
    {
        Mail::fake();

        $this->post('/contact', $this->valid)
            ->assertRedirect(route('home').'#contact')
            ->assertSessionHas('contact_sent');
    }

    public function test_the_form_is_rate_limited(): void
    {
        Mail::fake();

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/contact', $this->valid)->assertOk();
        }

        $this->postJson('/contact', $this->valid)->assertStatus(429);
    }
}
