<?php

namespace Database\Factories;

use App\Models\CaseDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;

class CaseDocumentFactory extends Factory
{
    protected $model = CaseDocument::class;

    public function definition(): array
    {
        $timestamp = $this->faker->dateTimeBetween('-6 months', 'now');
        $extension = $this->faker->randomElement(['pdf', 'docx', 'jpg']);

        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        $title = Str::title($this->faker->words($this->faker->numberBetween(2, 4), true)) . '.' . $extension;

        return [
            'title' => $title,
            'path' => 'cases/demo/' . Str::uuid() . '.' . $extension,
            'file_size' => $this->faker->numberBetween(15_000, 250_000),
            'mime_type' => $mime,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    protected function withFaker(): FakerGenerator
    {
        return FakerFactory::create('uk_UA');
    }
}
