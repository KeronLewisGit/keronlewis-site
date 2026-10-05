@props(['testimonial', 'omit' => null])
@php
    // The line under the name is left out when it would only repeat the name, or the project the quote already sits under.
    $detail = $testimonial['detail'] ?? ($testimonial['role'] ?? null);
    $detail = in_array($detail, [$testimonial['name'], $omit], true) ? null : $detail;
    $highlights = $testimonial['highlights'] ?? [];
    $initials = Str::of($testimonial['name'])->explode(' ')->filter()->take(2)->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))->implode('');

    // Escape the client's words first, then mark the standout phrases, so nothing they typed is ever treated as HTML.
    $words = e($testimonial['quote']);
    foreach ($highlights as $phrase) {
        $words = preg_replace('/'.preg_quote(e($phrase), '/').'/iu', '<mark>$0</mark>', $words, 1);
    }
@endphp
<figure {{ $attributes->class(['quote']) }}>
    @if ($highlights)
        <p class="quote-pull">{{ Str::ucfirst($highlights[0]) }}</p>
    @endif
    {{-- quotes.js adds a "Read more" button when the text runs past a few lines. --}}
    <blockquote data-quote><p>{!! nl2br($words) !!}</p></blockquote>
    <figcaption>
        <span class="quote-avatar" aria-hidden="true">{{ $initials }}</span>
        <span class="quote-by"><strong>{{ $testimonial['name'] }}</strong>@if ($detail) <span>{{ $detail }}</span>@endif</span>
    </figcaption>
</figure>
