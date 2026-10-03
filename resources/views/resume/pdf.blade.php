{{-- Rendered by dompdf, which supports a limited subset of CSS: no flexbox or grid, so layout is tables and floats. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $profile['name'] }} · Résumé</title>
    <style>
        @page { margin: 34pt 40pt 36pt; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9.4pt; line-height: 1.42; color: #1B1A17; }
        h1 { font-size: 23pt; margin: 0; letter-spacing: -0.4pt; }
        h2 { font-size: 8pt; text-transform: uppercase; letter-spacing: 1.4pt; color: #BE3A22; margin: 13pt 0 5pt; padding-bottom: 3pt; border-bottom: 0.6pt solid #D4CCBB; }
        h3 { font-size: 10.2pt; margin: 0; }
        p { margin: 0; }
        a { color: #1B1A17; text-decoration: none; }
        .title { font-size: 11pt; color: #46443E; margin-top: 2pt; }
        .contact { font-size: 8.6pt; color: #46443E; margin-top: 6pt; }
        .contact span { color: #BE3A22; padding: 0 3pt; }
        .summary { margin-top: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 0; }
        .role { margin-bottom: 8pt; page-break-inside: avoid; }
        .period { text-align: right; font-size: 8.6pt; color: #46443E; white-space: nowrap; }
        .org { color: #BE3A22; font-size: 8.8pt; margin-bottom: 2pt; }
        .org span { color: #747066; }
        ul { margin: 2pt 0 0; padding-left: 11pt; }
        li { margin-bottom: 1.6pt; }
        .skills td { padding: 1.5pt 0; }
        .skills .k { width: 118pt; font-weight: bold; }
        .edu { margin-bottom: 3pt; }
        .muted { color: #747066; }
    </style>
</head>
<body>
    <h1>{{ $profile['name'] }}</h1>
    <p class="title">{{ $profile['title'] }}</p>
    <p class="contact">
        {{ $profile['location'] }}<span>|</span>{{ $profile['email'] }}<span>|</span>{{ $profile['phone'] }}<br>
        <a href="{{ url('/') }}">{{ parse_url(url('/'), PHP_URL_HOST) }}</a>
        @foreach ($profile['links'] as $link)
            <span>|</span><a href="{{ $link['url'] }}">{{ $link['handle'] }}</a>
        @endforeach
    </p>

    <p class="summary">{{ $profile['summary'] }}</p>

    <h2>Experience</h2>
    @foreach ($experience as $role)
        <div class="role">
            <table>
                <tr>
                    <td><h3>{{ $role['role'] }}</h3></td>
                    <td class="period">{{ $role['period'] }}{{ $role['type'] ? ' · '.$role['type'] : '' }}</td>
                </tr>
            </table>
            <p class="org">{{ $role['org'] }}@if ($role['org_note'])<span> · {{ $role['org_note'] }}</span>@endif</p>
            <ul>
                @foreach ($role['bullets'] as $bullet)
                    <li>{{ $bullet }}</li>
                @endforeach
            </ul>
        </div>
    @endforeach

    <h2>Skills</h2>
    <table class="skills">
        @foreach ($skills as $group => $names)
            <tr>
                <td class="k">{{ $group }}</td>
                <td>{{ implode(', ', $names) }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Education &amp; certifications</h2>
    @foreach ($education as $item)
        <p class="edu"><strong>{{ $item['award'] }}</strong> · {{ $item['school'] }} <span class="muted">· {{ $item['status'] }}, {{ $item['period'] }}</span></p>
    @endforeach
    <p class="edu muted">
        {{ collect($certifications)->map(fn ($c) => $c['name'].' ('.$c['issuer'].($c['year'] ? ', '.$c['year'] : '').')')->implode(' · ') }}
    </p>
</body>
</html>
