<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'Адміністратор', 'slug' => 'admin'],
            ['name' => 'Виконавець', 'slug' => 'executor'],
            ['name' => 'Спостерігач', 'slug' => 'viewer'],
            ['name' => 'Заявник', 'slug' => 'applicant'],
            ['name' => 'Аналітик', 'slug' => 'analyst'],
        ];

        foreach ($positions as $position) {
            DB::table('positions')->updateOrInsert(
                ['slug' => $position['slug']],
                [
                    'name' => $position['name'],
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}