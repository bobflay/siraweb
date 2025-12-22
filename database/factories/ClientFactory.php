<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\BaseCommerciale;
use App\Models\Zone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'code' => 'CLI-' . $this->faker->unique()->numberBetween(10000, 99999),
            'name' => $this->faker->company(),
            'type' => $this->faker->randomElement(['Boutique', 'Supermarché', 'Demi-grossiste', 'Grossiste', 'Distributeur', 'Autre']),
            'potential' => $this->faker->randomElement(['A', 'B', 'C']),
            'base_commerciale_id' => BaseCommerciale::factory(),
            'zone_id' => Zone::factory(),
            'created_by' => User::factory(),
            'manager_name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'whatsapp' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'city' => $this->faker->city(),
            'district' => $this->faker->streetName(),
            'address_description' => $this->faker->address(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'visit_frequency' => $this->faker->randomElement(['weekly', 'biweekly', 'monthly', 'other']),
            'is_active' => true,
        ];
    }
}
