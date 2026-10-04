@extends('layouts.app')

@section('title', "{$project['name']} case study | {$profile['name']}")
@section('description', $study['summary'])
@section('share', "work-{$project['slug']}")

@section('content')
    <article class="section policy case">
        <div class="wrap">
            <header class="policy-head">
                <p class="eyebrow">Case study · {{ $project['category'] }}</p>
                <h1>{{ $study['title'] }}</h1>
                <p class="lead">{{ $study['summary'] }}</p>
                <div class="cta-row">
                    <a class="btn btn-primary" href="{{ $project['url'] }}" target="_blank" rel="noopener">Visit the live site <x-icon name="arrow-up-right" /></a>
                    <a class="btn btn-ghost" href="{{ route('home') }}#contact">Talk to me about a similar project</a>
                </div>
            </header>

            @if ($project['image'])
                <figure class="case-shot">
                    <span class="shot-bar"><i></i><i></i><i></i><span class="shot-host">{{ $project['host'] }}</span></span>
                    <div class="case-shot-view" tabindex="0" role="group" aria-label="Full-page screenshot of {{ $project['name'] }}. Scroll to see the whole page.">
                        <img src="{{ $project['image'] }}" width="{{ $project['image_width'] }}" height="{{ $project['image_height'] }}" decoding="async" alt="Full-page screenshot of the {{ $project['name'] }} website">
                    </div>
                </figure>
            @endif

            @foreach ($study['sections'] as $section)
                <section class="policy-row">
                    <h2>{{ $section['heading'] }}</h2>
                    <div class="policy-body">
                        @foreach ($section['body'] ?? [] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                        @if ($section['points'] ?? null)
                            <ul>
                                @foreach ($section['points'] as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>
            @endforeach

            <section class="policy-row">
                <h2>Built with</h2>
                <div class="policy-body">
                    <ul class="tags">
                        @foreach ($project['stack'] as $tag)
                            <li>{{ $tag }}</li>
                        @endforeach
                    </ul>
                </div>
            </section>

            @if ($project['testimonial'] ?? null)
                <section class="policy-row">
                    <h2>What the client said</h2>
                    <div class="policy-body">
                        <x-testimonial :testimonial="$project['testimonial']" :omit="$project['name']" />
                    </div>
                </section>
            @endif

            <nav class="case-more" aria-label="More work">
                <a class="sec-link" href="{{ route('home') }}#work"><x-icon name="chevron-left" /> All selected work</a>
                @foreach ($others as $other)
                    <a class="sec-link" href="{{ $other['case_url'] }}">{{ $other['name'] }} case study <x-icon name="arrow-right" /></a>
                @endforeach
            </nav>
        </div>
    </article>
@endsection
