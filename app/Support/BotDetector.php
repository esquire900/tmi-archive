<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Lightweight heuristic bot/crawler detection used to keep automated traffic
 * out of the talk metrics (and to throttle it elsewhere).
 */
class BotDetector
{
    /**
     * Substrings that, when present in the User-Agent, mark the request as a bot.
     */
    protected const BOT_SIGNATURES = [
        'bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'facebookexternalhit',
        'embedly', 'quora', 'pinterest', 'bufferbot', 'vkshare', 'w3c_validator',
        'redditbot', 'applebot', 'whatsapp', 'flipboard', 'tumblr', 'telegrambot',
        'headless', 'phantomjs', 'python-requests', 'python-urllib', 'go-http-client',
        'curl/', 'wget/', 'libwww', 'httpclient', 'okhttp', 'scrapy', 'ahrefs',
        'semrush', 'mj12bot', 'dotbot', 'petalbot', 'bytespider', 'gptbot',
        'ccbot', 'claudebot', 'perplexitybot', 'amazonbot', 'dataforseo',
    ];

    public function isBot(Request $request): bool
    {
        $ua = strtolower((string) $request->userAgent());

        if ($ua === '') {
            // Empty user-agents are almost always scripted traffic.
            return true;
        }

        foreach (self::BOT_SIGNATURES as $signature) {
            if (str_contains($ua, $signature)) {
                return true;
            }
        }

        return false;
    }
}
