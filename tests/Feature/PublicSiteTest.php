<?php

namespace Tests\Feature;

use App\Enums\MetricType;
use App\Models\Playlist;
use App\Models\Talk;
use App\Models\TalkMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders(): void
    {
        Playlist::factory()->create();

        $this->get('/')->assertOk()->assertSee('The Mind Illuminated archive');
    }

    public function test_talk_index_and_search(): void
    {
        Talk::factory()->create(['title' => 'Mindfulness and Attention']);
        Talk::factory()->create(['title' => 'Something Unrelated']);

        $this->get('/talk')->assertOk()->assertSee('Mindfulness and Attention');

        $this->get('/talk?q=mindfulness')
            ->assertOk()
            ->assertSee('Mindfulness and Attention')
            ->assertDontSee('Something Unrelated');
    }

    public function test_talk_show_records_a_view_metric(): void
    {
        $talk = Talk::factory()->create();

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (real browser)'])
            ->get("/talk/{$talk->id}")
            ->assertOk()
            ->assertSee($talk->title);

        $this->assertSame(1, TalkMetric::where('talk_id', $talk->id)
            ->where('metric_type', MetricType::View)
            ->where('is_bot', false)
            ->count());
    }

    public function test_download_redirects_to_media_host_and_tracks_download(): void
    {
        $talk = Talk::factory()->create(['audio_cleaned' => '5/cleaned.mp3']);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (real browser)'])
            ->get("/talks/{$talk->id}/download")
            ->assertRedirect(config('media.base_url').'/5/cleaned.mp3');

        $this->assertSame(1, TalkMetric::where('talk_id', $talk->id)
            ->where('metric_type', MetricType::Download)
            ->count());
    }

    public function test_download_404_when_no_audio(): void
    {
        $talk = Talk::factory()->withoutAudio()->create();

        $this->get("/talks/{$talk->id}/download")->assertNotFound();
    }

    public function test_transcription_endpoint_returns_timestamped_text(): void
    {
        $talk = Talk::factory()->withWhisper()->create();

        $this->get("/talks/{$talk->id}/transcription")
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertSee('[0:00:00.0 @] Hello and welcome.');
    }

    public function test_bulk_download_and_playlists_pages(): void
    {
        Talk::factory()->count(3)->create();
        Playlist::factory()->create();

        $this->get('/bulk-download')->assertOk();
        $this->get('/playlist')->assertOk();
    }
}
