@extends('layouts.admin')

@section('title', 'Connection')

@section('content')
    <header class="page-head">
        <div>
            <h1>Google Analytics connection</h1>
            <p class="page-sub">
                @if ($connected)
                    Connected to property {{ $propertyId }} as <span class="mono">{{ $serviceAccount }}</span>.
                @else
                    Not connected yet. The steps on the right take about ten minutes.
                @endif
            </p>
        </div>
    </header>

    <div class="connect-grid">
        <form class="panel" method="POST" action="{{ route('admin.connection.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="a-field">
                <label for="measurement_id">Measurement ID</label>
                <input id="measurement_id" name="measurement_id" type="text" value="{{ old('measurement_id', $measurementId) }}" placeholder="G-AB12CD34EF" autocomplete="off" spellcheck="false">
                <p class="a-hint">Adds the tracking tag to the public site so visits are recorded. Leave empty to turn tracking off.</p>
                @error('measurement_id')<p class="a-error">{{ $message }}</p>@enderror
            </div>

            <div class="a-field">
                <label for="property_id">Property ID</label>
                <input id="property_id" name="property_id" type="text" inputmode="numeric" value="{{ old('property_id', $propertyId) }}" placeholder="412345678" autocomplete="off">
                <p class="a-hint">The number that identifies the property. This and the key below let the dashboard read your reports.</p>
                @error('property_id')<p class="a-error">{{ $message }}</p>@enderror
            </div>

            <div class="a-field">
                <label for="credentials">Service-account key (JSON)</label>
                <input id="credentials" name="credentials" type="file" accept=".json,application/json">
                <p class="a-hint">
                    @if ($serviceAccount)
                        A key for <span class="mono">{{ $serviceAccount }}</span> is saved. Choose a file only to replace it.
                    @else
                        Stored encrypted in the database and never shown again.
                    @endif
                </p>
                @error('credentials')<p class="a-error">{{ $message }}</p>@enderror
            </div>

            <div class="a-field">
                <label for="search_console">Search Console verification</label>
                <input id="search_console" name="search_console" type="text" value="{{ old('search_console', $searchConsole) }}" placeholder="Paste the HTML tag or its code" autocomplete="off" spellcheck="false">
                <p class="a-hint">Optional. In <a href="https://search.google.com/search-console" target="_blank" rel="noopener">Google Search Console</a>, add the site as a URL-prefix property, choose "HTML tag", and paste it here. Then submit <span class="mono">{{ route('sitemap') }}</span> there so Google finds every page.</p>
                @error('search_console')<p class="a-error">{{ $message }}</p>@enderror
            </div>

            <div class="a-actions">
                <button class="btn btn-primary" type="submit">Save and test connection</button>
            </div>
        </form>

        <aside class="panel steps">
            <h2>How to connect</h2>
            <ol>
                <li>In <a href="https://analytics.google.com/" target="_blank" rel="noopener">Google Analytics</a>, create a GA4 property for this site and add a <strong>Web</strong> data stream. Copy its <strong>Measurement ID</strong> (starts with G-).</li>
                <li>Under Admin → Property details, copy the <strong>Property ID</strong> (a number).</li>
                <li>In <a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud Console</a>, create a project and enable the <strong>Google Analytics Data API</strong>.</li>
                <li>Under IAM &amp; Admin → Service accounts, create a service account, then add a key of type <strong>JSON</strong>. A file downloads.</li>
                <li>Back in Analytics, under Admin → Property access management, add the service account's email address with the <strong>Viewer</strong> role.</li>
                <li>Enter both IDs here, upload the JSON file, and save.</li>
            </ol>
            <p class="a-hint">A new property takes a day or so to start showing data in reports. "Active now" works straight away.</p>
        </aside>
    </div>

    @if ($connected)
        <form class="disconnect" method="POST" action="{{ route('admin.connection.destroy') }}">
            @csrf
            @method('DELETE')
            <p>Disconnecting deletes the saved key from this site. The tracking tag stays until you clear the Measurement ID.</p>
            <button class="btn btn-ghost" type="submit">Disconnect</button>
        </form>
    @endif
@endsection
