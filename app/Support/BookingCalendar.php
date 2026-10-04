<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Works out which call slots can still be booked, from the hours set in the
 * admin area and the calls already in the diary. Hours are in the site
 * owner's timezone; slots are handed around in UTC.
 */
class BookingCalendar
{
    public const DAYS = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];

    public const SLOT_LENGTHS = [15, 30, 45, 60];

    private ?array $settings = null;

    /**
     * The saved settings, with defaults for anything not set yet.
     *
     * @return array{enabled: bool, minutes: int, notice_hours: int, days_ahead: int, hours: array<string, array{on: bool, from: string, to: string}>, blocked: list<string>}
     */
    public function settings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $saved = json_decode((string) Setting::cached('booking'), true) ?: [];
        $weekday = ['on' => true, 'from' => '09:00', 'to' => '17:00'];
        $weekend = ['on' => false, 'from' => '09:00', 'to' => '13:00'];

        return $this->settings = [
            'enabled' => (bool) ($saved['enabled'] ?? false),
            'minutes' => (int) ($saved['minutes'] ?? 30),
            'notice_hours' => (int) ($saved['notice_hours'] ?? 12),
            'days_ahead' => (int) ($saved['days_ahead'] ?? 14),
            'hours' => collect(self::DAYS)->map(fn (string $name, string $day) => ($saved['hours'][$day] ?? []) + (in_array($day, ['sat', 'sun']) ? $weekend : $weekday))->all(),
            'blocked' => array_values($saved['blocked'] ?? []),
        ];
    }

    public function save(array $settings): void
    {
        Setting::write('booking', json_encode($settings));
        $this->settings = null;
    }

    public function isEnabled(): bool
    {
        return $this->settings()['enabled'];
    }

    public function timezone(): string
    {
        return config('portfolio.profile.timezone');
    }

    /**
     * Open slots for the coming days, grouped by local date ("2026-10-06" => [Carbon, ...]).
     * Days with nothing free are left out.
     *
     * @return Collection<string, Collection<int, Carbon>>
     */
    public function openSlots(): Collection
    {
        $settings = $this->settings();

        if (! $settings['enabled']) {
            return collect();
        }

        $earliest = now()->addHours($settings['notice_hours']);
        $today = now($this->timezone())->startOfDay();
        $taken = Booking::active()
            ->where('starts_at', '>=', now()->subDay())
            ->get()
            ->map(fn (Booking $booking) => [$booking->starts_at, $booking->endsAt()]);

        return collect(range(0, $settings['days_ahead']))
            ->mapWithKeys(function (int $offset) use ($settings, $today, $earliest, $taken) {
                $date = $today->copy()->addDays($offset);
                $hours = $settings['hours'][strtolower($date->format('D'))];

                if (! $hours['on'] || in_array($date->toDateString(), $settings['blocked'])) {
                    return [];
                }

                $slots = collect();
                $slot = $date->copy()->setTimeFromTimeString($hours['from']);
                $close = $date->copy()->setTimeFromTimeString($hours['to']);

                while ($slot->copy()->addMinutes($settings['minutes'])->lte($close)) {
                    $end = $slot->copy()->addMinutes($settings['minutes']);
                    $clash = $taken->contains(fn (array $booked) => $slot->lt($booked[1]) && $end->gt($booked[0]));

                    if ($slot->gte($earliest) && ! $clash) {
                        $slots->push($slot->copy()->utc());
                    }

                    $slot = $end;
                }

                return $slots->isEmpty() ? [] : [$date->toDateString() => $slots];
            });
    }

    /**
     * Whether a slot (given as the UTC value the form submits) can still be booked.
     */
    public function isOpen(string $slot): bool
    {
        return $this->openSlots()->flatten()->contains(fn (Carbon $open) => $open->toIso8601ZuluString() === $slot);
    }
}
