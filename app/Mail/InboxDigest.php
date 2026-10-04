<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The daily reminder of what is waiting in the admin area.
 */
class InboxDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, ContactMessage>  $unread
     * @param  Collection<int, Booking>  $calls
     */
    public function __construct(public Collection $unread, public int $pendingTestimonials, public Collection $calls) {}

    public function envelope(): Envelope
    {
        $parts = array_filter([
            $this->unread->isEmpty() ? null : $this->unread->count().' unread '.Str::plural('message', $this->unread->count()),
            $this->pendingTestimonials ? $this->pendingTestimonials.' '.Str::plural('testimonial', $this->pendingTestimonials).' to approve' : null,
            $this->calls->isEmpty() ? null : $this->calls->count().' '.Str::plural('call', $this->calls->count()).' coming up',
        ]);

        return new Envelope(subject: 'Waiting on your site: '.implode(', ', $parts));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.inbox-digest');
    }
}
