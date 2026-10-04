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
 * Sent to the site owner when someone books a call.
 */
class BookingReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->booking->email, $this->booking->name)],
            subject: "Call booked: {$this->booking->name}, {$this->booking->localStart()->format('D j M, g:i A')}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.booking-received');
    }
}
