@extends('layouts.admin')

@section('title', "Message from {$message->name}")

@php
    $timezone = config('portfolio.profile.timezone');
    $reply = 'mailto:'.$message->email.'?subject='.rawurlencode('Re: your message to '.config('portfolio.profile.name'));
@endphp

@section('content')
    <p class="back"><a href="{{ route('admin.messages') }}"><x-icon name="chevron-left" /> All messages</a></p>

    <article class="panel msg">
        <header class="msg-head">
            <div>
                <h1>{{ $message->name }}</h1>
                <p class="page-sub"><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></p>
            </div>
            <a class="btn btn-primary" href="{{ $reply }}"><x-icon name="mail" /> Reply by email</a>
        </header>

        <dl class="msg-meta">
            <div>
                <dt>About</dt>
                <dd>{{ $message->topicLabel() }}</dd>
            </div>
            @foreach ($message->extras() as $label => $value)
                <div>
                    <dt>{{ $label }}</dt>
                    <dd>{{ $value }}</dd>
                </div>
            @endforeach
            <div>
                <dt>Received</dt>
                <dd><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->timezone($timezone)->format('j M Y, g:i A') }}</time></dd>
            </div>
            <div>
                <dt>Emailed to you</dt>
                <dd>{{ $message->emailed_at ? 'Yes' : 'No, the email failed to send' }}</dd>
            </div>
        </dl>

        <div class="msg-text">{{ $message->message }}</div>

        <footer class="msg-actions">
            <form method="POST" action="{{ route('admin.messages.unread', $message) }}">
                @csrf
                @method('PATCH')
                <button class="btn btn-ghost" type="submit">Mark as unread</button>
            </form>
            <form method="POST" action="{{ route('admin.messages.destroy', $message) }}">
                @csrf
                @method('DELETE')
                <button class="btn btn-ghost is-danger" type="submit">Delete message</button>
            </form>
        </footer>
    </article>
@endsection
