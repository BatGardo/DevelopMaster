<?php

namespace Database\Factories;

use App\Models\CaseDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CaseDocumentFactory extends Factory
{
    protected $model = CaseDocument::class;

    public function definition(): array
    {
        $timestamp = fake()->dateTimeBetween('-6 months', 'now');
        $extension = fake()->randomElement(['pdf', 'docx', 'jpg']);

        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return [
            'title' => Str::title(fake()->words(rand(2, 4), true)).'.'.$extension,
            'path' => 'cases/demo/'.Str::uuid().'.'.$extension,
            'file_size' => fake()->numberBetween(15_000, 250_000),
            'mime_type' => $mime,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }
}
