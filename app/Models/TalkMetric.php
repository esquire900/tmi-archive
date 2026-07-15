<?php

namespace App\Models;

use App\Enums\MetricType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalkMetric extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'talk_id',
        'user_id',
        'metric_type',
        'ip',
        'user_agent',
        'is_bot',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metric_type' => MetricType::class,
            'is_bot' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
