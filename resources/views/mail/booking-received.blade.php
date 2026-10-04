<x-mail::message>
# A call has been booked

**When:** {{ $booking->when() }}<br>
**Length:** {{ $booking->minutes }} minutes<br>
**With:** {{ $booking->name }} ({{ $booking->email }})<br>
**Call them on:** {{ $booking->phone }}

@if ($booking->notes)
<x-mail::panel>
{!! nl2br(e($booking->notes)) !!}
</x-mail::panel>
@endif

<x-mail::button :url="route('admin.bookings')">
See your bookings
</x-mail::button>

Reply to this email to answer {{ $booking->name }} directly.
</x-mail::message>
