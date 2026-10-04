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
 * Sent to the person who booked a call.
 */
class BookingConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        $name = config('portfolio.profile.name');

        return new Envelope(
            replyTo: [new Address(config('portfolio.contact_to'), $name)],
            subject: "Your call with {$name}: {$this->booking->localStart()->format('D j M, g:i A')}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.booking-confirmation', with: ['profile' => config('portfolio.profile')]);
    }
}
