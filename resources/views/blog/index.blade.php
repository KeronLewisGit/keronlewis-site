@extends('layouts.app')

@section('title', 'Blog: AI, Laravel and Web Development in the Caribbean | Keron Lewis')
@section('description', 'Practical articles by Keron Lewis on AI tools, Laravel and PHP, web development and tech careers, written from Port of Spain for the Caribbean and beyond.')

@push('head')
    <link rel="alternate" type="application/rss+xml" href="{{ route('blog.feed') }}" title="{{ $profile['name'] }}'s blog">
@endpush

@section('content')
    <section class="section policy blog">
        <div class="wrap">
            <header class="policy-head">
                <p class="eyebrow">Blog · {{ $count }} articles</p>
                <h1>Notes on AI, Laravel and building for the web</h1>
                <p class="lead">Practical write-ups from my day-to-day work as a developer in {{ $profile['location'] }}: AI tools that earn their place, Laravel and PHP, web performance and accessibility, and working in tech from the Caribbean.</p>
                <nav class="blog-cats" aria-label="Topics">
                    @foreach ($categories as $category => $posts)
                        <a href="#{{ $posts->first()['category_slug'] }}">{{ $category }} <span>{{ $posts->count() }}</span></a>
                    @endforeach
                </nav>
            </header>

            @foreach ($categories as $category => $posts)
                <section class="blog-group" id="{{ $posts->first()['category_slug'] }}" aria-labelledby="{{ $posts->first()['category_slug'] }}-title">
                    <h2 id="{{ $posts->first()['category_slug'] }}-title">{{ $category }}</h2>
                    <div class="blog-grid">
                        @foreach ($posts as $post)
                            @include('blog.card', ['post' => $post])
                        @endforeach
                    </div>
                </section>
            @endforeach

            <nav class="case-more" aria-label="More">
                <a class="sec-link" href="{{ route('home') }}"><x-icon name="chevron-left" /> Home</a>
                <a class="sec-link" href="{{ route('blog.feed') }}">RSS feed <x-icon name="arrow-right" /></a>
            </nav>
        </div>
    </section>
@endsection
