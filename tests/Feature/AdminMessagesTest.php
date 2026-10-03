<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_see_or_change_messages(): void
    {
        $message = ContactMessage::factory()->create();

        $this->get('/admin/messages')->assertRedirect(route('admin.login'));
        $this->get("/admin/messages/{$message->id}")->assertRedirect(route('admin.login'));
        $this->delete("/admin/messages/{$message->id}")->assertRedirect(route('admin.login'));

        $this->assertModelExists($message);
        $this->assertNull($message->fresh()->read_at);
    }

    public function test_the_inbox_lists_messages_newest_first_with_an_unread_count(): void
    {
        ContactMessage::factory()->read()->create(['name' => 'Grace Hopper', 'created_at' => now()->subDay()]);
        ContactMessage::factory()->create(['name' => 'Ada Lovelace', 'message' => 'We have a full-stack role you might like.']);

        $this->actingAs(User::factory()->create())->get('/admin/messages')
            ->assertOk()
            ->assertSee('2 messages from the contact form · 1 unread')
            ->assertSeeInOrder(['Ada Lovelace', 'We have a full-stack role you might like.', 'Grace Hopper'])
            ->assertSee('aria-label="1 unread"', false);
    }

    public function test_the_unread_filter_hides_messages_already_opened(): void
    {
        ContactMessage::factory()->read()->create(['name' => 'Grace Hopper']);
        ContactMessage::factory()->create(['name' => 'Ada Lovelace']);

        $this->actingAs(User::factory()->create())->get('/admin/messages?show=unread')
            ->assertOk()
            ->assertSee('Ada Lovelace')
            ->assertDontSee('Grace Hopper');
    }

    public function test_opening_a_message_shows_it_in_full_and_marks_it_read(): void
    {
        $message = ContactMessage::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'message' => "First line.\n<script>alert(1)</script>",
        ]);

        $this->actingAs(User::factory()->create())->get("/admin/messages/{$message->id}")
            ->assertOk()
            ->assertSee('Ada Lovelace')
            ->assertSee('mailto:ada@example.com?subject=', false)
            ->assertSee('First line.')
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_a_message_can_be_marked_unread_again(): void
    {
        $message = ContactMessage::factory()->read()->create();

        $this->actingAs(User::factory()->create())->patch("/admin/messages/{$message->id}/unread")
            ->assertRedirect(route('admin.messages'));

        $this->assertNull($message->fresh()->read_at);
    }

    public function test_a_message_can_be_deleted(): void
    {
        $message = ContactMessage::factory()->create();

        $this->actingAs(User::factory()->create())->delete("/admin/messages/{$message->id}")
            ->assertRedirect(route('admin.messages'));

        $this->assertModelMissing($message);
    }
}
