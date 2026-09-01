<?php

namespace App\Jobs;

use App\Domain\Aggregator\AggregatorReportsService;
use App\Models\ReportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Asynchronous report generation (Stage H §62). The service's generate() is
 * idempotent, so redelivery never produces a second artifact.
 */
class AggregatorReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(public readonly int $reportJobId)
    {
    }

    public function handle(AggregatorReportsService $reports): void
    {
        $job = ReportJob::query()->find($this->reportJobId);

        if ($job === null) {
            return;
        }

        $reports->generate($job);
    }
}
