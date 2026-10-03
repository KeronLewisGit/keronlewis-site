@extends('layouts.app')

@section('title', 'Page not found · Keron Lewis')

@section('content')
    <section class="section not-found">
        <div class="wrap">
            <p class="eyebrow">404</p>
            <h1>That page isn't here.</h1>
            <p class="lead">The link may be old, or the address might have a typo in it.</p>
            <div class="cta-row">
                <a class="btn btn-primary" href="{{ route('home') }}">Back to the home page</a>
                <a class="btn btn-ghost" href="{{ route('resume') }}">Read my résumé</a>
            </div>
        </div>
    </section>
@endsection
