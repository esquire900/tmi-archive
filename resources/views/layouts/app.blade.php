<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TMI Archive @hasSection('title')| @yield('title')@endif</title>
    <meta name="description" content="An archive of talks and texts from the late Culadasa, author of The Mind Illuminated.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f6f6f6; display: flex; flex-direction: column; min-height: 100vh; }
        main { flex: 1 0 auto; }
        .site-header { background-image: url('{{ asset('img/meditator-2.jpg') }}'); background-size: cover; background-position: center; }
        .site-header .overlay { background-color: rgba(255,255,255,.35); }
        .transcript { max-height: 22rem; overflow-y: auto; }
        .transcript .cue { cursor: pointer; display: block; padding: .1rem .25rem; border-radius: .25rem; }
        .transcript .cue:hover { background: #eef2ff; }
        .transcript .cue .t { color: #6c757d; font-variant-numeric: tabular-nums; margin-right: .5rem; font-size: .85em; }
        .transcript .cue.active { background: #e7f1ff; font-weight: 600; }
    </style>
    @stack('head')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ route('home') }}">TMI Archive</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
                aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="{{ route('talks.index') }}">Talks</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('playlists.index') }}">Playlists</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('talks.bulk-download') }}">Bulk download</a></li>
            </ul>
            <ul class="navbar-nav">
                @auth
                    <li class="nav-item"><a class="nav-link" href="/admin">{{ auth()->user()->name }}</a></li>
                @else
                    <li class="nav-item"><a class="nav-link" href="/admin/login">Login</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

@if(! empty($displayHeader))
    <header class="site-header">
        <div class="overlay py-5">
            <div class="container">
                <h1 class="display-4 text-dark">The Mind Illuminated archive</h1>
                <p class="lead text-dark mb-0">All known talks and texts from Culadasa.</p>
            </div>
        </div>
    </header>
@endif

<main class="py-4">
    <div class="container">
        @yield('content')
    </div>
</main>

<footer class="mt-auto py-4 border-top bg-white">
    <div class="container">
        <div class="row">
            <div class="col-md-8 text-muted small">
                tmi-archive.com does not hold any rights to its content. Please
                <a href="{{ route('contact') }}">contact us</a> if you feel any copyright is violated.
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('contact') }}">Contact</a> &middot;
                <a href="https://github.com/esquire900/tmi-archive">Source Code</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
@stack('scripts')
</body>
</html>
