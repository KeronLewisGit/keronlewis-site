<x-mail::message>
# Thanks for getting in touch

Your message about "{{ Str::lower($contactMessage->topicLabel()) }}" has reached me, and I'll reply to this address as soon as I can.

If it's urgent, call or message me on {{ $profile['phone'] }}.

{{ $profile['name'] }}<br>
{{ $profile['title'] }}, {{ $profile['location'] }}

<x-mail::subcopy>
You're receiving this because this address was entered in the contact form at {{ url('/') }}. If that wasn't you, you can ignore this email.
</x-mail::subcopy>
</x-mail::message>
