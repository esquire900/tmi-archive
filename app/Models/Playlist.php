<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Playlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'first_recording_date',
        'created_by_id',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'first_recording_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Playlist $playlist) {
            if (auth()->check()) {
                $playlist->created_by_id ??= auth()->id();
                $playlist->updated_by_id ??= auth()->id();
            }
        });

        static::updating(function (Playlist $playlist) {
            if (auth()->check()) {
                $playlist->updated_by_id = auth()->id();
            }
        });
    }

    public function talks(): BelongsToMany
    {
        return $this->belongsToMany(Talk::class, 'playlist_talk')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('playlist_talk.position');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
