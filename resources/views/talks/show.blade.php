@extends('layouts.app')

@section('title', $talk->title)

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('talks.index') }}">Talks</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $talk->title }}</li>
        </ol>
    </nav>

    <h1 class="h2 mb-3">{{ $talk->title }}</h1>

    <div class="row g-4">
        <div class="col-lg-8">
            @if($talk->has_audio)
                <div class="card mb-4">
                    <div class="card-body">
                        <audio id="player" class="w-100" controls preload="none" src="{{ $playUrl }}"></audio>
                        @if($isOriginal)
                            <div class="small text-muted mt-2">Playing the <strong>original</strong> recording.</div>
                        @elseif(! $talk->has_cleaned_audio)
                            <div class="alert alert-info mt-3 mb-0">
                                This talk doesn't have a cleaned file yet &mdash; you're listening to the original.
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="alert alert-secondary">There is no audio for this talk.</div>
            @endif

            <div class="card">
                <div class="card-body">
                    @if($talk->description)
                        <div class="card-text">{!! $talk->description !!}</div>
                    @endif

                    @if(! empty($transcriptCues))
                        <hr>
                        <h2 class="h4">Transcription</h2>
                        <div id="transcript" class="transcript border rounded p-2">
                            @foreach($transcriptCues as $cue)
                                <span class="cue" data-start="{{ $cue['start'] }}">
                                    <span class="t">{{ \App\Support\Time::hms($cue['start']) }}</span>{{ $cue['text'] }}
                                </span>
                            @endforeach
                        </div>
                        <div class="form-text">Click any line to jump to that point in the audio.</div>
                    @elseif($talk->transcription_text)
                        <hr>
                        <h2 class="h4">Transcription</h2>
                        <div class="card-text">{!! $talk->transcription_text !!}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <table class="table table-sm">
                <tbody>
                @if($talk->created_at)
                    <tr><th scope="row" class="fw-normal">Added</th><td>{{ $talk->created_at->format('M j, Y') }}</td></tr>
                @endif
                @if($talk->recorded_date)
                    <tr><th scope="row" class="fw-normal">Recorded</th><td>{{ $talk->recorded_date->format('M j, Y') }}</td></tr>
                @endif
                @if($talk->audio_length)
                    <tr><th scope="row" class="fw-normal">Length</th><td>{{ $talk->audio_length_formatted }}</td></tr>
                @endif
                @if($talk->original_file_name)
                    <tr><th scope="row" class="fw-normal">Original file</th><td class="text-break small">{{ $talk->original_file_name }}</td></tr>
                @endif
                <tr><th scope="row" class="fw-normal">Views</th><td>{{ number_format($talk->viewCount()) }}</td></tr>
                <tr><th scope="row" class="fw-normal">Downloads</th><td>{{ number_format($talk->downloadCount()) }}</td></tr>
                </tbody>
            </table>

            @if($talk->has_audio)
                <div class="card">
                    <div class="list-group list-group-flush">
                        <a class="list-group-item list-group-item-action" href="{{ route('talks.download', $talk) }}">
                            <i class="bi bi-download"></i> Download audio (mp3)
                        </a>
                        <a class="list-group-item list-group-item-action" href="{{ route('talks.download.original', $talk) }}">
                            <i class="bi bi-download"></i> Download original audio
                        </a>
                        @if($isOriginal)
                            <a class="list-group-item list-group-item-action" href="{{ route('talks.show', $talk) }}">
                                <i class="bi bi-play-circle"></i> Listen to cleaned audio
                            </a>
                        @else
                            <a class="list-group-item list-group-item-action" href="{{ route('talks.show', ['talk' => $talk, 'original' => 1]) }}">
                                <i class="bi bi-play-circle"></i> Listen to original audio
                            </a>
                        @endif
                        @auth
                            @if(auth()->user()->is_admin)
                                <a class="list-group-item list-group-item-action" href="/admin/talks/{{ $talk->id }}/edit">
                                    <i class="bi bi-pencil"></i> Edit talk
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
                <p class="form-text mt-2">
                    * Audio files are processed to reduce background noise and improve compression. The original
                    files remain accessible via the &ldquo;original&rdquo; links above.
                </p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const player = document.getElementById('player');
            const transcript = document.getElementById('transcript');
            if (!player || !transcript) return;

            const cues = Array.from(transcript.querySelectorAll('.cue')).map(el => ({
                el, start: parseFloat(el.dataset.start)
            }));

            transcript.addEventListener('click', function (e) {
                const cue = e.target.closest('.cue');
                if (!cue) return;
                player.currentTime = parseFloat(cue.dataset.start);
                player.play();
            });

            let activeIndex = -1;
            player.addEventListener('timeupdate', function () {
                const t = player.currentTime;
                let idx = -1;
                for (let i = 0; i < cues.length; i++) {
                    if (cues[i].start <= t) idx = i; else break;
                }
                if (idx !== activeIndex) {
                    if (activeIndex >= 0) cues[activeIndex].el.classList.remove('active');
                    if (idx >= 0) cues[idx].el.classList.add('active');
                    activeIndex = idx;
                }
            });
        })();
    </script>
@endpush
