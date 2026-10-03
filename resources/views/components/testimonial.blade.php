@props(['testimonial'])
<figure {{ $attributes->merge(['class' => 'quote']) }}>
    <blockquote><p>{{ $testimonial['quote'] }}</p></blockquote>
    <figcaption>{{ $testimonial['name'] }}@if ($testimonial['role'] ?? null)<span>{{ $testimonial['role'] }}</span>@endif</figcaption>
</figure>
