<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PositionSeeder::class);

        $positionIds = Position::query()->pluck('id', 'slug');
        $password = Hash::make('password');

        $namedAccounts = Collection::make([
            ['role' => 'admin',     'name' => 'Олена Адміністратор',  'email' => 'admin@sokil.test'],
            ['role' => 'admin',     'name' => 'Юрій Керівник',        'email' => 'admin2@sokil.test'],
            ['role' => 'executor',  'name' => 'Ганна Виконавиця',     'email' => 'executor1@sokil.test'],
            ['role' => 'executor',  'name' => 'Ростислав Виконавець', 'email' => 'executor2@sokil.test'],
            ['role' => 'viewer',    'name' => 'Світлана Спостерігач', 'email' => 'viewer1@sokil.test'],
            ['role' => 'applicant', 'name' => 'Микола Заявник',       'email' => 'applicant1@sokil.test'],
            ['role' => 'applicant', 'name' => 'Леся Підприємиця',     'email' => 'applicant2@sokil.test'],
        ]);

        $seededUsers = $namedAccounts->map(function (array $account) use ($password, $positionIds) {
            return User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $password,
                    'role' => $account['role'],
                    'position_id' => $positionIds[$account['role']] ?? null,
                    'email_verified_at' => now(),
                ]
            );
        });

        $additionalAdmins = User::factory()->count(1)->admin()->create();
        $additionalExecutors = User::factory()->count(8)->executor()->create();
        $additionalViewers = User::factory()->count(5)->viewer()->create();
        $additionalApplicants = User::factory()->count(12)->applicant()->create();

        $admins = $seededUsers->where('role', 'admin')->merge($additionalAdmins);
        $executors = $seededUsers->where('role', 'executor')->merge($additionalExecutors);
        $viewers = $seededUsers->where('role', 'viewer')->merge($additionalViewers);
        $applicants = $seededUsers->where('role', 'applicant')->merge($additionalApplicants);

        $authors = $admins->merge($executors)->merge($viewers);

        Post::factory()->count(24)->make()->each(function (Post $post) use ($authors) {
            $post->user_id = $authors->random()->id;
            $post->save();
        });

        $this->call(UkrainianCaseSeeder::class);
    }
}