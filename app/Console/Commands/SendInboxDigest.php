<?php

namespace App\Console\Commands;

use App\Mail\InboxDigest;
use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Testimonial;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('portfolio:digest {--hours=24 : Only count messages that have sat unread for this long}')]
#[Description('Email a reminder when messages are unread, testimonials await approval or calls are booked for the next day')]
class SendInboxDigest extends Command
{
    public function handle(): int
    {
        $messages = ContactMessage::unread()->where('created_at', '<=', now()->subHours((int) $this->option('hours')))->oldest()->get();
        $testimonials = Testimonial::pending()->count();
        $calls = Booking::upcoming()->where('starts_at', '<=', now()->addDay())->get();

        if ($messages->isEmpty() && $testimonials === 0 && $calls->isEmpty()) {
            $this->info('Nothing waiting, so no email was sent.');

            return self::SUCCESS;
        }

        Mail::to(config('portfolio.contact_to'))->send(new InboxDigest($messages, $testimonials, $calls));

        $this->info("Sent a reminder: {$messages->count()} unread, {$testimonials} awaiting approval, {$calls->count()} calls in the next day.");

        return self::SUCCESS;
    }
}
