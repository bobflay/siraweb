<?php

namespace Database\Factories;

use App\Models\Visit;
use App\Models\Client;
use App\Models\User;
use App\Models\BaseCommerciale;
use App\Models\Zone;
use App\Models\RoutingItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'base_commerciale_id' => BaseCommerciale::factory(),
            'zone_id' => Zone::factory(),
            'routing_item_id' => null,
            'started_at' => now(),
            'ended_at' => null,
            'duration_seconds' => null,
            'status' => 'started',
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = $attributes['started_at'] ?? now()->subHours(2);
            $endedAt = now();

            return [
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_seconds' => $endedAt->diffInSeconds($startedAt),
                'status' => 'completed',
            ];
        });
    }

    public function aborted(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = $attributes['started_at'] ?? now()->subHours(1);
            $endedAt = now();

            return [
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_seconds' => $endedAt->diffInSeconds($startedAt),
                'status' => 'aborted',
            ];
        });
    }
}
