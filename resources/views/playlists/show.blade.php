@extends('layouts.app')

@section('title', $playlist->title)

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('playlists.index') }}">Playlists</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $playlist->title }}</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-body">
            <h1 class="h3">{{ $playlist->title }}</h1>
            <div class="card-text">{!! $playlist->description !!}</div>
            <ol class="mt-3">
                @foreach($playlist->talks as $talk)
                    <li><a href="{{ route('talks.show', $talk) }}">{{ $talk->title }}</a></li>
                @endforeach
            </ol>
        </div>
    </div>
@endsection
