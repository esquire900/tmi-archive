<?php

namespace Tests\Feature;

use App\Models\Talk;
use App\Models\TalkMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_bot_user_agent_is_flagged_and_excluded_from_counts(): void
    {
        $talk = Talk::factory()->create();

        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
            ->get("/talk/{$talk->id}")
            ->assertOk();

        // Metric is still stored, but flagged as a bot and not counted.
        $this->assertSame(1, TalkMetric::where('talk_id', $talk->id)->where('is_bot', true)->count());
        $this->assertSame(0, $talk->fresh()->viewCount());
    }

    public function test_repeated_views_from_same_client_are_deduplicated(): void
    {
        $talk = Talk::factory()->create();

        for ($i = 0; $i < 4; $i++) {
            $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (real listener)'])
                ->get("/talk/{$talk->id}")
                ->assertOk();
        }

        // Rapid repeats collapse into a single counted view.
        $this->assertSame(1, $talk->fresh()->viewCount());
    }

    public function test_empty_user_agent_is_treated_as_a_bot(): void
    {
        $talk = Talk::factory()->create();

        $this->withHeaders(['User-Agent' => ''])
            ->get("/talk/{$talk->id}")
            ->assertOk();

        $this->assertSame(0, $talk->fresh()->viewCount());
    }
}
