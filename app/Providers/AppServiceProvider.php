<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\CaseModel;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Карта політик (якщо використовуєте Policy-класи – додайте тут).
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // CaseModel::class => \App\Policies\CasePolicy::class,
    ];

    public function boot(): void
{
    $this->registerPolicies();

    // Аналітика: лише адміністратор або виконавець
    Gate::define('view-analytics', fn($user) => $user->isAdmin() || $user->isExecutor());

    // Список усіх справ (реєстр): лише адміністратор або виконавець
    Gate::define('list-cases', fn($user) => $user->isAdmin() || $user->isExecutor());

    // Створення справи: адміністратор, виконавець, заявник
    Gate::define('create-case', fn($user) => $user->isAdmin() || $user->isExecutor() || $user->isApplicant());

    // Перегляд конкретної справи
    Gate::define('view-case', function($user, \App\Models\CaseModel $case){
        if ($user->isAdmin()) return true;
        // власник/призначений виконавець/переглядач
        return $case->user_id === $user->id
            || $case->executor_id === $user->id
            || $user->isViewer();
    });

    Gate::define('view-analytics', function ($user) {
    return in_array($user->role, ['admin', 'executor']);
    });

}

}
