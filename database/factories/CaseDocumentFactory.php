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

        return [
            'title' => Str::title(fake()->words(rand(2, 4), true)).'.'.$extension,
            'path' => 'cases/demo/'.Str::uuid().'.'.$extension,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }
}
