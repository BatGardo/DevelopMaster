<?php

namespace App\Providers;

use App\Services\Olap\OlapEventRecorder;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class OlapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OlapEventRecorder::class);
    }

    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event) {
            $request = request();

            app(OlapEventRecorder::class)->recordLogin($event->user, [
                'occurred_at' => now(),
                'source' => 'web',
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        });

        Event::listen(Registered::class, function (Registered $event) {
            app(OlapEventRecorder::class)->recordRegistration($event->user, [
                'occurred_at' => now(),
                'registered_at' => now(),
                'source' => 'web',
            ]);
        });
    }
}
