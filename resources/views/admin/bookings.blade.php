@extends('layouts.admin')

@section('title', 'Bookings')

@section('content')
    <header class="page-head">
        <div>
            <h1>Bookings</h1>
            <p class="page-sub">
                @if ($settings['enabled'])
                    Booking is open at <a href="{{ route('booking.show') }}">{{ route('booking.show') }}</a> · {{ $openSlots }} {{ Str::plural('time', $openSlots) }} on offer · {{ $upcoming->count() }} {{ Str::plural('call', $upcoming->count()) }} coming up
                @else
                    Booking is switched off, so visitors can't book yet. Set your hours below and switch it on.
                @endif
            </p>
        </div>
    </header>

    <section class="panel t-section">
        <h2>Calls coming up</h2>
        @forelse ($upcoming as $booking)
            <article class="t-item">
                <p class="b-when">{{ $booking->when() }} <span>· {{ $booking->minutes }} minutes</span></p>
                <p class="t-who">{{ $booking->name }} <span>· <a href="mailto:{{ $booking->email }}">{{ $booking->email }}</a> · <a href="tel:{{ $booking->phone }}">{{ $booking->phone }}</a></span></p>
                @if ($booking->notes)
                    <blockquote>{{ $booking->notes }}</blockquote>
                @endif
                <form class="t-actions" method="POST" action="{{ route('admin.bookings.cancel', $booking) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-ghost is-danger" type="submit">Cancel and email them</button>
                </form>
            </article>
        @empty
            <p class="none">No calls booked.</p>
        @endforelse
    </section>

    <form class="panel t-section" method="POST" action="{{ route('admin.bookings.update') }}">
        @csrf
        @method('PUT')
        <h2>When calls can be booked</h2>
        <p class="a-hint t-intro">Times are your local time ({{ $timezone }}). A day's last call ends by its closing time.</p>

        <label class="a-check">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings['enabled']))>
            Let visitors book calls on the site
        </label>

        <div class="b-hours">
            @foreach ($days as $day => $name)
                @php
                    $row = old("hours.{$day}", $settings['hours'][$day]);
                @endphp
                <div class="b-day">
                    <label class="b-day-on">
                        <input type="hidden" name="hours[{{ $day }}][on]" value="0">
                        <input type="checkbox" name="hours[{{ $day }}][on]" value="1" @checked($row['on'] ?? false)>
                        {{ $name }}
                    </label>
                    <label>From <input type="time" name="hours[{{ $day }}][from]" value="{{ $row['from'] }}" required aria-label="{{ $name }} from"></label>
                    <label>to <input type="time" name="hours[{{ $day }}][to]" value="{{ $row['to'] }}" required aria-label="{{ $name }} to"></label>
                    @error("hours.{$day}.from")<p class="a-error">{{ $message }}</p>@enderror
                    @error("hours.{$day}.to")<p class="a-error">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>

        <div class="b-grid">
            <div class="a-field">
                <label for="minutes">Length of a call</label>
                <select id="minutes" name="minutes">
                    @foreach ($slotLengths as $length)
                        <option value="{{ $length }}" @selected((int) old('minutes', $settings['minutes']) === $length)>{{ $length }} minutes</option>
                    @endforeach
                </select>
                @error('minutes')<p class="a-error">{{ $message }}</p>@enderror
            </div>
            <div class="a-field">
                <label for="notice_hours">Notice you need (hours)</label>
                <input id="notice_hours" name="notice_hours" type="text" inputmode="numeric" value="{{ old('notice_hours', $settings['notice_hours']) }}">
                <p class="a-hint">The soonest someone can book, counted from now.</p>
                @error('notice_hours')<p class="a-error">{{ $message }}</p>@enderror
            </div>
            <div class="a-field">
                <label for="days_ahead">How far ahead (days)</label>
                <input id="days_ahead" name="days_ahead" type="text" inputmode="numeric" value="{{ old('days_ahead', $settings['days_ahead']) }}">
                @error('days_ahead')<p class="a-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="a-field">
            <label for="blocked">Days off</label>
            <textarea id="blocked" name="blocked" rows="3" placeholder="2026-12-25">{{ old('blocked', implode("\n", $settings['blocked'])) }}</textarea>
            <p class="a-hint">Dates nobody can book, one per line, written like 2026-12-25.</p>
            @error('blocked')<p class="a-error">{{ $message }}</p>@enderror
        </div>

        <button class="btn btn-primary" type="submit">Save booking settings</button>
    </form>

    @if ($earlier->isNotEmpty())
        <section class="panel t-section">
            <h2>Earlier and cancelled</h2>
            @foreach ($earlier as $booking)
                <p class="t-who b-past">{{ $booking->when() }} <span>· {{ $booking->name }} · {{ $booking->email }}@if ($booking->cancelled_at) · cancelled @endif</span></p>
            @endforeach
        </section>
    @endif
@endsection
