<?php

use App\Http\Middleware\Role;
use App\Providers\AuthServiceProvider;
use App\Providers\OlapServiceProvider;
use Dotenv\Dotenv;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$basePath = dirname(__DIR__);

if (is_file($basePath.'/env/olap.env')) {
    Dotenv::createMutable($basePath.'/env', 'olap.env')->safeLoad();
}

return Application::configure(basePath: $basePath)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $time = config('olap.schedule_time');

        if (! empty($time)) {
            $schedule->command('olap:run-etl')
                ->dailyAt($time)
                ->name('olap-nightly-etl')
                ->onOneServer()
                ->withoutOverlapping()
                ->description('Nightly OLAP data mart refresh');
        }
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => Role::class,
        ]);
    })
    ->withProviders([
        AuthServiceProvider::class,
        OlapServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
