<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the person who booked when the site owner cancels the call.
 */
class BookingCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        $name = config('portfolio.profile.name');

        return new Envelope(
            replyTo: [new Address(config('portfolio.contact_to'), $name)],
            subject: "Your call with {$name} on {$this->booking->localStart()->format('D j M')} is cancelled",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.booking-cancelled', with: ['profile' => config('portfolio.profile')]);
    }
}
