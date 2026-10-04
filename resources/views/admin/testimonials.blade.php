@extends('layouts.admin')

@section('title', 'Testimonials')

@php
    $timezone = config('portfolio.profile.timezone');
@endphp

@section('content')
    <header class="page-head">
        <div>
            <h1>Testimonials</h1>
            <p class="page-sub">{{ $pending->count() }} waiting for approval · {{ $approved->count() }} on the site · {{ $unanswered->count() }} {{ Str::plural('link', $unanswered->count()) }} not filled in yet</p>
        </div>
    </header>

    <div class="connect-grid">
        <form class="panel" method="POST" action="{{ route('admin.testimonials.store') }}">
            @csrf
            <h2>Create a private link</h2>
            <p class="a-hint t-intro">Each link is for one client and takes one testimonial. Only someone who has the link can open the page.</p>

            <div class="a-field">
                <label for="sent_to">Client's name</label>
                <input id="sent_to" name="sent_to" type="text" value="{{ old('sent_to') }}" maxlength="120" required>
                <p class="a-hint">They'll see this on the page, and it's filled in as their name for them to change.</p>
                @error('sent_to')<p class="a-error">{{ $message }}</p>@enderror
            </div>

            <div class="a-field">
                <label for="project_slug">Project it's about</label>
                <select id="project_slug" name="project_slug">
                    <option value="">No particular project</option>
                    @foreach ($projects as $slug => $name)
                        <option value="{{ $slug }}" @selected(old('project_slug') === $slug)>{{ $name }}</option>
                    @endforeach
                </select>
                <p class="a-hint">A testimonial tied to a project shows on that project's card and case study. The rest show under "What clients say".</p>
                @error('project_slug')<p class="a-error">{{ $message }}</p>@enderror
            </div>

            <button class="btn btn-primary" type="submit">Create link</button>
        </form>

        <section class="panel">
            <h2>Links not filled in yet</h2>
            @forelse ($unanswered as $testimonial)
                <div class="t-item">
                    <p class="t-who">{{ $testimonial->sent_to }}@if ($testimonial->projectName()) <span>· {{ $testimonial->projectName() }}</span>@endif <span>· created {{ $testimonial->created_at->timezone($timezone)->format('j M Y') }}</span></p>
                    <div class="t-link">
                        <input type="text" value="{{ $testimonial->link() }}" readonly aria-label="Private link for {{ $testimonial->sent_to }}">
                        <button class="btn btn-ghost" type="button" data-copy="{{ $testimonial->link() }}">Copy</button>
                    </div>
                    <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}">
                        @csrf
                        @method('DELETE')
                        <button class="link-danger" type="submit">Cancel this link</button>
                    </form>
                </div>
            @empty
                <p class="none">No links waiting. Create one and send it to a client.</p>
            @endforelse
        </section>
    </div>

    <section class="panel t-section">
        <h2>Waiting for your approval</h2>
        @forelse ($pending as $testimonial)
            <article class="t-item">
                <blockquote>{{ $testimonial->quote }}</blockquote>
                <p class="t-who">{{ $testimonial->name }}@if ($testimonial->role) <span>· {{ $testimonial->role }}</span>@endif @if ($testimonial->projectName()) <span>· about {{ $testimonial->projectName() }}</span>@endif <span>· sent {{ $testimonial->submitted_at->timezone($timezone)->format('j M Y') }}</span></p>
                <div class="t-actions">
                    <form method="POST" action="{{ route('admin.testimonials.approve', $testimonial) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-primary" type="submit">Approve and publish</button>
                    </form>
                    <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-ghost is-danger" type="submit">Delete</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="none">Nothing waiting. A testimonial appears here when a client fills in their link.</p>
        @endforelse
    </section>

    <section class="panel t-section">
        <h2>On the site</h2>
        @forelse ($approved as $testimonial)
            <article class="t-item">
                <blockquote>{{ $testimonial->quote }}</blockquote>
                <p class="t-who">{{ $testimonial->name }}@if ($testimonial->role) <span>· {{ $testimonial->role }}</span>@endif @if ($testimonial->projectName()) <span>· about {{ $testimonial->projectName() }}</span>@endif <span>· published {{ $testimonial->approved_at->timezone($timezone)->format('j M Y') }}</span></p>
                <div class="t-actions">
                    <form method="POST" action="{{ route('admin.testimonials.unpublish', $testimonial) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-ghost" type="submit">Take off the site</button>
                    </form>
                    <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-ghost is-danger" type="submit">Delete</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="none">No testimonials published yet.</p>
        @endforelse
    </section>
@endsection
