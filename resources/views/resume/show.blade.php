@extends('layouts.app')

@section('title', $profile['seo']['resume_title'])
@section('description', $profile['seo']['resume_description'])
@section('body-class', 'page-resume')

@section('content')
    <article class="resume wrap" data-resume>
        <header class="r-head">
            <div class="r-id">
                <p class="eyebrow">Résumé</p>
                <h1>{{ $profile['name'] }} <span class="r-title">{{ $profile['title'] }}</span></h1>
                <ul class="r-contact">
                    <li><x-icon name="map-pin" /> {{ $profile['location'] }}</li>
                    <li><a href="mailto:{{ $profile['email'] }}"><x-icon name="mail" /> {{ $profile['email'] }}</a></li>
                    <li><a href="tel:{{ $profile['phone_e164'] }}"><x-icon name="phone" /> {{ $profile['phone'] }}</a></li>
                    <li><a href="{{ $profile['links']['github']['url'] }}" target="_blank" rel="noopener"><x-icon name="github" /> {{ $profile['links']['github']['handle'] }}</a></li>
                    <li><a href="{{ $profile['links']['linkedin']['url'] }}" target="_blank" rel="noopener"><x-icon name="linkedin" /> {{ $profile['links']['linkedin']['handle'] }}</a></li>
                    <li><a href="{{ $profile['links']['studio']['url'] }}" target="_blank" rel="noopener"><x-icon name="globe" /> {{ $profile['links']['studio']['handle'] }}</a></li>
                </ul>
            </div>

            <div class="r-actions no-print">
                <a class="btn btn-primary" href="{{ route('resume.pdf') }}"><x-icon name="download" /> Download PDF</a>
                <button class="btn btn-ghost" type="button" data-print><x-icon name="printer" /> Print</button>
                <div class="r-actions-minor">
                    <a href="{{ route('vcard') }}"><x-icon name="contact" /> Contact card</a>
                    <a href="{{ route('resume.json') }}"><x-icon name="braces" /> JSON</a>
                    <button type="button" data-copy="{{ route('resume') }}" data-copy-label="Link copied"><x-icon name="link" /> Copy link</button>
                </div>
            </div>
        </header>

        <p class="r-summary">{{ $profile['summary'] }}</p>

        <ul class="r-highlights" aria-label="Results">
            @foreach ($highlights as $item)
                <li>
                    <strong>{{ $item['value'] }}</strong>
                    <span>{{ $item['label'] }}</span>
                    <small>{{ $item['context'] }}</small>
                </li>
            @endforeach
        </ul>

        <section class="r-chart" aria-labelledby="timeline-title">
            <div class="r-sec-head">
                <h2 id="timeline-title">Timeline</h2>
                <p class="r-note no-print">Every role on one time axis. Select a bar to jump to it.</p>
            </div>

            <div class="gantt" data-gantt>
                <div class="gantt-grid" aria-hidden="true">
                    @foreach ($chart['years'] as $tick)
                        <span @class(['gantt-tick', 'is-end' => $loop->last]) style="left: {{ $tick['left'] }}%"><i>{{ $tick['year'] }}</i></span>
                    @endforeach
                    <span class="gantt-today" style="left: {{ $chart['today'] }}%"><i>Today</i></span>
                </div>
                <ul class="gantt-rows">
                    @foreach ($chart['bars'] as $bar)
                        <li class="gantt-row" data-bar-row="{{ $bar['id'] }}">
                            <span class="gantt-label">{{ $bar['org'] }}<small>{{ $bar['role'] }}</small></span>
                            <span class="gantt-track">
                                <a @class(['gantt-bar', 'tip-end' => $bar['left'] + $bar['width'] / 2 > 60]) href="#role-{{ $bar['id'] }}" data-bar="{{ $bar['id'] }}"
                                   style="left: {{ $bar['left'] }}%; width: {{ $bar['width'] }}%"
                                   aria-label="{{ $bar['role'] }} at {{ $bar['org'] }}, {{ $bar['period'] }}{{ $bar['type'] ? ', '.$bar['type'] : '' }}">
                                    <span class="gantt-tip" aria-hidden="true">
                                        <b>{{ $bar['role'] }}</b>
                                        {{ $bar['org'] }}<br>{{ $bar['period'] }}{{ $bar['type'] ? ' · '.$bar['type'] : '' }}
                                    </span>
                                </a>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <div class="r-cols">
            <div class="r-main">
                <section id="experience" aria-labelledby="experience-title">
                    <div class="r-sec-head">
                        <h2 id="experience-title">Experience</h2>
                        <div class="seg no-print" role="group" aria-label="How much detail to show">
                            <button type="button" data-detail="brief" aria-pressed="false">Brief</button>
                            <button type="button" data-detail="full" aria-pressed="true">Full</button>
                        </div>
                    </div>

                    <p class="filter-note no-print" data-filter-note hidden>
                        <span>Highlighting <strong data-filter-count></strong> where I used <strong data-filter-skill></strong>.</span>
                        <button type="button" data-filter-clear><x-icon name="x" /> Clear</button>
                    </p>

                    @foreach ($experience as $role)
                        <article class="role" id="role-{{ $role['id'] }}" data-role="{{ $role['id'] }}" data-stack='@json($role['stack'])'>
                            <header>
                                <h3>{{ $role['role'] }}</h3>
                                <span class="period">{{ $role['period'] }}{{ $role['type'] ? ' · '.$role['type'] : '' }}</span>
                            </header>
                            <p class="org">{{ $role['org'] }}@if ($role['org_note'])<span> · {{ $role['org_note'] }}</span>@endif</p>
                            <p class="role-brief">{{ $role['summary'] }}</p>
                            <ul class="role-full">
                                @foreach ($role['bullets'] as $bullet)
                                    <li>{{ $bullet }}</li>
                                @endforeach
                            </ul>
                            <ul class="tags no-print">
                                @foreach ($role['stack'] as $skill)
                                    <li><button type="button" data-skill="{{ $skill }}">{{ $skill }}</button></li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </section>

                <section aria-labelledby="work-title">
                    <div class="r-sec-head">
                        <h2 id="work-title">Sites I've built</h2>
                        <a class="r-note no-print" href="{{ route('home') }}#work">See them with screenshots <x-icon name="arrow-right" /></a>
                    </div>
                    <ul class="r-projects">
                        @foreach ($projects as $project)
                            <li>
                                <a href="{{ $project['url'] }}" target="_blank" rel="noopener">{{ $project['name'] }}</a>
                                <span class="mono">{{ $project['host'] }}</span>
                                <p>{{ $project['summary'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            <aside class="r-side">
                <section aria-labelledby="skills-title">
                    <div class="r-sec-head">
                        <h2 id="skills-title">Skills</h2>
                    </div>
                    <p class="r-note no-print">Select a skill to highlight the roles where I used it.</p>
                    @foreach ($skills as $group => $names)
                        <h3 class="r-group">{{ $group }}</h3>
                        <ul class="chips">
                            @foreach ($names as $name)
                                <li>@if (isset($skillIndex[$name]))<button class="chip" type="button" data-skill="{{ $name }}" aria-pressed="false">{{ $name }}</button>@else<span class="chip">{{ $name }}</span>@endif</li>
                            @endforeach
                        </ul>
                    @endforeach
                </section>

                <section aria-labelledby="education-title">
                    <div class="r-sec-head">
                        <h2 id="education-title">Education</h2>
                    </div>
                    <ul class="r-list">
                        @foreach ($education as $item)
                            <li>
                                <strong>{{ $item['award'] }}</strong>
                                <span>{{ $item['school'] }}</span>
                                <small>{{ $item['status'] }} · {{ $item['period'] }}</small>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section aria-labelledby="certs-title">
                    <div class="r-sec-head">
                        <h2 id="certs-title">Certifications</h2>
                    </div>
                    <ul class="r-list">
                        @foreach ($certifications as $cert)
                            <li>
                                <strong>{{ $cert['name'] }}</strong>
                                <small>{{ $cert['issuer'] }}{{ $cert['year'] ? ' · '.$cert['year'] : '' }}</small>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </aside>
        </div>

        <footer class="r-foot no-print">
            <p>Want to talk about a role?</p>
            <a class="btn btn-primary" href="{{ route('home') }}#contact">Get in touch <x-icon name="arrow-right" /></a>
        </footer>
    </article>
@endsection
