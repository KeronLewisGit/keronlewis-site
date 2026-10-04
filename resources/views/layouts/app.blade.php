@php
    $profile = config('portfolio.profile');
    $onHome = request()->routeIs('home');
    $gaId = auth()->check() ? null : \App\Models\Setting::measurementId();
    $home = $onHome ? '' : route('home');
    // Section content arrives already escaped, so decode it here and let {{ }} escape it once.
    $pageTitle = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES) ?: $profile['seo']['home_title'];
    $pageDescription = html_entity_decode(trim($__env->yieldContent('description')), ENT_QUOTES) ?: $profile['seo']['home_description'];
    $siteVerification = \App\Models\Setting::cached('google_site_verification');

    $commands = [
        ['group' => 'Go to', 'label' => 'Selected work', 'href' => $home.'#work'],
        ['group' => 'Go to', 'label' => 'Experience', 'href' => $home.'#experience'],
        ['group' => 'Go to', 'label' => 'Skills', 'href' => $home.'#skills'],
        ['group' => 'Go to', 'label' => 'Services', 'href' => $home.'#services'],
        ['group' => 'Go to', 'label' => 'About', 'href' => $home.'#about'],
        ['group' => 'Go to', 'label' => 'Contact form', 'href' => $home.'#contact'],
        ['group' => 'Go to', 'label' => 'Résumé', 'href' => route('resume')],
        ['group' => 'Do', 'label' => 'Download résumé as PDF', 'href' => route('resume.pdf'), 'download' => true],
        ['group' => 'Do', 'label' => 'Save contact card (.vcf)', 'href' => route('vcard'), 'download' => true],
        ['group' => 'Do', 'label' => 'Copy email address', 'copy' => $profile['email']],
        ['group' => 'Do', 'label' => 'Copy phone number', 'copy' => $profile['phone']],
        ['group' => 'Do', 'label' => 'Switch light / dark theme', 'action' => 'theme'],
    ];
    foreach ($profile['links'] as $link) {
        $commands[] = ['group' => 'Elsewhere', 'label' => $link['label'], 'hint' => $link['handle'], 'href' => $link['url'], 'external' => true];
    }
    $services = app(\App\Support\Portfolio::class)->services();
    foreach ($services as $service) {
        $commands[] = ['group' => 'Services', 'label' => $service['title'], 'hint' => $profile['location'], 'href' => $service['url']];
    }
    foreach (config('portfolio.projects') as $project) {
        if (isset($project['case_study'])) {
            $commands[] = ['group' => 'Case studies', 'label' => $project['case_study']['title'], 'hint' => $project['name'], 'href' => route('work.show', $project['slug'])];
        }
    }
    foreach (config('portfolio.projects') as $project) {
        $commands[] = ['group' => 'Sites I built', 'label' => $project['name'], 'hint' => parse_url($project['url'], PHP_URL_HOST), 'href' => $project['url'], 'external' => true];
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="author" content="{{ $profile['name'] }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    {{-- The site is in English only, so each page names itself as the English and the default version. --}}
    <link rel="alternate" hreflang="en" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">
    @if ($siteVerification)
        <meta name="google-site-verification" content="{{ $siteVerification }}">
    @endif
    {{-- Where the business is, for search engines that read these (Bing does). --}}
    <meta name="geo.region" content="{{ $profile['country_code'] }}">
    <meta name="geo.placename" content="{{ $profile['location'] }}">
    <meta name="theme-color" content="#F5F2EB" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#15140F" media="(prefers-color-scheme: dark)">

    <meta property="og:type" content="{{ request()->routeIs('home', 'resume') ? 'profile' : 'website' }}">
    <meta property="og:locale" content="en_US">
    <meta property="og:site_name" content="{{ $profile['name'] }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('og-cover.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $profile['name'] }}, {{ $profile['title'] }}, {{ $profile['location'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ asset('og-cover.png') }}">

    @if (request()->routeIs('home', 'resume'))
        <script type="application/ld+json">
            {!! json_encode(app(\App\Support\Portfolio::class)->schema($pageTitle), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate" type="application/json" href="{{ route('resume.json') }}" title="Résumé (JSON Resume)">

    @stack('head')

    {{-- Google Analytics is only loaded by resources/js/modules/consent.js, after the visitor allows it. Left out for the signed-in admin so your own visits aren't counted. --}}
    @if ($gaId)
        <meta name="ga-id" content="{{ $gaId }}">
    @endif

    {{-- Set the theme before first paint so there is no flash. --}}
    <script>
        (function () {
            var root = document.documentElement, stored = null;
            try { stored = localStorage.getItem('theme'); } catch (e) {}
            root.dataset.theme = stored || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            root.classList.add('js');
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('body-class')">
    {{-- Cloudflare's email obfuscation would turn each mailto: link into /cdn-cgi/l/email-protection, which
         crawlers see as a broken page, and each visible address into "[email protected]". These markers switch it off. --}}
    <!--email_off-->
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="progress" aria-hidden="true"><span data-progress></span></div>

    <header class="nav" data-nav>
        <div class="wrap nav-row">
            <a class="brand" href="{{ route('home') }}" aria-label="{{ $profile['name'] }}, home">
                <span class="brand-mark"></span>keron&nbsp;lewis
            </a>

            <nav class="nav-links" id="menu" aria-label="Main">
                <a href="{{ $home }}#work" @if ($onHome) data-spy="work" @endif>Work</a>
                <a href="{{ $home }}#services" @if ($onHome) data-spy="services" @elseif (request()->routeIs('services.show')) aria-current="page" @endif>Services</a>
                <a href="{{ $home }}#experience" @if ($onHome) data-spy="experience" @endif>Experience</a>
                <a href="{{ $home }}#skills" @if ($onHome) data-spy="skills" @endif>Skills</a>
                <a href="{{ $home }}#about" @if ($onHome) data-spy="about" @endif>About</a>
                <a href="{{ route('resume') }}" @if (request()->routeIs('resume')) aria-current="page" @endif>Résumé</a>
                <a class="btn btn-primary nav-cta" href="{{ $home }}#contact">Get in touch</a>
            </nav>

            <div class="nav-tools">
                <button class="tool-btn palette-btn" type="button" data-palette-open aria-label="Open quick search">
                    <x-icon name="search" /><kbd data-mod-key>⌘K</kbd>
                </button>
                <button class="tool-btn" type="button" data-theme-toggle aria-label="Switch theme">
                    <x-icon name="moon" class="icon i-moon" /><x-icon name="sun" class="icon i-sun" />
                </button>
                <button class="tool-btn nav-toggle" type="button" data-menu-toggle aria-controls="menu" aria-expanded="false" aria-label="Menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <main id="main">
        @yield('content')
    </main>

    <footer class="footer">
        <div class="wrap">
            <nav class="footer-services" aria-label="Services">
                <span>{{ $profile['title'] }} in {{ $profile['location'] }}</span>
                @foreach ($services as $service)
                    <a href="{{ $service['url'] }}">{{ $service['title'] }}</a>
                @endforeach
            </nav>
        <div class="footer-row">
            <span>© {{ date('Y') }} {{ $profile['name'] }}. Designed and built by me, on Laravel.</span>
            <span class="footer-links">
                <a href="{{ route('resume') }}">Résumé</a>
                <a href="{{ route('privacy') }}">Privacy</a>
                @if ($gaId)
                    <button type="button" data-consent-open>Cookie settings</button>
                @endif
                <a href="{{ route('resume.json') }}">resume.json</a>
                <a href="{{ $profile['links']['github']['url'] }}" target="_blank" rel="noopener">GitHub</a>
                <a href="{{ $profile['links']['linkedin']['url'] }}" target="_blank" rel="noopener">LinkedIn</a>
            </span>
        </div></div>
    </footer>

    <dialog class="palette" data-palette aria-label="Quick search">
        <div class="palette-box">
            <label class="palette-input">
                <x-icon name="search" />
                <input type="text" placeholder="Jump to a section, a site I built, or an action" autocomplete="off" spellcheck="false" data-palette-input aria-label="Search">
                <kbd>esc</kbd>
            </label>
            <ul class="palette-list" data-palette-list role="listbox"></ul>
            <p class="palette-empty" data-palette-empty hidden>Nothing matches that.</p>
        </div>
    </dialog>
    <script type="application/json" id="palette-data">@json($commands)</script>

    <div class="toast" data-toast role="status" aria-live="polite"></div>

    @if ($gaId)
        <section class="consent" data-consent hidden aria-label="Analytics cookies">
            <p>May I count your visit? I use Google Analytics to see which pages get read. It sets cookies, so it stays off unless you allow it. <a href="{{ route('privacy') }}">Privacy details</a></p>
            <div class="consent-actions">
                <button class="btn btn-ghost" type="button" data-consent-deny>No thanks</button>
                <button class="btn btn-ghost" type="button" data-consent-allow>Allow analytics</button>
            </div>
        </section>
    @endif
    <!--/email_off-->
</body>
</html>
