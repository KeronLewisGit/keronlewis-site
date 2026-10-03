<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') · {{ config('portfolio.profile.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <script>
        (function () {
            var root = document.documentElement, stored = null;
            try { stored = localStorage.getItem('theme'); } catch (e) {}
            root.dataset.theme = stored || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            root.classList.add('js');
        })();
    </script>

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="admin">
    <header class="nav">
        <div class="wrap nav-row">
            <a class="brand" href="{{ route('admin.analytics') }}"><span class="brand-mark"></span><span class="brand-name">keron&nbsp;lewis <span class="brand-sub">/ admin</span></span></a>

            @auth
                <nav class="admin-links" aria-label="Admin">
                    <a href="{{ route('admin.analytics') }}" @if (request()->routeIs('admin.analytics')) aria-current="page" @endif>Analytics</a>
                    <a href="{{ route('admin.connection') }}" @if (request()->routeIs('admin.connection')) aria-current="page" @endif>Connection</a>
                </nav>
            @endauth

            <div class="nav-tools">
                <a class="tool-btn admin-site" href="{{ route('home') }}">View site <x-icon name="arrow-up-right" /></a>
                <button class="tool-btn" type="button" data-theme-toggle aria-label="Switch theme">
                    <x-icon name="moon" class="icon i-moon" /><x-icon name="sun" class="icon i-sun" />
                </button>
                @auth
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="tool-btn" type="submit">Sign out</button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    <main class="wrap admin-main">
        @if (session('status'))
            <p class="notice is-ok" role="status"><x-icon name="check" /> {{ session('status') }}</p>
        @endif

        @yield('content')
    </main>
</body>
</html>
