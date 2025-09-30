<?php

namespace Database\Factories;

use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        $role = fake()->randomElement(['applicant', 'executor', 'viewer']);
        $positionId = optional(Position::firstWhere('slug', $role))->id;

        return [
            'name' => fake()->name(),
            'email'=> fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
            'role' => $role,
            'position_id' => $positionId,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => $this->stateForRole('admin'));
    }

    public function executor(): static
    {
        return $this->state(fn () => $this->stateForRole('executor'));
    }

    public function viewer(): static
    {
        return $this->state(fn () => $this->stateForRole('viewer'));
    }

    public function applicant(): static
    {
        return $this->state(fn () => $this->stateForRole('applicant'));
    }

    protected function stateForRole(string $role): array
    {
        return [
            'role' => $role,
            'position_id' => optional(Position::firstWhere('slug', $role))->id,
        ];
    }
}
