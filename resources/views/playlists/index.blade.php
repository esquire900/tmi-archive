@extends('layouts.app')

@section('title', 'Playlists')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Playlists</li>
        </ol>
    </nav>

    <h1 class="h2 mb-4">Playlists</h1>

    <div class="row">
        <div class="col-lg-8">
            @forelse($playlists as $playlist)
                <div class="mb-3 pb-3 border-bottom">
                    <h4 class="mb-1">
                        <a href="{{ route('playlists.show', $playlist) }}">{{ $playlist->title }}</a>
                    </h4>
                    <p class="text-muted mb-1">{{ \Illuminate\Support\Str::words(strip_tags($playlist->description), 30) }}</p>
                    <span class="badge text-bg-light">{{ $playlist->talks_count }} talks</span>
                </div>
            @empty
                <div class="alert alert-info">No playlists yet.</div>
            @endforelse
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <p class="mb-0">Playlists help organize talks into curated series.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
