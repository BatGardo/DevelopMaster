<?php

namespace Database\Factories;

use App\Models\CaseAction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;

class CaseActionFactory extends Factory
{
    protected $model = CaseAction::class;

    public function definition(): array
    {
        $timestamp = $this->faker->dateTimeBetween('-6 months', 'now');

        return [
            'type' => $this->faker->randomElement([
                'created',
                'comment',
                'document_added',
                'hearing_scheduled',
                'payment_received',
                'reminder_sent',
            ]),
            'notes' => $this->faker->optional(0.7)->sentence(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    protected function withFaker(): FakerGenerator
    {
        return FakerFactory::create('uk_UA');
    }
}
