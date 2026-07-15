@extends('layouts.app')

@section('title', 'Bulk download')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('talks.index') }}">Talks</a></li>
            <li class="breadcrumb-item active" aria-current="page">Bulk download</li>
        </ol>
    </nav>

    <h1 class="h2">Bulk download</h1>
    <p>
        This page lists download links for every talk's audio (talks can also be downloaded from their own page).
        You can grab them all at once with a browser extension such as
        <a href="https://en.wikipedia.org/wiki/DownThemAll!" target="_blank" rel="noopener">DownThemAll!</a>
    </p>
    <p>
        @if($downloadOriginal)
            These are the <strong>original</strong> audio files.
            <a href="{{ route('talks.bulk-download') }}">Switch to the cleaned recordings.</a>
        @else
            To download the original recordings instead of the processed ones,
            <a href="{{ route('talks.bulk-download', ['original_audio' => 1]) }}">click here</a>.
        @endif
    </p>

    <table class="table table-sm table-hover">
        <thead>
        <tr><th scope="col">Talk</th><th scope="col">Audio</th></tr>
        </thead>
        <tbody>
        @foreach($talks as $talk)
            <tr>
                <td>{{ $talk->title }}</td>
                <td>
                    @if($talk->has_audio)
                        @if($downloadOriginal)
                            <a href="{{ route('talks.download.original', $talk) }}" target="_blank" rel="noopener">download original</a>
                        @else
                            <a href="{{ route('talks.download', $talk) }}" target="_blank" rel="noopener">download</a>
                        @endif
                    @else
                        <span class="text-muted">No audio</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $talks->links() }}
@endsection
