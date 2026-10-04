<x-mail::message>
# Your call has been cancelled

I've had to cancel our call on {{ $booking->when() }} (Trinidad &amp; Tobago time). I'm sorry for the change.

<x-mail::button :url="route('booking.show')">
Pick another time
</x-mail::button>

Or reply to this email and we'll find a time that works.

{{ $profile['name'] }}<br>
{{ $profile['phone'] }}
</x-mail::message>
