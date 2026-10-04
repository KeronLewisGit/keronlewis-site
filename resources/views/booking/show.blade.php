@extends('layouts.app')

@section('title', 'Book a call with '.$profile['name'].' | Web Developer in Trinidad & Tobago')
@section('description', 'Pick a time for a call about your website, online store or web app. Calls are by phone and times are shown in Trinidad and Tobago time.')
@unless ($enabled)
    @section('robots', 'noindex, follow')
@endunless

@php
    $offset = now($timezone)->format('P') === '-04:00' ? 'UTC−4' : 'UTC'.now($timezone)->format('P');
@endphp

@section('content')
    <section class="section policy">
        <div class="wrap">
            @if ($booked)
                <header class="policy-head">
                    <p class="eyebrow">Book a call</p>
                    <h1>You're booked.</h1>
                    <p class="lead">I'll call you on {{ $booked->phone }} on <strong>{{ $booked->when() }}</strong> (Trinidad &amp; Tobago time). A confirmation is on its way to {{ $booked->email }}.</p>
                    <div class="cta-row">
                        <a class="btn btn-primary" href="{{ route('home') }}">Back to the site <x-icon name="arrow-right" /></a>
                    </div>
                </header>
            @elseif (! $enabled || $days->isEmpty())
                <header class="policy-head">
                    <p class="eyebrow">Book a call</p>
                    <h1>{{ $enabled ? 'No times are free right now' : "Online booking isn't open at the moment" }}</h1>
                    <p class="lead">Send me a message instead and I'll come back to you with a time, or call me on <a href="tel:{{ $profile['phone_e164'] }}">{{ $profile['phone'] }}</a>.</p>
                    <div class="cta-row">
                        <a class="btn btn-primary" href="{{ route('home') }}#contact">Send a message <x-icon name="arrow-right" /></a>
                    </div>
                </header>
            @else
                <header class="policy-head">
                    <p class="eyebrow">Book a call</p>
                    <h1>Book a call with me</h1>
                    <p class="lead">Pick a time and I'll phone you to talk through your website, store or web app. Calls last {{ $minutes }} minutes. Times are Trinidad &amp; Tobago time ({{ $offset }}).</p>
                </header>

                <form class="plain-form book-form" method="POST" action="{{ route('booking.store') }}">
                    @csrf

                    <fieldset class="plain-field">
                        <legend>Pick a time</legend>
                        @error('slot')<p class="plain-error">{{ $message }}</p>@enderror
                        {{-- One day is open at a time to keep the page short: the first, or the one already picked. --}}
                        @php
                            $picked = $days->search(fn ($slots) => $slots->contains(fn ($slot) => $slot->toIso8601ZuluString() === old('slot'))) ?: $days->keys()->first();
                        @endphp
                        @foreach ($days as $date => $slots)
                            <details class="slot-day" name="day" @if ($date === $picked) open @endif>
                                <summary>{{ \Illuminate\Support\Carbon::parse($date)->format('l j F') }} <span>{{ $slots->count() }} {{ Str::plural('time', $slots->count()) }}</span></summary>
                                <div class="slot-row">
                                    @foreach ($slots as $slot)
                                        <label class="slot">
                                            <input type="radio" name="slot" value="{{ $slot->toIso8601ZuluString() }}" @checked(old('slot') === $slot->toIso8601ZuluString())>
                                            <span>{{ $slot->copy()->timezone($timezone)->format('g:i A') }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </fieldset>

                    <div class="plain-row">
                        <div class="plain-field">
                            <label for="b-name">Your name</label>
                            <input id="b-name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" maxlength="120" required>
                            @error('name')<p class="plain-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="plain-field">
                            <label for="b-email">Email</label>
                            <input id="b-email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" maxlength="190" required>
                            @error('email')<p class="plain-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="plain-field">
                        <label for="b-phone">Number I should call</label>
                        <input id="b-phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}" maxlength="40" required>
                        @error('phone')<p class="plain-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="plain-field">
                        <label for="b-notes">What's the call about? <span>optional</span></label>
                        <textarea id="b-notes" name="notes" rows="4" maxlength="1000">{{ old('notes') }}</textarea>
                        @error('notes')<p class="plain-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Honeypot. Real visitors never see or fill this. --}}
                    <div class="hp" aria-hidden="true">
                        <label for="b-website">Leave this empty</label>
                        <input id="b-website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <button class="btn btn-primary" type="submit">Book the call <x-icon name="arrow-right" /></button>
                    <p class="plain-hint">Your details are stored so I can make the call, and a confirmation is emailed to you. <a href="{{ route('privacy') }}">Privacy</a></p>
                </form>
            @endif
        </div>
    </section>
@endsection
