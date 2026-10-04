<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmation;
use App\Mail\BookingReceived;
use App\Models\Booking;
use App\Support\BookingCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * The public "Book a call" page. Which times are offered is set in the admin area.
 */
class BookingController extends Controller
{
    public function show(BookingCalendar $calendar): View
    {
        return view('booking.show', [
            'profile' => config('portfolio.profile'),
            'enabled' => $calendar->isEnabled(),
            'days' => $calendar->openSlots(),
            'minutes' => $calendar->settings()['minutes'],
            'timezone' => $calendar->timezone(),
            'booked' => session('booked') ? Booking::find(session('booked')) : null,
        ]);
    }

    public function store(Request $request, BookingCalendar $calendar): RedirectResponse
    {
        abort_unless($calendar->isEnabled(), 404);

        $data = $request->validate([
            'slot' => ['required', 'string'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            // Digits and the usual separators only, since this number is repeated in the confirmation email.
            'phone' => ['required', 'regex:/^[0-9+()\-\s.]{7,40}$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
            // Honeypot: hidden from people, irresistible to bots.
            'website' => ['nullable', 'string'],
        ], [
            'slot.required' => 'Pick a time for the call.',
            'name.required' => 'Please tell me your name.',
            'email.required' => "I'll need an email address to send the confirmation to.",
            'email.email' => "That email address doesn't look right.",
            'phone.required' => 'Add the number I should call.',
            'phone.regex' => "That phone number doesn't look right.",
        ]);

        // Bots that fill the honeypot are sent back as if nothing happened.
        if (filled($data['website'] ?? null)) {
            return redirect()->route('booking.show');
        }

        $booking = DB::transaction(function () use ($calendar, $data) {
            if (! $calendar->isOpen($data['slot'])) {
                throw ValidationException::withMessages(['slot' => 'That time is no longer available. Please pick another.']);
            }

            return Booking::create([
                'starts_at' => Carbon::parse($data['slot']),
                'minutes' => $calendar->settings()['minutes'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        // The call is in the diary either way, so a mail outage shouldn't fail the request.
        foreach ([[config('portfolio.contact_to'), new BookingReceived($booking)], [$booking->email, new BookingConfirmation($booking)]] as [$to, $mail]) {
            try {
                Mail::to($to)->send($mail);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('booking.show')->with('booked', $booking->id);
    }
}
