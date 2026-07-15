<?php

namespace Database\Factories;

use App\Models\Talk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Talk>
 */
class TalkFactory extends Factory
{
    protected $model = Talk::class;

    public function definition(): array
    {
        $id = fake()->unique()->numberBetween(1, 999999);

        return [
            'title' => rtrim(fake()->sentence(4), '.'),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'audio_original' => $id.'/original.mp3',
            'audio_cleaned' => $id.'/cleaned.mp3',
            'recorded_date' => fake()->date(),
            'original_file_name' => fake()->word().'.mp3',
            'transcription' => null,
            'whisper_transcription' => null,
            'audio_length' => fake()->numberBetween(120, 5400),
        ];
    }

    public function withoutAudio(): static
    {
        return $this->state(fn () => [
            'audio_original' => null,
            'audio_cleaned' => null,
        ]);
    }

    public function withWhisper(): static
    {
        return $this->state(fn () => [
            'whisper_transcription' => json_encode([
                ['start' => 0.0, 'end' => 2.5, 'text' => 'Hello and welcome.'],
                ['start' => 2.5, 'end' => 5.0, 'text' => 'Today we talk about mindfulness.'],
            ]),
        ]);
    }
}
