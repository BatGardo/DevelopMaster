<?php

namespace Database\Seeders;

use App\Models\CaseAction;
use App\Models\CaseDocument;
use App\Models\CaseModel;
use App\Models\Position;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        $caseOwners = $applicants->values();
        $caseExecutors = $executors->values();

        CaseModel::factory()->count(60)->make()->each(function (CaseModel $case) use ($caseOwners, $caseExecutors) {
            $owner = $caseOwners->random();
            $executor = $caseExecutors->random();

            $case->user_id = $owner->id;
            $case->executor_id = $executor->id;

            if ($case->status === 'closed' && $case->updated_at < $case->created_at) {
                $case->updated_at = $case->created_at->copy()->addDays(rand(5, 60));
            }

            $case->save();

            CaseAction::create([
                'case_id' => $case->id,
                'user_id' => $owner->id,
                'type' => 'created',
                'notes' => 'Case registered via demo seeder',
                'created_at' => $case->created_at,
                'updated_at' => $case->created_at,
            ]);

            $participants = collect([$owner, $executor]);

            CaseAction::factory()->count(rand(2, 6))->make()->each(function (CaseAction $action) use ($case, $participants) {
                $actor = $participants->random();
                $action->case_id = $case->id;
                $action->user_id = $actor->id;
                $action->created_at = $action->created_at ?? now();
                $action->updated_at = $action->created_at;
                $action->save();
            });

            CaseDocument::factory()->count(rand(0, 3))->make()->each(function (CaseDocument $document) use ($case, $participants) {
                $uploader = $participants->random();
                $path = Str::replaceFirst('cases/demo/', 'cases/'.$case->id.'/', $document->path);

                Storage::disk('public')->put($path, 'Demo document for case #'.$case->id);

                $document->case_id = $case->id;
                $document->uploaded_by = $uploader->id;
                $document->path = $path;
                $document->created_at = $document->created_at ?? now();
                $document->updated_at = $document->created_at;
                $document->save();
            });
        });
    }
}
