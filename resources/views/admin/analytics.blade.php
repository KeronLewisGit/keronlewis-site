@extends('layouts.admin')

@section('title', 'Analytics')

@php
    use App\Support\Format;

    $tiles = $state === 'ready' ? [
        ['label' => 'Visitors', 'value' => Format::count($report['totals']['visitors']['value']), 'change' => $report['totals']['visitors']['change']],
        ['label' => 'Sessions', 'value' => Format::count($report['totals']['sessions']['value']), 'change' => $report['totals']['sessions']['change']],
        ['label' => 'Page views', 'value' => Format::count($report['totals']['views']['value']), 'change' => $report['totals']['views']['change']],
        ['label' => 'Time per visitor', 'value' => Format::duration($report['totals']['engagement']['value']), 'change' => $report['totals']['engagement']['change']],
    ] : [];

    $lists = $state === 'ready' ? [
        ['title' => 'Top pages', 'unit' => 'Views', 'rows' => $report['pages']],
        ['title' => 'Where visitors came from', 'unit' => 'Sessions', 'rows' => $report['sources']],
        ['title' => 'Countries', 'unit' => 'Visitors', 'rows' => $report['countries']],
        ['title' => 'Devices', 'unit' => 'Visitors', 'rows' => $report['devices']],
    ] : [];
@endphp

@section('content')
    <header class="page-head">
        <div>
            <h1>Analytics</h1>
            @if ($state === 'ready')
                <p class="page-sub">Property {{ $propertyId }} · updated {{ \Illuminate\Support\Carbon::parse($report['fetched_at'])->diffForHumans() }} · counts only visitors who allowed analytics</p>
            @endif
        </div>

        @if ($state !== 'disconnected')
            <form method="POST" action="{{ route('admin.analytics.refresh', ['days' => $days]) }}">
                @csrf
                <button class="btn btn-ghost" type="submit">Refresh</button>
            </form>
        @endif
    </header>

    @if ($state === 'disconnected')
        <section class="panel empty">
            <h2>Google Analytics isn't connected yet</h2>
            <p>
                @if ($tracking)
                    The tracking tag ({{ $tracking }}) is on the public site, so visits are being recorded. Add the Property ID and a service-account key to see the numbers here.
                @else
                    Connect a GA4 property and this page will show visitors, top pages, traffic sources and résumé downloads.
                @endif
            </p>
            <a class="btn btn-primary" href="{{ route('admin.connection') }}">Connect Google Analytics <x-icon name="arrow-right" /></a>
        </section>
    @elseif ($state === 'error')
        <section class="panel empty is-bad">
            <h2>Couldn't load the reports</h2>
            <p>{{ $error }}</p>
            <a class="btn btn-ghost" href="{{ route('admin.connection') }}">Check the connection</a>
        </section>
    @else
        {{-- One filter row; everything below is scoped to it. --}}
        <nav class="seg range" aria-label="Date range">
            @foreach ($ranges as $range)
                <a href="{{ route('admin.analytics', ['days' => $range]) }}" @if ($range === $days) aria-current="true" @endif>Last {{ $range }} days</a>
            @endforeach
        </nav>

        <section class="tiles" aria-label="Totals">
            @foreach ($tiles as $tile)
                @php
                    $delta = Format::change($tile['change']);
                @endphp
                <div class="panel tile">
                    <p class="tile-label">{{ $tile['label'] }}</p>
                    <p class="tile-value">{{ $tile['value'] }}</p>
                    <p class="tile-delta">
                        @if ($delta === null)
                            No earlier data to compare
                        @else
                            <span @class(['delta', 'is-up' => $tile['change'] > 0, 'is-down' => $tile['change'] < 0])>
                                {{ $tile['change'] > 0 ? '▲' : ($tile['change'] < 0 ? '▼' : '') }} {{ $delta }}
                            </span>
                            vs previous {{ $days }} days
                        @endif
                    </p>
                </div>
            @endforeach

            <div class="panel tile">
                <p class="tile-label"><span class="live-dot"></span>Active now</p>
                <p class="tile-value">{{ Format::count($activeNow) }}</p>
                <p class="tile-delta">Visitors in the last 30 minutes</p>
            </div>
        </section>

        <section class="panel chart-card">
            <h2>Visitors per day</h2>
            <div class="line-chart" data-line-chart tabindex="0" role="group" aria-label="Line chart of visitors per day. Use the left and right arrow keys to read each day.">
                <script type="application/json">@json($report['daily'])</script>
            </div>

            <details class="table-view">
                <summary>Show as a table</summary>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Visitors</th><th>Page views</th></tr>
                        </thead>
                        <tbody>
                            @foreach (array_reverse($report['daily']) as $day)
                                <tr><td>{{ $day['label'] }}</td><td>{{ number_format($day['visitors']) }}</td><td>{{ number_format($day['views']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </section>

        <div class="cards">
            @foreach ($lists as $list)
                <section class="panel">
                    <header class="card-head">
                        <h2>{{ $list['title'] }}</h2>
                        <span>{{ $list['unit'] }}</span>
                    </header>
                    @if ($list['rows'])
                        <ol class="bar-list">
                            @foreach ($list['rows'] as $row)
                                <li>
                                    <span class="bar-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                                    <span class="bar-value">{{ number_format($row['value']) }}</span>
                                    <span class="bar-track"><i style="width: {{ $row['share'] }}%"></i></span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="none">Nothing recorded in this period.</p>
                    @endif
                </section>
            @endforeach

            <section class="panel">
                <header class="card-head">
                    <h2>What visitors did</h2>
                    <span>Times</span>
                </header>
                <ul class="event-list">
                    @foreach ($report['events'] as $event)
                        <li><span>{{ $event['label'] }}</span><strong>{{ number_format($event['value']) }}</strong></li>
                    @endforeach
                </ul>
            </section>
        </div>
    @endif
@endsection
