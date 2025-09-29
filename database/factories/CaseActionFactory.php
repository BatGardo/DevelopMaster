<?php

namespace Database\Factories;

use App\Models\CaseAction;
use Illuminate\Database\Eloquent\Factories\Factory;

class CaseActionFactory extends Factory
{
    protected $model = CaseAction::class;

    public function definition(): array
    {
        $timestamp = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'type' => fake()->randomElement([
                'created',
                'comment',
                'document_added',
                'hearing_scheduled',
                'payment_received',
                'reminder_sent',
            ]),
            'notes' => fake()->optional(0.7)->sentence(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }
}
