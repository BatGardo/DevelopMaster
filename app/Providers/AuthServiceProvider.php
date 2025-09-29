<?php

namespace App\Providers;

use App\Models\CaseModel;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('view-analytics', function ($user) {
            return $user->isAdmin() || $user->isExecutor() || $user->role === 'analyst';
        });

        Gate::define('list-cases', function ($user) {
            return $user->isAdmin() || $user->isExecutor();
        });

        Gate::define('create-case', function ($user) {
            return $user->isAdmin() || $user->isExecutor() || $user->isApplicant();
        });

        Gate::define('view-case', function ($user, CaseModel $case) {
            return $user->isAdmin()
                || $case->user_id === $user->id
                || $case->executor_id === $user->id
                || $user->isViewer();
        });

        Gate::define('update-case', function ($user, CaseModel $case) {
            return $user->isAdmin() || $case->executor_id === $user->id;
        });
    }
}
