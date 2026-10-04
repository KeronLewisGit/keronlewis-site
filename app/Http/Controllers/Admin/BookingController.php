<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingCancelled;
use App\Models\Booking;
use App\Support\BookingCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class BookingController extends Controller
{
    public function index(BookingCalendar $calendar): View
    {
        return view('admin.bookings', [
            'upcoming' => Booking::upcoming()->get(),
            'earlier' => Booking::where(fn ($query) => $query->where('starts_at', '<', now())->orWhereNotNull('cancelled_at'))
                ->latest('starts_at')->limit(10)->get(),
            'settings' => $calendar->settings(),
            'days' => BookingCalendar::DAYS,
            'slotLengths' => BookingCalendar::SLOT_LENGTHS,
            'openSlots' => $calendar->openSlots()->flatten()->count(),
            'timezone' => $calendar->timezone(),
        ]);
    }

    /**
     * Save when calls can be booked.
     */
    public function update(Request $request, BookingCalendar $calendar): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'minutes' => ['required', Rule::in(BookingCalendar::SLOT_LENGTHS)],
            'notice_hours' => ['required', 'integer', 'between:0,168'],
            'days_ahead' => ['required', 'integer', 'between:1,60'],
            'hours' => ['required', 'array:'.implode(',', array_keys(BookingCalendar::DAYS))],
            'hours.*.on' => ['nullable', 'boolean'],
            'hours.*.from' => ['required', 'date_format:H:i'],
            'hours.*.to' => ['required', 'date_format:H:i'],
            'blocked' => ['nullable', 'string', 'max:2000'],
        ], [
            'hours.*.from.date_format' => 'Times look like 09:00.',
            'hours.*.to.date_format' => 'Times look like 17:00.',
        ]);

        $hours = [];
        foreach (BookingCalendar::DAYS as $day => $name) {
            $row = $data['hours'][$day] ?? ['from' => '09:00', 'to' => '17:00'];
            $on = (bool) ($row['on'] ?? false);

            if ($on && $row['to'] <= $row['from']) {
                throw ValidationException::withMessages(["hours.{$day}.to" => "{$name} has to end after it starts."]);
            }

            $hours[$day] = ['on' => $on, 'from' => $row['from'], 'to' => $row['to']];
        }

        // One date per line (or separated by commas), written like 2026-12-25.
        $blocked = collect(preg_split('/[\s,]+/', (string) ($data['blocked'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(function (string $date) {
                if (! Carbon::canBeCreatedFromFormat($date, 'Y-m-d')) {
                    throw ValidationException::withMessages(['blocked' => "\"{$date}\" isn't a date. Write dates like 2026-12-25, one per line."]);
                }

                return $date;
            })
            ->unique()->sort()->values()->all();

        $calendar->save([
            'enabled' => (bool) ($data['enabled'] ?? false),
            'minutes' => (int) $data['minutes'],
            'notice_hours' => (int) $data['notice_hours'],
            'days_ahead' => (int) $data['days_ahead'],
            'hours' => $hours,
            'blocked' => $blocked,
        ]);

        return redirect()->route('admin.bookings')->with('status', 'Booking settings saved.');
    }

    /**
     * Cancel a call and tell the person who booked it.
     */
    public function cancel(Booking $booking): RedirectResponse
    {
        $booking->update(['cancelled_at' => now()]);

        try {
            Mail::to($booking->email)->send(new BookingCancelled($booking));
            $told = "{$booking->name} has been emailed.";
        } catch (Throwable $e) {
            report($e);
            $told = "The email to {$booking->name} failed, so let them know yourself.";
        }

        return redirect()->route('admin.bookings')->with('status', "Cancelled the call on {$booking->when()}. {$told}");
    }
}
