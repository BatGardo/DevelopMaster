<?php

namespace Database\Factories;

use App\Models\CaseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Support\Carbon;

class CaseModelFactory extends Factory
{
    protected $model = CaseModel::class;

    public function definition(): array
    {
        $status = Arr::random(['new', 'in_progress', 'done', 'closed']);
        $createdAt = Carbon::instance($this->faker->dateTimeBetween('-9 months', '-1 month'));
        $updatedAt = $status === 'new'
            ? Carbon::instance($this->faker->dateTimeBetween($createdAt, 'now'))
            : Carbon::instance($this->faker->dateTimeBetween($createdAt, '+2 months'));

        $deadline = null;
        if (in_array($status, ['new', 'in_progress'], true)) {
            $deadline = Carbon::instance($this->faker->dateTimeBetween('+2 weeks', '+6 months'));
        } elseif ($this->faker->boolean(40)) {
            $deadline = Carbon::instance($this->faker->dateTimeBetween($createdAt, '+2 months'));
        }

        $claimant = 'ТОВ «' . Str::title($this->faker->words(2, true)) . '»';
        $debtor = $this->faker->name();

        return [
            'title' => Str::ucfirst($this->faker->sentence(6)),
            'description' => $this->faker->paragraphs($this->faker->numberBetween(2, 4), true),
            'status' => $status,
            'claimant_name' => $claimant,
            'debtor_name' => $debtor,
            'deadline_at' => $deadline,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    protected function withFaker(): FakerGenerator
    {
        return FakerFactory::create('uk_UA');
    }
}
