<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'code' => 'ROLE_' . strtoupper($this->faker->word()),
            'name' => $this->faker->jobTitle(),
            'description' => $this->faker->sentence(),
        ];
    }

    public function agent(): static
    {
        return $this->state([
            'code' => 'ROLE_AGENT',
            'name' => 'Commercial Agent',
            'description' => 'Commercial agent role',
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state([
            'code' => 'ROLE_SUPER_ADMIN',
            'name' => 'Super Administrator',
            'description' => 'Super admin with full access',
        ]);
    }
}
