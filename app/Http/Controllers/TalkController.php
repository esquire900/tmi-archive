<?php

namespace App\Http\Controllers;

use App\Enums\MetricType;
use App\Models\Talk;
use App\Support\MetricTracker;
use Illuminate\Http\Request;

class TalkController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $talks = Talk::query()
            ->search($query)
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('talks.index', [
            'talks' => $talks,
            'searchQuery' => $query,
        ]);
    }

    public function show(Request $request, Talk $talk, MetricTracker $tracker)
    {
        $tracker->track($talk, MetricType::View, $request);

        $original = $request->boolean('original');

        return view('talks.show', [
            'talk' => $talk,
            // Direct media URL for the <audio> element (playback shouldn't be
            // counted as a download — only the explicit download links are).
            'playUrl' => $talk->audioUrl($original),
            'isOriginal' => $original,
            'transcriptCues' => $talk->transcriptCues(),
        ]);
    }

    public function bulkDownload(Request $request)
    {
        $original = $request->boolean('original_audio');

        $talks = Talk::query()
            ->orderBy('id')
            ->paginate(500)
            ->withQueryString();

        return view('talks.download', [
            'talks' => $talks,
            'downloadOriginal' => $original,
        ]);
    }
}
