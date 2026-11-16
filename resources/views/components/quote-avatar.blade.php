@props(['name'])
@php
    $initials = Str::of($name)->explode(' ')->filter()->take(2)->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))->implode('');
@endphp
<span {{ $attributes->class(['quote-avatar']) }} aria-hidden="true">{{ $initials }}</span>
