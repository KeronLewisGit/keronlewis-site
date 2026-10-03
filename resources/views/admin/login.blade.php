@extends('layouts.admin')

@section('title', 'Sign in')

@section('content')
    <form class="panel login" method="POST" action="{{ route('admin.login.store') }}">
        @csrf
        <h1>Sign in</h1>

        <div class="a-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            @error('email')<p class="a-error">{{ $message }}</p>@enderror
        </div>

        <div class="a-field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            @error('password')<p class="a-error">{{ $message }}</p>@enderror
        </div>

        <label class="a-check"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device</label>

        <button class="btn btn-primary" type="submit">Sign in</button>
    </form>
@endsection
