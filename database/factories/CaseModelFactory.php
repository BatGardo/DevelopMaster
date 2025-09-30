<?php

namespace Database\Factories;

use App\Models\CaseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class CaseModelFactory extends Factory
{
    protected $model = CaseModel::class;

    public function definition(): array
    {
        $status = Arr::random(['new', 'in_progress', 'done', 'closed']);
        $createdAt = fake()->dateTimeBetween('-8 months', 'now');
        $updatedAt = $status === 'done'
            ? fake()->dateTimeBetween($createdAt, '+2 months')
            : fake()->dateTimeBetween($createdAt, 'now');

        return [
            'title' => fake()->sentence(6),
            'description' => fake()->paragraphs(rand(1, 3), true),
            'status' => $status,
            'claimant_name' => fake()->company(),
            'debtor_name' => fake()->name(),
            'deadline_at' => fake()->boolean(70)
                ? fake()->dateTimeBetween('+3 days', '+4 months')
                : null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }
}
