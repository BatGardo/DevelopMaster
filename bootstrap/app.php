<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\Role; // <— ІМПОРТ middleware
use App\Providers\AuthServiceProvider; // ⬅️ ДОДАЛИ

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => Role::class, // <— ось тут
        ]);
    })
        ->withProviders([
        AuthServiceProvider::class,   // ⬅️ ДОДАЛИ
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
