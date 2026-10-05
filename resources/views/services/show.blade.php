@extends('layouts.app')

@section('title', $page['seo_title'])
@section('description', $page['summary'])
@section('share', "service-{$service['slug']}")

@push('head')
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <article class="section policy case">
        <div class="wrap">
            <header class="policy-head">
                <p class="eyebrow">Services · {{ $profile['location'] }}</p>
                <h1>{{ $page['heading'] }}</h1>
                <p class="lead">{{ $page['summary'] }}</p>
                <div class="cta-row">
                    <a class="btn btn-primary" href="{{ route('home') }}#contact">Talk to me about a project <x-icon name="arrow-right" /></a>
                    @if ($bookingOpen)
                        <a class="btn btn-ghost" href="{{ route('booking.show') }}"><x-icon name="phone" /> Book a call</a>
                    @endif
                    <a class="btn btn-ghost" href="{{ route('home') }}#work">See my work</a>
                </div>
            </header>

            @foreach ($page['sections'] as $section)
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

            @if ($page['faq'] ?? null)
                <section class="policy-row">
                    <h2>Common questions</h2>
                    <div class="policy-body faq">
                        @foreach ($page['faq'] as $item)
                            <h3>{{ $item['question'] }}</h3>
                            <p>{{ $item['answer'] }}</p>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($testimonials->isNotEmpty())
                <section class="policy-row">
                    <h2>What clients say</h2>
                    <div class="policy-body">
                        <div class="quotes-grid">
                            @foreach ($testimonials as $testimonial)
                                <x-testimonial :testimonial="$testimonial" />
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if ($projects->isNotEmpty())
                <section class="policy-row">
                    <h2>Related work</h2>
                    <div class="policy-body">
                        <ul>
                            @foreach ($projects as $project)
                                <li>
                                    @if ($project['case_url'])
                                        <a href="{{ $project['case_url'] }}">{{ $project['name'] }}</a>
                                    @else
                                        <a href="{{ $project['url'] }}" target="_blank" rel="noopener">{{ $project['name'] }}</a>
                                    @endif
                                    · {{ $project['summary'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>
            @endif

            <section class="policy-row">
                <h2>Other services</h2>
                <div class="policy-body">
                    <ul>
                        @foreach ($others as $other)
                            <li><a href="{{ $other['url'] }}">{{ $other['title'] }}</a> · {{ $other['text'] }}</li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <nav class="case-more" aria-label="More">
                <a class="sec-link" href="{{ route('home') }}#services"><x-icon name="chevron-left" /> All services</a>
                <a class="sec-link" href="{{ route('home') }}#contact">Get in touch <x-icon name="arrow-right" /></a>
            </nav>
        </div>
    </article>
@endsection
