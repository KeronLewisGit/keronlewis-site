<x-mail::message>
# Your call is booked

**When:** {{ $booking->when() }} (Trinidad &amp; Tobago time)<br>
**Length:** {{ $booking->minutes }} minutes<br>
**I'll call you on:** {{ $booking->phone }}

To change the time or cancel, reply to this email.

{{ $profile['name'] }}<br>
{{ $profile['title'] }}, {{ $profile['location'] }}<br>
{{ $profile['phone'] }}

<x-mail::subcopy>
You're receiving this because this address was used to book a call at {{ route('booking.show') }}. If that wasn't you, reply to let me know.
</x-mail::subcopy>
</x-mail::message>
