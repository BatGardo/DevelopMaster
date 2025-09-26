<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email'=> 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        User::factory(10)->create()->each(function ($user) {
            Post::factory()->count(rand(1,4))->create([
                'user_id' => $user->id,
            ]);
        });

        Post::factory()->count(5)->create([
            'user_id' => $admin->id,
        ]);
        $this->call(PositionSeeder::class);
    }
}
