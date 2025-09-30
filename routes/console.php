<?php

use App\Jobs\Olap\ProcessOlapBatch;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('olap:run-etl {--until-empty}', function () {
    $runUntilEmpty = (bool) $this->option('until-empty');

    ProcessOlapBatch::dispatch($runUntilEmpty)->onQueue(config('olap.queue'));

    $this->info('OLAP ETL job dispatched.');
})->purpose('Dispatch the OLAP ETL queue job');
