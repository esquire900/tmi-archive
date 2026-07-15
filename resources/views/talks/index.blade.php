@extends('layouts.app')

@section('title', 'Talks')

@section('content')
    <div class="row mb-4">
        <div class="col-md-6 offset-md-3">
            <form action="{{ route('talks.index') }}" method="get" class="input-group input-group-lg">
                <input type="text" name="q" class="form-control" placeholder="Search TMI talks"
                       value="{{ $searchQuery }}" aria-label="Search talks">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            @if($searchQuery !== '')
                <h4 class="mb-3">Results for &ldquo;{{ $searchQuery }}&rdquo;</h4>
            @endif

            <div class="list-group">
                @forelse($talks as $talk)
                    <a href="{{ route('talks.show', $talk) }}" class="list-group-item list-group-item-action">
                        <h5 class="mb-1">{{ $talk->title }}</h5>
                        <p class="mb-1 text-muted">
                            {{ \Illuminate\Support\Str::words(strip_tags($talk->description), 20) }}
                        </p>
                    </a>
                @empty
                    <div class="alert alert-info">No talks found.</div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $talks->links() }}
            </div>
        </div>
    </div>
@endsection
