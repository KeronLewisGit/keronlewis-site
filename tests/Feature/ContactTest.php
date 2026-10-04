<?php

namespace Tests\Feature;

use App\Mail\ContactAutoReply;
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

    public function test_the_sender_gets_an_acknowledgement_that_repeats_nothing_they_typed(): void
    {
        Mail::fake();

        $this->postJson('/contact', $this->valid)->assertOk();

        Mail::assertSent(ContactAutoReply::class, function (ContactAutoReply $mail) {
            $mail->assertSeeInHtml('has reached me')
                ->assertDontSeeInHtml('We have a full-stack role you might like.')
                ->assertDontSeeInHtml('Ada Lovelace');

            return $mail->hasTo('ada@example.com') && $mail->hasReplyTo(config('portfolio.contact_to'));
        });
    }

    public function test_budget_and_timeline_are_kept_only_for_a_project_enquiry(): void
    {
        Mail::fake();
        $extras = ['budget' => '1500-5000', 'timeline' => 'month'];

        $this->postJson('/contact', ['topic' => 'project'] + $extras + $this->valid)->assertOk();
        $this->postJson('/contact', ['email' => 'grace@example.com'] + $extras + $this->valid)->assertOk();
        $this->postJson('/contact', ['topic' => 'project', 'budget' => 'a-million'] + $this->valid)->assertJsonValidationErrors('budget');

        $project = ContactMessage::firstWhere('topic', 'project');
        $this->assertSame(['Budget' => 'TT$1,500 to TT$5,000', 'Timeline' => 'Within a month'], $project->extras());
        $this->assertSame([], ContactMessage::firstWhere('email', 'grace@example.com')->extras());

        Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->contactMessage->is($project) && $mail->assertSeeInHtml('TT$1,500 to TT$5,000'));
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
