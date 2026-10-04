<x-mail::message>
# Waiting for you on the site

@if ($calls->isNotEmpty())
**Calls in the next 24 hours**

@foreach ($calls as $call)
- {{ $call->when() }} with {{ $call->name }} ({{ $call->phone }})
@endforeach

@endif
@if ($unread->isNotEmpty())
**Unread messages**

@foreach ($unread as $message)
- [{{ $message->name }}]({{ route('admin.messages.show', $message) }}), {{ Str::lower($message->topicLabel()) }}, sent {{ $message->created_at->diffForHumans() }}
@endforeach

@endif
@if ($pendingTestimonials)
**Testimonials**

{{ $pendingTestimonials }} {{ Str::plural('testimonial', $pendingTestimonials) }} waiting for [your approval]({{ route('admin.testimonials') }}).

@endif
<x-mail::button :url="route('admin.messages')">
Open the admin
</x-mail::button>
</x-mail::message>
