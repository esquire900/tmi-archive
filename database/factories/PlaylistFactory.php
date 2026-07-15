<?php

namespace Database\Factories;

use App\Models\Playlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Playlist>
 */
class PlaylistFactory extends Factory
{
    protected $model = Playlist::class;

    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(3), '.'),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'first_recording_date' => fake()->date(),
        ];
    }
}
