<?php

namespace App\Models;

use App\Enums\MetricType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Talk extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'audio_original',
        'audio_cleaned',
        'recorded_date',
        'original_file_name',
        'transcription',
        'whisper_transcription',
        'audio_length',
        'created_by_id',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'recorded_date' => 'date',
            'audio_length' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Talk $talk) {
            if (auth()->check()) {
                $talk->created_by_id ??= auth()->id();
                $talk->updated_by_id ??= auth()->id();
            }
        });

        static::updating(function (Talk $talk) {
            if (auth()->check()) {
                $talk->updated_by_id = auth()->id();
            }
        });
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, 'playlist_talk')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('playlist_talk.position');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(TalkMetric::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /*
     * ---- Derived helpers (ported from the Django model) ----
     */

    public function getSlugAttribute(): string
    {
        return Str::slug($this->title);
    }

    public function getHasAudioAttribute(): bool
    {
        return ! empty($this->audio_original);
    }

    public function getHasCleanedAudioAttribute(): bool
    {
        return $this->has_audio && ! empty($this->audio_cleaned);
    }

    /**
     * Relative media path for the requested variant (cleaned by default, with
     * original as fallback).
     */
    public function audioPath(bool $original = false): ?string
    {
        if ($original) {
            return $this->audio_original ?: null;
        }

        return $this->audio_cleaned ?: ($this->audio_original ?: null);
    }

    /**
     * Absolute URL to the MP3 file on the media host.
     */
    public function audioUrl(bool $original = false): ?string
    {
        $path = $this->audioPath($original);
        if (! $path) {
            return null;
        }

        return config('media.base_url').'/'.ltrim($path, '/');
    }

    /**
     * Human-friendly audio length, e.g. "14 minutes" or "01 hours, 04 min".
     */
    public function getAudioLengthFormattedAttribute(): ?string
    {
        $sec = $this->audio_length;
        if ($sec === null) {
            return null;
        }

        if ($sec < 3600) {
            return intdiv($sec, 60).' minutes';
        }

        return sprintf('%02d hours, %02d min', intdiv($sec, 3600), intdiv($sec % 3600, 60));
    }

    /**
     * Transcription rendered as readable paragraphs (drops any "[timestamp]"
     * prefixes and groups sentences into ~200-char paragraphs).
     */
    public function getTranscriptionTextAttribute(): ?string
    {
        if ($this->transcription === null) {
            return null;
        }

        $sentences = preg_split('/\r\n/', $this->transcription);
        $sentences = array_map(function ($s) {
            return str_contains($s, ']') ? Str::after($s, ']') : $s;
        }, $sentences);

        $paragraphs = [];
        $paragraph = '';
        foreach ($sentences as $sentence) {
            if (strlen($paragraph) > 200) {
                $paragraphs[] = $paragraph;
                $paragraph = '';
            }
            $paragraph .= $sentence;
        }
        $paragraphs[] = $paragraph;

        return implode('<br><br>', $paragraphs);
    }

    /**
     * Structured transcript cues [{start,end,text}, ...] for the player.
     * Parsed from the whisper JSON when present.
     *
     * @return array<int, array{start: float, text: string}>
     */
    public function transcriptCues(): array
    {
        $cues = [];

        $source = $this->whisper_transcription ?: $this->transcription;
        if (! $source) {
            return $cues;
        }

        $decoded = json_decode($source, true);
        if (is_array($decoded)) {
            foreach ($decoded as $entry) {
                if (isset($entry['text'])) {
                    $cues[] = [
                        'start' => (float) ($entry['start'] ?? 0),
                        'text' => trim((string) $entry['text']),
                    ];
                }
            }
        }

        return $cues;
    }

    /**
     * Search scope over title + description.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', '%'.$term.'%')
                ->orWhere('description', 'like', '%'.$term.'%');
        });
    }

    public function viewCount(): int
    {
        return $this->metrics()
            ->where('metric_type', MetricType::View)
            ->where('is_bot', false)
            ->count();
    }

    public function downloadCount(): int
    {
        return $this->metrics()
            ->where('metric_type', MetricType::Download)
            ->where('is_bot', false)
            ->count();
    }
}
