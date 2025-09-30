<?php

namespace App\Jobs\Olap;

use App\Services\Olap\OlapEtlService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessOlapBatch implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(protected bool $runUntilEmpty = false)
    {
        $this->onQueue(config('olap.queue'));
    }

    public function handle(OlapEtlService $etl): void
    {
        $processed = $etl->syncBatch(config('olap.batch_size'));

        if ($processed === 0) {
            return;
        }

        $shouldContinue = $this->runUntilEmpty || config('olap.run_until_empty');

        if ($shouldContinue) {
            static::dispatch($this->runUntilEmpty)->onQueue(config('olap.queue'));
        }
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('olap-etl'))
                ->releaseAfter(10)
                ->expireAfter(600),
        ];
    }
}
