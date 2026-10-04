<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the person who used the contact form that their message arrived.
 * It deliberately repeats nothing they typed, so the form can't be used to
 * send someone else's text to an address of the sender's choosing.
 */
class ContactAutoReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        $profile = config('portfolio.profile');

        return new Envelope(
            replyTo: [new Address(config('portfolio.contact_to'), $profile['name'])],
            subject: "Thanks for getting in touch · {$profile['name']}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contact-auto-reply', with: ['profile' => config('portfolio.profile')]);
    }
}
