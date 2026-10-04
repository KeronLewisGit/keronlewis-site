<x-mail::message>
# New message from the website

**From:** {{ $contactMessage->name }} ({{ $contactMessage->email }})<br>
**About:** {{ $contactMessage->topicLabel() }}<br>
@foreach ($contactMessage->extras() as $label => $value)
**{{ $label }}:** {{ $value }}<br>
@endforeach
**Sent:** {{ $contactMessage->created_at->timezone(config('portfolio.profile.timezone'))->format('j M Y, g:i a') }}

<x-mail::panel>
{!! nl2br(e($contactMessage->message)) !!}
</x-mail::panel>

Reply to this email to answer {{ $contactMessage->name }} directly.
</x-mail::message>
