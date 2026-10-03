@extends('layouts.app')

@section('title', 'Privacy · '.config('portfolio.profile.name'))
@section('description', 'What this site stores about visitors, what it uses cookies for, and how to have your data removed.')

@php
    $profile = config('portfolio.profile');
    $analytics = \App\Models\Setting::measurementId();
@endphp

@section('content')
    <article class="section policy">
        <div class="wrap">
            <header class="policy-head">
                <p class="eyebrow">Privacy</p>
                <h1>What this site knows about you</h1>
                <p class="lead">This is a personal portfolio. It doesn't show ads, sell data or build profiles of visitors. Here is everything it does store.</p>
            </header>

            <section class="policy-row">
                <h2>Who is responsible</h2>
                <div class="policy-body">
                    <p>{{ $profile['name'] }}, {{ $profile['location'] }}. For anything on this page, email <a href="mailto:{{ $profile['email'] }}">{{ $profile['email'] }}</a>.</p>
                </div>
            </section>

            <section class="policy-row">
                <h2>The contact form</h2>
                <div class="policy-body">
                    <p>If you send a message, the site stores your name, email address, the topic you picked and the message itself, and emails a copy to me. I use it only to reply to you.</p>
                    <p>Tell me if you'd like a message deleted and I'll remove it.</p>
                </div>
            </section>

            <section class="policy-row">
                <h2>Analytics</h2>
                <div class="policy-body">
                    @if ($analytics)
                        <p>With your permission, the site uses Google Analytics to count visits. It stays switched off until you choose "Allow analytics", and nothing is sent to Google if you decline or ignore the question.</p>
                        <p>If your browser sends a Global Privacy Control or Do Not Track signal, the site treats that as a no and doesn't ask.</p>
                        <p>When it is on, Google Analytics records:</p>
                        <ul>
                            <li>the pages you view</li>
                            <li>roughly where you are (country)</li>
                            <li>your device and browser type</li>
                            <li>the site that referred you</li>
                            <li>a few actions here: downloading the résumé, saving the contact card, opening a project preview and sending a contact message</li>
                        </ul>
                        <p>It sets two cookies, <code>_ga</code> and <code>_ga_*</code>, which last up to two years and let it tell one visitor from another. Google processes this data on my behalf; its own terms are in the <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Google privacy policy</a>.</p>
                        <p>You can change your mind at any time: <button class="link-btn" type="button" data-consent-open>open cookie settings</button>. Switching analytics off also deletes those cookies.</p>
                    @else
                        <p>The site doesn't use any analytics at the moment.</p>
                    @endif
                </div>
            </section>

            <section class="policy-row">
                <h2>Cookies and storage the site needs</h2>
                <div class="policy-body">
                    <ul>
                        <li>A session cookie and a security token cookie, which protect the contact form against forged submissions. They hold no personal details and expire after about two hours.</li>
                        <li>Your light or dark theme choice, your Brief or Full résumé preference and your analytics choice, kept in your browser's local storage. They never leave your device.</li>
                    </ul>
                    <p>The fonts and images are served from this site, so loading a page doesn't contact any other company.</p>
                </div>
            </section>

            <section class="policy-row">
                <h2>Server logs</h2>
                <div class="policy-body">
                    <p>Like most websites, the hosting provider may keep routine server logs (IP address, time and the page requested) for security and troubleshooting.</p>
                </div>
            </section>

            <section class="policy-row">
                <h2>Your rights</h2>
                <div class="policy-body">
                    <p>You can ask what I hold about you, ask for it to be corrected or deleted, or withdraw consent for analytics. Email me and I'll deal with it.</p>
                    <p>If you're in the EU or UK you also have the right to complain to your data protection authority.</p>
                </div>
            </section>

            <p class="policy-date">Last updated 3 October 2026.</p>
        </div>
    </article>
@endsection
