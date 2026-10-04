@extends('layouts.app')

@section('title', 'Write a testimonial · '.$profile['name'])
@section('description', 'A private page for writing a testimonial.')
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="section policy">
        <div class="wrap">
            @if ($testimonial->submitted_at)
                <header class="policy-head">
                    <p class="eyebrow">Testimonial</p>
                    <h1>Thank you, {{ $testimonial->name }}.</h1>
                    <p class="lead">Your testimonial has reached me. I'll read it and then add it to the site.</p>
                    <div class="cta-row">
                        <a class="btn btn-primary" href="{{ route('home') }}">Go to the site <x-icon name="arrow-right" /></a>
                    </div>
                </header>
            @else
                <header class="policy-head">
                    <p class="eyebrow">Testimonial</p>
                    <h1>A few words about working with me</h1>
                    <p class="lead">
                        Thanks for taking the time, {{ $testimonial->sent_to }}.
                        @if ($testimonial->projectName())
                            Tell me how the work on {{ $testimonial->projectName() }} went for you.
                        @endif
                        I read every testimonial before it goes on the site, and only your name, your role and your words are shown.
                    </p>
                </header>

                <form class="plain-form" method="POST" action="{{ route('testimonials.store', $testimonial->token) }}">
                    @csrf

                    <div class="plain-field">
                        <label for="t-name">Your name</label>
                        <input id="t-name" name="name" type="text" autocomplete="name" value="{{ old('name', $testimonial->sent_to) }}" maxlength="120" required>
                        @error('name')<p class="plain-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="plain-field">
                        <label for="t-role">Your role and company <span>optional</span></label>
                        <input id="t-role" name="role" type="text" autocomplete="organization-title" value="{{ old('role') }}" maxlength="160" placeholder="Owner, Example Ltd.">
                        @error('role')<p class="plain-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="plain-field">
                        <label for="t-quote">Your testimonial</label>
                        <textarea id="t-quote" name="quote" rows="6" maxlength="600" required>{{ old('quote') }}</textarea>
                        <p class="plain-hint">Up to 600 characters. What you needed, how the work went and what you'd tell someone thinking of hiring me.</p>
                        @error('quote')<p class="plain-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="plain-field">
                        <label class="plain-check">
                            <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
                            <span>I'm happy for this to be published on this website with my name and role.</span>
                        </label>
                        @error('consent')<p class="plain-error">{{ $message }}</p>@enderror
                    </div>

                    <button class="btn btn-primary" type="submit">Send testimonial <x-icon name="send" /></button>
                    <p class="plain-hint">What you send is stored so it can be shown on the site. <a href="{{ route('privacy') }}">Privacy</a></p>
                </form>
            @endif
        </div>
    </section>
@endsection
