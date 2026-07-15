@extends('layouts.app')

@section('content')
    <div class="row mb-4">
        <div class="col-md-6 offset-md-3">
            <form action="{{ route('talks.index') }}" method="get" class="input-group input-group-lg">
                <input type="text" name="q" class="form-control" placeholder="Search TMI talks" aria-label="Search talks">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <div class="text-center mt-2">
                Or <a href="{{ route('talks.index') }}">browse all talks</a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @foreach($playlists as $playlist)
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header">Playlist: {{ $playlist->title }}</div>
                    <div class="card-body">
                        @foreach($playlist->talks as $i => $talk)
                            <div class="text-truncate">
                                #{{ $i + 1 }}
                                <a href="{{ route('talks.show', $talk) }}">{{ $talk->title }}</a>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer bg-white">
                        <a href="{{ route('playlists.show', $playlist) }}">View playlist</a>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">The Mind Illuminated archive</h5>
                    <p class="card-text">
                        This website contains talks and texts from the late Culadasa, and is named after his
                        wonderful book.
                    </p>
                    <p class="card-text">
                        Any <a href="{{ route('contact') }}">feedback</a> or help organizing talks is more than welcome.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Updates</h5>
                    <p class="card-text small mb-0">
                        All talks have been transcribed with Whisper, and audio files cleaned for better quality.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
