<?php

namespace App\Support;

use App\Enums\MetricType;
use App\Models\Talk;
use App\Models\TalkMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Records talk view/download metrics with built-in bot filtering and
 * per-IP de-duplication so that automated / repeated traffic can't inflate
 * the "revigorated" popularity numbers.
 */
class MetricTracker
{
    /**
     * Window (seconds) during which repeated identical hits from the same
     * client are collapsed into a single metric row.
     */
    protected const DEDUPE_WINDOW = 1800; // 30 minutes

    public function __construct(protected BotDetector $bots)
    {
    }

    public function track(Talk $talk, MetricType $type, Request $request): void
    {
        $isBot = $this->bots->isBot($request);
        $ip = $this->clientIp($request);

        // Collapse rapid repeats (reload spam, double-clicks, prefetch) from the
        // same client into one counted hit. Bot hits are always stored but flagged.
        if (! $isBot && $this->recentlySeen($talk, $type, $ip, $request)) {
            return;
        }

        TalkMetric::create([
            'talk_id' => $talk->id,
            'user_id' => $request->user()?->id,
            'metric_type' => $type,
            'ip' => $ip,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512) ?: null,
            'is_bot' => $isBot,
            'created_at' => now(),
        ]);
    }

    protected function recentlySeen(Talk $talk, MetricType $type, ?string $ip, Request $request): bool
    {
        $fingerprint = sha1(($ip ?? '').'|'.$request->userAgent());
        $key = "metric:{$type->value}:{$talk->id}:{$fingerprint}";

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, 1, self::DEDUPE_WINDOW);

        return false;
    }

    protected function clientIp(Request $request): ?string
    {
        return $request->ip();
    }
}
