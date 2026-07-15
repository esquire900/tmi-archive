<?php

namespace Database\Factories;

use App\Enums\MetricType;
use App\Models\Talk;
use App\Models\TalkMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TalkMetric>
 */
class TalkMetricFactory extends Factory
{
    protected $model = TalkMetric::class;

    public function definition(): array
    {
        return [
            'talk_id' => Talk::factory(),
            'user_id' => null,
            'metric_type' => MetricType::View,
            'ip' => fake()->ipv4(),
            'user_agent' => 'Mozilla/5.0',
            'is_bot' => false,
            'created_at' => now(),
        ];
    }

    public function download(): static
    {
        return $this->state(fn () => ['metric_type' => MetricType::Download]);
    }

    public function bot(): static
    {
        return $this->state(fn () => ['is_bot' => true]);
    }
}
