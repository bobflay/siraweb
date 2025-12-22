<?php

namespace Database\Factories;

use App\Models\Zone;
use App\Models\BaseCommerciale;
use Illuminate\Database\Eloquent\Factories\Factory;

class ZoneFactory extends Factory
{
    protected $model = Zone::class;

    public function definition(): array
    {
        return [
            'code' => 'ZONE-' . $this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->city() . ' Zone',
            'base_commerciale_id' => BaseCommerciale::factory(),
            'city' => $this->faker->city(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'is_active' => true,
        ];
    }
}
