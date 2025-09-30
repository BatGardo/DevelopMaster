<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PositionSeeder::class);

        $positionIds = Position::query()->pluck('id', 'slug');
        $password = Hash::make('password');

        $seededUsers = collect();

        $namedAccounts = [
            ['role' => 'admin',     'name' => 'Olena Administrator', 'email' => 'admin@sokil.test'],
            ['role' => 'executor',  'name' => 'Danylo Executor',     'email' => 'executor1@sokil.test'],
            ['role' => 'executor',  'name' => 'Inna Executor',       'email' => 'executor2@sokil.test'],
            ['role' => 'viewer',    'name' => 'Vira Viewer',         'email' => 'viewer@sokil.test'],
            ['role' => 'applicant', 'name' => 'Petro Applicant',     'email' => 'applicant1@sokil.test'],
            ['role' => 'applicant', 'name' => 'Maryna Applicant',    'email' => 'applicant2@sokil.test'],
        ];

        foreach ($namedAccounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $password,
                    'role' => $account['role'],
                    'position_id' => $positionIds[$account['role']] ?? null,
                    'email_verified_at' => now(),
                ]
            );

            $seededUsers->push($user);
        }

        $admins     = $seededUsers->where('role', 'admin');
        $executors  = $seededUsers->where('role', 'executor');
        $viewers    = $seededUsers->where('role', 'viewer');
        $applicants = $seededUsers->where('role', 'applicant');

        $executors = $executors->merge(
            User::factory()->count(3)->executor()->create()
        );

        $viewers = $viewers->merge(
            User::factory()->count(2)->viewer()->create()
        );

        $applicants = $applicants->merge(
            User::factory()->count(6)->applicant()->create()
        );

        $authors = $admins->merge($viewers)->merge($executors);

        Post::factory()->count(20)->make()->each(function (Post $post) use ($authors) {
            $post->user_id = $authors->random()->id;
            $post->save();
        });

        $this->call(UkrainianCaseSeeder::class);
    }
}
