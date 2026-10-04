@extends('layouts.app')

@php
    [$taglineBefore, $taglineAfter] = explode($profile['tagline_mark'], $profile['tagline'], 2) + [1 => ''];
    $localTime = now()->timezone($profile['timezone']);
@endphp

@section('content')
    {{-- HERO --}}
    <section class="hero" id="top">
        <canvas class="hero-dots" data-dots aria-hidden="true"></canvas>
        <div class="wrap hero-grid">
            <div class="hero-copy">
                {{-- The role and place are part of the h1 so search engines read them as the page's subject. --}}
                <h1><span class="eyebrow">{{ $profile['title'] }} · {{ $profile['location'] }}</span> {{ $profile['name'] }}</h1>
                <p class="thesis">
                    {{ $taglineBefore }}<span class="marker">{{ $profile['tagline_mark'] }}<svg viewBox="0 0 200 24" preserveAspectRatio="none" aria-hidden="true"><path d="M4 16 C 50 6, 150 6, 196 14"/></svg></span>{{ $taglineAfter }}
                </p>
                <p class="lead">{{ $profile['lead'] }}</p>
                <div class="cta-row">
                    <a class="btn btn-primary" href="#contact">Get in touch <x-icon name="arrow-right" /></a>
                    <a class="btn btn-ghost" href="{{ route('resume') }}"><x-icon name="file" /> Read my résumé</a>
                    <button class="copy-link" type="button" data-copy="{{ $profile['email'] }}" data-copy-label="Email address copied">
                        <x-icon name="copy" /> {{ $profile['email'] }}
                    </button>
                </div>
            </div>

            <aside class="now-card" aria-label="What I'm doing now">
                <div class="now-head">
                    <span class="mono">now</span>
                    <span class="avail"><i></i>{{ $profile['availability'] }}</span>
                </div>
                <dl>
                    <div>
                        <dt>Local time</dt>
                        <dd>
                            <span data-clock data-tz="{{ $profile['timezone'] }}">{{ $localTime->format('g:i A') }}</span>
                            <small>{{ $profile['city'] }}, UTC{{ $localTime->format('P') === '-04:00' ? '−4' : $localTime->format('P') }}</small>
                        </dd>
                    </div>
                    @foreach ($profile['now'] as $item)
                        <div>
                            <dt>{{ $item['label'] }}</dt>
                            <dd>{{ $item['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </aside>
        </div>
    </section>

    {{-- WORK --}}
    <section class="section" id="work">
        <div class="wrap">
            <header class="sec-head reveal">
                <span class="num">01</span>
                <h2>Selected work</h2>
            </header>
            <p class="subhead reveal">
                Nine live sites I designed and built, most of them for clients and a few for my own businesses. They run on WordPress and Elementor, and a few have custom tools I wrote on top, like the paid SEO audit on my studio's site and the drinks calculator for Mixers Anonymous. My back-end and automation work is mostly internal to the companies I've worked for, so that's covered under <a href="#experience">Experience</a>.
            </p>

            <div class="filters reveal" role="group" aria-label="Filter projects">
                <button class="filter is-on" type="button" data-filter="all" aria-pressed="true">All <span>{{ $projects->count() }}</span></button>
                @foreach ($projectGroups as $key => $label)
                    <button class="filter" type="button" data-filter="{{ $key }}" aria-pressed="false">
                        {{ $label }} <span>{{ $projects->filter(fn ($p) => in_array($key, $p['groups']))->count() }}</span>
                    </button>
                @endforeach
            </div>

            <div class="work-grid" data-work-grid>
                @foreach ($projects as $project)
                    <article class="card reveal" data-project="{{ $project['slug'] }}" data-groups="{{ implode(' ', $project['groups']) }}" style="view-transition-name: card-{{ $project['slug'] }}">
                        <a class="shot" href="{{ $project['url'] }}" target="_blank" rel="noopener" data-project-open aria-label="Preview {{ $project['name'] }}">
                            <span class="shot-bar"><i></i><i></i><i></i><span class="shot-host">{{ $project['host'] }}</span></span>
                            <span class="shot-view">
                                @if ($project['image'])
                                    <img src="{{ $project['thumb'] ?? $project['image'] }}"
                                         @if ($project['thumb']) srcset="{{ $project['thumb'] }} {{ $project['thumb_width'] }}w, {{ $project['image'] }} {{ $project['image_width'] }}w" sizes="(max-width: 640px) calc(100vw - 42px), (max-width: 1000px) calc(50vw - 42px), 350px" @endif
                                         width="{{ $project['image_width'] }}" height="{{ $project['image_height'] }}" loading="lazy" decoding="async" alt="{{ $project['name'] }} website, a {{ Str::lower($project['category']) }} site built on WordPress by {{ $profile['name'] }}"
                                         style="--pan: {{ round(max(1.2, ($project['image_height'] / $project['image_width'] * 350 - 220) / 200), 1) }}s">
                                @else
                                    <span class="shot-empty">{{ $project['host'] }}</span>
                                @endif
                            </span>
                            <span class="shot-hint"><x-icon name="expand" /> Preview</span>
                        </a>
                        <div class="card-body">
                            <span class="cat">{{ $project['category'] }}</span>
                            <h3>{{ $project['name'] }}</h3>
                            <p>{{ $project['summary'] }}</p>
                            <ul class="tags">
                                @foreach ($project['stack'] as $tag)
                                    <li>{{ $tag }}</li>
                                @endforeach
                            </ul>
                            @if ($project['testimonial'] ?? null)
                                <x-testimonial :testimonial="$project['testimonial']" />
                            @endif
                            <div class="card-links">
                                @if ($project['case_url'])
                                    <a class="visit" href="{{ $project['case_url'] }}">Read the case study <x-icon name="arrow-right" /></a>
                                @endif
                                <a class="visit" href="{{ $project['url'] }}" target="_blank" rel="noopener">Visit site <x-icon name="arrow-up-right" /></a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- EXPERIENCE --}}
    <section class="section" id="experience">
        <div class="wrap">
            <header class="sec-head reveal">
                <span class="num">02</span>
                <h2>Experience</h2>
                <a class="sec-link" href="{{ route('resume') }}">Full résumé <x-icon name="arrow-right" /></a>
            </header>

            <ol class="xp">
                @foreach ($experience as $role)
                    <li class="xp-row reveal">
                        <div class="xp-when">
                            {{ $role['period'] }}
                            @if ($status = collect([$role['type'], $role['end'] ? null : 'Current'])->filter()->implode(' · '))
                                <span class="xp-now">{{ $status }}</span>
                            @endif
                        </div>
                        <div class="xp-body">
                            <h3>{{ $role['role'] }}</h3>
                            <p class="org">{{ $role['org'] }}@if ($role['org_note'])<span> · {{ $role['org_note'] }}</span>@endif</p>
                            <p>{{ $role['summary'] }}</p>
                            @if (count($role['bullets']) > 1)
                                <details class="xp-more">
                                    <summary>The details <x-icon name="chevron-down" /></summary>
                                    <ul>
                                        @foreach ($role['bullets'] as $bullet)
                                            <li>{{ $bullet }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- SKILLS --}}
    <section class="section" id="skills">
        <div class="wrap">
            <header class="sec-head reveal">
                <span class="num">03</span>
                <h2>What I work with</h2>
            </header>
            <p class="subhead reveal">Pick a skill to see which roles I've used it in.</p>

            <div class="skills-grid">
                @foreach ($skills as $group => $names)
                    <div class="skillset reveal">
                        <h3>{{ $group }}</h3>
                        <ul class="chips">
                            @foreach ($names as $name)
                                <li>
                                    @if ($roles = $skillIndex[$name] ?? null)
                                        <a class="chip" href="{{ route('resume', ['skill' => $name]) }}#experience" title="Used in {{ count($roles) }} {{ Str::plural('role', count($roles)) }}">
                                            {{ $name }} <span>{{ count($roles) }}</span>
                                        </a>
                                    @else
                                        <span class="chip">{{ $name }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- SERVICES --}}
    <section class="section" id="services">
        <div class="wrap">
            <header class="sec-head reveal">
                <span class="num">04</span>
                <h2>Work I take on</h2>
            </header>
            <p class="subhead reveal">I'm available for full-time web developer roles and for contracts, remote or on site in Trinidad &amp; Tobago. Freelance projects run through my studio, Code Canvas.</p>

            <div class="services">
                @foreach ($services as $service)
                    <article class="service reveal">
                        <h3>{{ $service['title'] }}</h3>
                        <p>{{ $service['text'] }}</p>
                    </article>
                @endforeach
            </div>

            <p class="services-cta reveal">
                <a class="btn btn-primary" href="#contact">Talk to me about a role or project <x-icon name="arrow-right" /></a>
                <a class="btn btn-ghost" href="{{ route('resume.pdf') }}"><x-icon name="download" /> Download my résumé</a>
            </p>
        </div>
    </section>

    {{-- ABOUT --}}
    <section class="section" id="about">
        <div class="wrap">
            <header class="sec-head reveal">
                <span class="num">05</span>
                <h2>About</h2>
            </header>

            <div class="about-grid">
                <div class="about reveal">
                    @foreach ($profile['about'] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>

                <dl class="facts reveal">
                    <div class="fact">
                        <dt>Based in</dt>
                        <dd>{{ $profile['location'] }}<small>{{ $profile['remote'] }}</small></dd>
                    </div>
                    <div class="fact">
                        <dt>Education</dt>
                        @foreach ($education as $item)
                            <dd>{{ $item['award'] }}<small>{{ $item['school'] }} · {{ $item['status'] }}</small></dd>
                        @endforeach
                    </div>
                    <div class="fact">
                        <dt>Certifications</dt>
                        <dd>
                            @foreach ($certifications as $cert)
                                <small class="cert">{{ $cert['name'] }} · {{ $cert['issuer'] }}{{ $cert['year'] ? ', '.$cert['year'] : '' }}</small>
                            @endforeach
                        </dd>
                    </div>
                    <div class="fact">
                        <dt>Currently</dt>
                        <dd>{{ $profile['availability'] }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- CONTACT --}}
    <section class="section" id="contact">
        <div class="wrap reveal">
            <div class="contact-card">
                <div class="contact-copy">
                    <p class="eyebrow"><span class="num">06</span> Contact</p>
                    <h2>Get in touch</h2>
                    <p>I'm open to full-stack and web development roles, full-time or contract, remote or in Trinidad. I also take on client projects through Code Canvas. Use the form, or email me directly.</p>

                    <ul class="contact-links">
                        <li>
                            <a href="mailto:{{ $profile['email'] }}"><x-icon name="mail" /> {{ $profile['email'] }}</a>
                            <button class="mini-copy" type="button" data-copy="{{ $profile['email'] }}" data-copy-label="Email address copied" aria-label="Copy email address"><x-icon name="copy" /></button>
                        </li>
                        <li><a href="tel:{{ $profile['phone_e164'] }}"><x-icon name="phone" /> {{ $profile['phone'] }}</a></li>
                        <li><a href="{{ $profile['links']['github']['url'] }}" target="_blank" rel="noopener"><x-icon name="github" /> {{ $profile['links']['github']['handle'] }}</a></li>
                        <li><a href="{{ $profile['links']['linkedin']['url'] }}" target="_blank" rel="noopener"><x-icon name="linkedin" /> {{ $profile['links']['linkedin']['handle'] }}</a></li>
                        <li><a href="{{ $profile['links']['studio']['url'] }}" target="_blank" rel="noopener"><x-icon name="globe" /> {{ $profile['links']['studio']['handle'] }}</a></li>
                        <li><a href="{{ route('vcard') }}"><x-icon name="contact" /> Save my contact card</a></li>
                    </ul>
                </div>

                <form class="contact-form" method="POST" action="{{ route('contact.store') }}" data-contact-form novalidate>
                    @csrf

                    @if (session('contact_sent'))
                        <p class="form-status is-ok" data-form-status role="status">{{ session('contact_sent') }}</p>
                    @else
                        <p class="form-status" data-form-status role="status" hidden></p>
                    @endif

                    <div class="field-row">
                        <div class="field">
                            <label for="cf-name">Your name</label>
                            <input id="cf-name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" maxlength="120" required>
                            <p class="field-error" data-error-for="name">@error('name'){{ $message }}@enderror</p>
                        </div>
                        <div class="field">
                            <label for="cf-email">Email</label>
                            <input id="cf-email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" maxlength="190" required>
                            <p class="field-error" data-error-for="email">@error('email'){{ $message }}@enderror</p>
                        </div>
                    </div>

                    <fieldset class="field">
                        <legend>What's it about?</legend>
                        <div class="topic-row">
                            @foreach ($topics as $key => $label)
                                <label class="topic">
                                    <input type="radio" name="topic" value="{{ $key }}" @checked(old('topic', 'role') === $key)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="field-error" data-error-for="topic">@error('topic'){{ $message }}@enderror</p>
                    </fieldset>

                    <div class="field">
                        <label for="cf-message">Message <span class="counter" data-counter aria-hidden="true"></span></label>
                        <textarea id="cf-message" name="message" rows="5" maxlength="4000" required>{{ old('message') }}</textarea>
                        <p class="field-error" data-error-for="message">@error('message'){{ $message }}@enderror</p>
                    </div>

                    {{-- Honeypot. Real visitors never see or fill this. --}}
                    <div class="hp" aria-hidden="true">
                        <label for="cf-website">Leave this empty</label>
                        <input id="cf-website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <button class="btn btn-primary" type="submit" data-submit>
                        <span data-submit-label>Send message</span> <x-icon name="send" />
                    </button>
                    <p class="form-note">Your message is stored and emailed to me so I can reply. <a href="{{ route('privacy') }}">Privacy</a></p>
                </form>
            </div>
        </div>
    </section>

    {{-- Project preview --}}
    <dialog class="viewer" data-viewer aria-label="Project preview">
        <div class="viewer-box">
            <div class="viewer-shot" data-viewer-shot tabindex="0">
                <span class="shot-empty" data-viewer-empty hidden></span>
            </div>
            <div class="viewer-info">
                <button class="tool-btn viewer-close" type="button" data-viewer-close aria-label="Close preview"><x-icon name="x" /></button>
                <span class="cat" data-viewer-cat></span>
                <p data-viewer-summary></p>
                <ul class="tags" data-viewer-tags></ul>
                <a class="btn btn-primary" data-viewer-url target="_blank" rel="noopener">Visit the live site <x-icon name="arrow-up-right" /></a>
                <a class="visit viewer-case" data-viewer-case hidden>Read the case study <x-icon name="arrow-right" /></a>
                <div class="viewer-nav">
                    <button class="tool-btn" type="button" data-viewer-prev aria-label="Previous project"><x-icon name="chevron-left" /></button>
                    <span class="mono" data-viewer-count></span>
                    <button class="tool-btn" type="button" data-viewer-next aria-label="Next project"><x-icon name="chevron-right" /></button>
                </div>
                <p class="viewer-tip">Scroll the screenshot to see the whole page. Arrow keys switch projects.</p>
            </div>
        </div>
    </dialog>
    <script type="application/json" id="projects-data">@json($projects->map(fn ($project) => Arr::except($project, ['case_study', 'testimonial']))->values())</script>
@endsection
