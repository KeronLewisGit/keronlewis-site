<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A call booked through /book. Times are stored in UTC and shown in the
 * site owner's timezone.
 */
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = ['starts_at', 'minutes', 'name', 'email', 'phone', 'notes', 'cancelled_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('cancelled_at');
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->whereNull('cancelled_at')->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    public function endsAt(): Carbon
    {
        return $this->starts_at->copy()->addMinutes($this->minutes);
    }

    /** The start time as the site owner's clock shows it. */
    public function localStart(): Carbon
    {
        return $this->starts_at->copy()->timezone(config('portfolio.profile.timezone'));
    }

    /** For example "Tuesday 6 October 2026, 2:30 PM". */
    public function when(): string
    {
        return $this->localStart()->format('l j F Y, g:i A');
    }
}
