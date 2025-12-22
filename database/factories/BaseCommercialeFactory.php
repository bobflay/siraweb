<?php

namespace Database\Factories;

use App\Models\BaseCommerciale;
use Illuminate\Database\Eloquent\Factories\Factory;

class BaseCommercialeFactory extends Factory
{
    protected $model = BaseCommerciale::class;

    public function definition(): array
    {
        return [
            'code' => 'BASE-' . $this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->city() . ' Base',
            'description' => $this->faker->sentence(),
            'city' => $this->faker->city(),
            'region' => $this->faker->state(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'default_currency' => 'XOF',
            'default_tax_rate' => 0.00,
            'allow_discount' => true,
            'max_discount_percent' => 10.00,
            'order_cutoff_time' => '17:00:00',
            'is_active' => true,
        ];
    }
}
