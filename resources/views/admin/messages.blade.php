@extends('layouts.admin')

@section('title', 'Messages')

@php
    $timezone = config('portfolio.profile.timezone');
@endphp

@section('content')
    <header class="page-head">
        <div>
            <h1>Messages</h1>
            <p class="page-sub">{{ $total }} {{ Str::plural('message', $total) }} from the contact form · {{ $unread }} unread</p>
        </div>

        <nav class="seg" aria-label="Filter messages">
            <a href="{{ route('admin.messages') }}" @if (! $unreadOnly) aria-current="true" @endif>All</a>
            <a href="{{ route('admin.messages', ['show' => 'unread']) }}" @if ($unreadOnly) aria-current="true" @endif>Unread</a>
        </nav>
    </header>

    @if ($messages->isEmpty())
        <section class="panel empty">
            <h2>{{ $unreadOnly && $total ? 'Nothing unread' : 'No messages yet' }}</h2>
            <p>{{ $unreadOnly && $total ? "You've opened every message." : 'Messages sent through the contact form on the site will show up here.' }}</p>
            @if ($unreadOnly && $total)
                <a class="btn btn-ghost" href="{{ route('admin.messages') }}">Show all messages</a>
            @endif
        </section>
    @else
        <ul class="panel msg-list">
            @foreach ($messages as $message)
                <li>
                    <a @class(['msg-row', 'is-unread' => $message->read_at === null]) href="{{ route('admin.messages.show', $message) }}">
                        <span class="msg-from">
                            @if ($message->read_at === null)
                                <i class="msg-dot" title="Unread"></i><span class="sr-only">Unread:</span>
                            @endif
                            {{ $message->name }}
                        </span>
                        <span class="msg-topic">{{ $message->topicLabel() }}</span>
                        <span class="msg-snippet">{{ Str::limit(preg_replace('/\s+/', ' ', $message->message), 110) }}</span>
                        <time class="msg-when" datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->timezone($timezone)->format('j M Y, g:i A') }}</time>
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($messages->hasPages())
            <nav class="pager" aria-label="Pages">
                @if ($messages->previousPageUrl())
                    <a class="btn btn-ghost" href="{{ $messages->previousPageUrl() }}"><x-icon name="chevron-left" /> Newer</a>
                @endif
                @if ($messages->nextPageUrl())
                    <a class="btn btn-ghost pager-next" href="{{ $messages->nextPageUrl() }}">Older <x-icon name="chevron-right" /></a>
                @endif
            </nav>
        @endif
    @endif
@endsection
