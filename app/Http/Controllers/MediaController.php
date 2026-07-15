<?php

namespace App\Http\Controllers;

use App\Enums\MetricType;
use App\Models\Talk;
use App\Support\MetricTracker;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    /**
     * Download the (cleaned, or original as fallback) audio for a talk.
     * Replaces the old /api/v1/talks/{pk}/download endpoint.
     */
    public function download(Request $request, Talk $talk, MetricTracker $tracker)
    {
        return $this->serveAudio($request, $talk, false, $tracker);
    }

    /**
     * Download the original (unprocessed) audio.
     * Replaces /api/v1/talks/{pk}/download/original.
     */
    public function downloadOriginal(Request $request, Talk $talk, MetricTracker $tracker)
    {
        return $this->serveAudio($request, $talk, true, $tracker);
    }

    protected function serveAudio(Request $request, Talk $talk, bool $original, MetricTracker $tracker)
    {
        $path = $talk->audioPath($original);
        if (! $path) {
            abort(Response::HTTP_NOT_FOUND, "No audio file found for this talk (id: {$talk->id}).");
        }

        $tracker->track($talk, MetricType::Download, $request);

        $filename = "tmi-archive-{$talk->id}-{$talk->slug}.mp3";

        // If the MP3s live on the local filesystem, stream them; otherwise
        // redirect to the media host (the common production setup).
        $root = config('media.root');
        if ($root) {
            $full = rtrim($root, '/').'/'.ltrim($path, '/');
            if (is_file($full)) {
                return response()->download($full, $filename, [
                    'Content-Type' => 'audio/mpeg',
                ]);
            }
        }

        return redirect()->away($talk->audioUrl($original));
    }

    /**
     * Player-formatted transcript (text/plain) with "[H:MM:SS.f @] text" lines.
     * Replaces /api/v1/talks/{pk}/transcription.
     */
    public function transcription(Talk $talk)
    {
        $body = $this->playerFormattedTranscript($talk) ?? (string) $talk->transcription_text;

        return response($body, Response::HTTP_OK)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Raw structured transcript (JSON array of {start,end,text}).
     * Replaces /api/v1/talks/{pk}/transcription_json.
     */
    public function transcriptionJson(Talk $talk)
    {
        $body = $talk->whisper_transcription
            ?: ($talk->transcription && json_decode($talk->transcription) !== null ? $talk->transcription : json_encode($talk->transcriptCues()));

        return response($body ?: '[]', Response::HTTP_OK)
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Port of the Django Talk.transcription_player_formatted property.
     */
    protected function playerFormattedTranscript(Talk $talk): ?string
    {
        $cues = $talk->transcriptCues();
        if (empty($cues)) {
            return null;
        }

        $paragraphs = [];
        $paragraph = '';
        foreach ($cues as $cue) {
            $sentence = $cue['text'];
            if ($paragraph === '') {
                $sentence = '['.$this->formatTimestamp($cue['start']).' @] '.$sentence;
            }
            $paragraph .= $sentence;

            if (strlen($paragraph) > 200) {
                $paragraphs[] = $paragraph;
                $paragraph = '';
            }
        }
        $paragraphs[] = $paragraph;

        return implode("\r\n", $paragraphs);
    }

    /**
     * Format seconds as "H:MM:SS.f" (matching Python's timedelta str()).
     */
    protected function formatTimestamp(float $seconds): string
    {
        $rounded = round($seconds, 2);
        $h = intdiv((int) $rounded, 3600);
        $m = intdiv((int) $rounded % 3600, 60);
        $s = $rounded - ($h * 3600) - ($m * 60);

        $frac = $s - floor($s);
        if ($frac > 0) {
            $sStr = rtrim(sprintf('%05.2f', $s), '0');
            $sStr = rtrim($sStr, '.');
        } else {
            $sStr = sprintf('%02d.0', (int) $s);
        }

        return sprintf('%d:%02d:%s', $h, $m, $sStr);
    }
}
