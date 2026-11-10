@extends('layouts.app')

@section('title', $post['seo_title'])
@section('description', $post['description'])
@section('share', "blog-{$post['slug']}")

@push('head')
    <meta property="article:published_time" content="{{ $post['date']->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ ($post['updated'] ?? $post['date'])->toIso8601String() }}">
    <meta property="article:section" content="{{ $post['category'] }}">
    <link rel="alternate" type="application/rss+xml" href="{{ route('blog.feed') }}" title="{{ $profile['name'] }}'s blog">
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <article class="section policy post">
        <div class="wrap">
            <header class="policy-head">
                <p class="eyebrow"><a href="{{ route('blog.index') }}">Blog</a> · <a href="{{ route('blog.index') }}#{{ $post['category_slug'] }}">{{ $post['category'] }}</a></p>
                <h1>{{ $post['title'] }}</h1>
                <p class="lead">{{ $post['description'] }}</p>
                <p class="post-byline">
                    <span>By <a href="{{ route('home') }}#about">{{ $profile['name'] }}</a></span>
                    <time datetime="{{ $post['date']->toDateString() }}">{{ $post['date']->format('j F Y') }}</time>
                    @if ($post['updated'])
                        <span>Updated <time datetime="{{ $post['updated']->toDateString() }}">{{ $post['updated']->format('j F Y') }}</time></span>
                    @endif
                    <span>{{ $post['minutes'] }} min read</span>
                </p>
            </header>

            <div class="post-layout">
                @if (count($post['toc']) > 2)
                    <nav class="post-toc" aria-label="In this article">
                        <p class="eyebrow">In this article</p>
                        <ol>
                            @foreach ($post['toc'] as $item)
                                <li><a href="#{{ $item['id'] }}">{{ $item['text'] }}</a></li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                <div class="post-body">
                    {!! $post['html'] !!}

                    <aside class="post-author">
                        <p class="eyebrow">About the author</p>
                        <p>I'm {{ $profile['name'] }}, a {{ strtolower($profile['title']) }} in {{ $profile['location'] }}, building websites, WordPress stores and Laravel apps since 2016. If you want a hand with something like this, <a href="{{ route('home') }}#contact">get in touch</a> or look at <a href="{{ route('home') }}#work">the work I've done</a>.</p>
                    </aside>
                </div>
            </div>

            @if ($related->isNotEmpty())
                <section class="blog-group" aria-labelledby="related-title">
                    <h2 id="related-title">Keep reading</h2>
                    <div class="blog-grid">
                        @foreach ($related as $item)
                            @include('blog.card', ['post' => $item])
                        @endforeach
                    </div>
                </section>
            @endif

            <nav class="case-more" aria-label="More">
                <a class="sec-link" href="{{ route('blog.index') }}"><x-icon name="chevron-left" /> All articles</a>
                <a class="sec-link" href="{{ route('home') }}#contact">Get in touch <x-icon name="arrow-right" /></a>
            </nav>
        </div>
    </article>
@endsection
