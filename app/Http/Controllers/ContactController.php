<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactAutoReply;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function store(ContactRequest $request)
    {
        $reply = 'Thanks, your message is on its way to me.';

        // Bots that fill the honeypot get the same answer, but nothing is saved or sent.
        if (! $request->isSpam()) {
            $message = ContactMessage::create($request->details());

            // The message is already saved, so a mail outage shouldn't fail the request.
            try {
                Mail::to(config('portfolio.contact_to'))->send(new ContactMessageReceived($message));
                $message->update(['emailed_at' => now()]);
            } catch (Throwable $e) {
                report($e);
            }

            // Let the sender know it arrived. A failure here must not look like a failed message.
            try {
                Mail::to($message->email)->send(new ContactAutoReply($message));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $request->expectsJson()
            ? response()->json(['message' => $reply])
            : redirect()->to(route('home').'#contact')->with('contact_sent', $reply);
    }
}
