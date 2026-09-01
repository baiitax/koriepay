<?php

namespace App\Domain\Aggregator;

use App\Domain\Aggregator\ReportFormats\ReportFormatter;
use App\Jobs\AggregatorReportJob;
use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\AggregatorSettlement;
use App\Models\AuditLog;
use App\Models\CommissionEntry;
use App\Models\LiquidityRequest;
use App\Models\ReportJob;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AGGREGATOR CONSOLE — Stage H (report center, §62, §65).
 *
 * Asynchronous generation: request() enqueues AggregatorReportJob; the job
 * drives generate() which produces a real CSV/XLSX/PDF artifact and stores
 * it. Every lifecycle event is audited (report.requested / processing /
 * completed / failed). All data is tenant-scoped by aggregator_id and every
 * number comes from real records (or the derived daily read model).
 */
class AggregatorReportsService
{
    public function __construct(private readonly AggregatorTenantService $tenant)
    {
    }

    /** Report catalog — honest metadata for the UI. */
    public function catalog(): array
    {
        return [
            ['type' => 'agent', 'label' => 'Agent report', 'description' => 'Every agent in the network with live operation volume and commission.', 'formats' => ReportJob::FORMATS],
            ['type' => 'transaction', 'label' => 'Transaction report', 'description' => 'Posted/failed operations with amounts, fees and commission.', 'formats' => ReportJob::FORMATS],
            ['type' => 'commission', 'label' => 'Commission report', 'description' => 'Commission entries accruals/adjustments/reversals in range.', 'formats' => ReportJob::FORMATS],
            ['type' => 'liquidity', 'label' => 'Liquidity report', 'description' => 'Liquidity requests with risk levels and final status.', 'formats' => ReportJob::FORMATS],
            ['type' => 'settlement', 'label' => 'Settlement report', 'description' => 'Settlement batches with gross/adjustments/net and reconciliation.', 'formats' => ReportJob::FORMATS],
            ['type' => 'risk', 'label' => 'Risk report', 'description' => 'Risk alerts with severity, status and workflow state.', 'formats' => ReportJob::FORMATS],
            ['type' => 'kyc', 'label' => 'KYC report', 'description' => 'Agent vs linked-user KYC status across the network.', 'formats' => ReportJob::FORMATS],
            ['type' => 'network_growth', 'label' => 'Network growth report', 'description' => 'Daily volume, activity and new-agent series from the read model.', 'formats' => ReportJob::FORMATS],
        ];
    }

    /**
     * Enqueue an async report generation. Returns the job row (status will be
     * 'ready' immediately under sync queues, 'queued' otherwise).
     */
    public function request(Aggregator $aggregator, User $actor, string $type, string $format, ?string $dateFrom = null, ?string $dateTo = null): ReportJob
    {
        if (! in_array($type, ReportJob::TYPES, true)) {
            throw new \InvalidArgumentException("Unsupported report type [{$type}].");
        }
        if (! in_array($format, ReportJob::FORMATS, true)) {
            throw new \InvalidArgumentException("Unsupported report format [{$format}].");
        }

        $job = ReportJob::create([
            'reference' => 'RPT-'.strtoupper(Str::random(8)),
            'aggregator_id' => $aggregator->id,
            'type' => $type,
            'format' => $format,
            'date_from' => $dateFrom ?: now()->subDays(30)->toDateString(),
            'date_to' => $dateTo ?: now()->toDateString(),
            'status' => ReportJob::STATUS_QUEUED,
            'requested_by' => $actor->id,
            'requested_at' => now(),
        ]);

        AuditLog::record('report.requested', $actor->id, $actor->id, [
            'description' => "Report [{$type} / {$format}] requested for {$job->date_from} → {$job->date_to}.", 'event_type' => 'operations',
            'metadata' => ['report_job_id' => $job->id, 'reference' => $job->reference, 'type' => $type, 'format' => $format],
        ]);

        AggregatorReportJob::dispatch($job->id);

        return $job;
    }

    /** Tenant-scoped job list, newest first. */
    public function jobs(Aggregator $aggregator, int $perPage = 10, int $page = 1): array
    {
        $query = ReportJob::query()->where('aggregator_id', $aggregator->id)->latest('id');
        $total = (clone $query)->count();

        return [
            'jobs' => $query->forPage($page, $perPage)->get()->map(fn (ReportJob $j) => $this->present($j))->all(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Prepare a tenant-authorized download of a ready report.
     *
     * @return array{path: string, name: string}
     */
    public function download(ReportJob $job, Aggregator $aggregator, User $actor): array
    {
        abort_unless((int) $job->aggregator_id === (int) $aggregator->id, 404, 'Report not found for this network.');
        abort_unless($job->status === ReportJob::STATUS_READY, 409, 'Report is not ready to download.');
        abort_unless($job->file_path !== null && Storage::disk('local')->exists($job->file_path), 404, 'Report file is not available.');

        AuditLog::record('report.downloaded', $actor->id, $actor->id, [
            'description' => "Report [{$job->reference} / {$job->type} / {$job->format}] downloaded.", 'event_type' => 'operations',
            'metadata' => ['report_job_id' => $job->id, 'reference' => $job->reference],
        ]);

        return [
            'path' => $job->file_path,
            'name' => $job->reference.'.'.$job->format,
        ];
    }

    /**
     * Generate the artifact for a job. Idempotent: ready/failed jobs are left
     * untouched on re-entry (queue redelivery safe). Writes a real file and
     * audits completion or failure.
     */
    public function generate(ReportJob $job): ReportJob
    {
        if (in_array($job->status, [ReportJob::STATUS_READY, ReportJob::STATUS_FAILED], true)) {
            return $job;
        }

        $job->forceFill(['status' => ReportJob::STATUS_PROCESSING])->save();
        AuditLog::record('report.processing', $job->requested_by, $job->requested_by ?? $job->aggregator_id, [
            'description' => "Report [{$job->reference}] generation started.", 'event_type' => 'operations',
            'metadata' => ['report_job_id' => $job->id, 'reference' => $job->reference],
        ]);

        try {
            [$headers, $rows] = $this->data($job);

            $bytes = (new ReportFormatter())->format($job->format, ucfirst($job->type).' report', $headers, $rows);

            $relative = 'reports/'.$job->reference.'.'.$job->format;
            Storage::disk('local')->put($relative, $bytes);

            $job->forceFill([
                'status' => ReportJob::STATUS_READY,
                'file_path' => $relative,
                'row_count' => count($rows),
                'error' => null,
                'completed_at' => now(),
            ])->save();

            AuditLog::record('report.completed', $job->requested_by, $job->requested_by ?? $job->aggregator_id, [
                'description' => "Report [{$job->reference} / {$job->type} / {$job->format}] generated (".count($rows).' rows).',
                'event_type' => 'operations',
                'metadata' => ['report_job_id' => $job->id, 'reference' => $job->reference, 'rows' => count($rows), 'format' => $job->format],
            ]);
        } catch (\Throwable $e) {
            $job->forceFill([
                'status' => ReportJob::STATUS_FAILED,
                'error' => Str::limit($e->getMessage(), 500),
                'completed_at' => now(),
            ])->save();

            AuditLog::record('report.failed', $job->requested_by, $job->requested_by ?? $job->aggregator_id, [
                'description' => "Report [{$job->reference}] failed: ".Str::limit($e->getMessage(), 200), 'event_type' => 'operations',
                'metadata' => ['report_job_id' => $job->id, 'reference' => $job->reference],
            ]);
        }

        return $job->refresh();
    }

    /**
     * @return array{0: array<string>, 1: array<int, array<int, mixed>>}
     */
    protected function data(ReportJob $job): array
    {
        $aggregator = Aggregator::query()->findOrFail($job->aggregator_id);
        $agentIds = $this->tenant->agentIds($aggregator);
        $from = $job->date_from;
        $to = $job->date_to ? \Illuminate\Support\Carbon::parse($job->date_to)->endOfDay() : null;

        return match ($job->type) {
            'agent' => $this->agentRows($aggregator, $agentIds),
            'transaction' => $this->transactionRows($agentIds, $from, $to),
            'commission' => $this->commissionRows($aggregator, $agentIds, $from, $to),
            'liquidity' => $this->liquidityRows($aggregator, $from, $to),
            'settlement' => $this->settlementRows($aggregator),
            'risk' => $this->riskRows($aggregator, $agentIds),
            'kyc' => $this->kycRows($aggregator, $agentIds),
            'network_growth' => $this->growthRows($aggregator, $from, $to),
            default => throw new \InvalidArgumentException("Unsupported report type [{$job->type}]."),
        };
    }

    /** @return array{0: array<string>, 1: array<int, array<int, mixed>>} */
    protected function agentRows(Aggregator $aggregator, array $agentIds): array
    {
        $rows = [];
        foreach (Agent::whereIn('id', $agentIds)->with('user')->get() as $agent) {
            $ops = AgencyOperation::where('agent_id', $agent->id);
            $rows[] = [
                $agent->agent_code,
                $agent->user?->name ?? $agent->agent_code,
                $agent->status,
                $agent->kyc_status,
                $agent->city ?? '',
                $agent->country_iso2,
                (string) (clone $ops)->where('status', 'posted')->sum('amount'),
                (string) (clone $ops)->where('status', 'posted')->count(),
                CommissionEntry::where('beneficiary_type', 'agent')->where('beneficiary_id', $agent->id)->sum('amount'),
            ];
        }

        return [['agent_code', 'name', 'status', 'kyc_status', 'city', 'country', 'volume', 'posted_ops', 'commission'], $rows];
    }

    /** @return array{0: array<string>, 1: array<int, array<int, mixed>>} */
    protected function transactionRows(array $agentIds, ?string $from, $to): array
    {
        $rows = [];
        AgencyOperation::whereIn('agent_id', $agentIds)
            ->where(fn ($q) => $q->when($from, fn ($w) => $w->where('created_at', '>=', $from))->when($to, fn ($w) => $w->where('created_at', '<=', $to)))
            ->orderBy('created_at')
            ->get()
            ->each(function (AgencyOperation $op) use (&$rows) {
                $rows[] = [$op->reference, (string) $op->agent_id, $op->operation_type, $op->amount, $op->fee, $op->commission_amount, $op->status, $op->created_at?->toDateTimeString()];
            });

        return [['reference', 'agent_id', 'type', 'amount', 'fee', 'commission', 'status', 'created_at'], $rows];
    }

    /** @return array{0: array<string>, 1: array<int, array<int, mixed>>} */
    protected function commissionRows(Aggregator $aggregator, array $agentIds, ?string $from, $to): array
    {
        $rows = [];
        CommissionEntry::where(function ($q) use ($aggregator, $agentIds) {
            $q->where(fn ($w) => $w->where('beneficiary_type', 'aggregator')->where('beneficiary_id', $aggregator->id))
                ->orWhere(fn ($w) => $w->where('beneficiary_type', 'agent')->whereIn('beneficiary_id', $agentIds));
        })
            ->where(fn ($q) => $q->when($from, fn ($w) => $w->where('created_at', '>=', $from))->when($to, fn ($w) => $w->where('created_at', '<=', $to)))
            ->orderBy('created_at')
            ->get()
            ->each(function (CommissionEntry $e) use (&$rows) {
                $rows[] = [$e->beneficiary_type, (string) $e->beneficiary_id, (string) $e->rule_id, $e->currency_code, $e->amount, $e->status, $e->created_at?->toDateTimeString()];
            });

        return [['beneficiary_type', 'beneficiary_id', 'rule_id', 'currency', 'amount', 'status', 'created_at'], $rows];
    }

    /** @return array{0: array<string>, 1: array<int, array<int, mixed>>} */
    protected function liquidityRows(Aggregator $aggregator, ?string $from, $to): array
    {
        $rows = [];
        LiquidityRequest::where('aggregator_id', $aggregator->id)
            ->where(fn ($q) => $q->when($from, fn ($w) => $w->where('created_at', '>=', $from))->when($to, fn ($w) => $w->where('created_at', '<=', $to)))
            ->orderBy('created_at')
            ->get()
            ->each(function (LiquidityRequest $l) use (&$rows) {
                $rows[] = [$l->reference, (string) $l->agent_id, $l->currency_code, $l->amount, $l->status, (string) ($l->risk_level ?? 'not_assessed'), $l->created_at?->toDateTimeString()];
            });

        return [['reference', 'agent_id', 'currency', 'amount', 'status', 'risk_level', 'created_at'], $rows];
    }

    /** @return array{0: array<string>, 1: array<int, array<int, mixed>>} */
    protected function settlementRows(Aggregator $aggregator): array
    {
        $rows = [];
        AggregatorSettlement::where('aggregator_id', $aggregator->id)->orderBy('created_at')->get()
            ->each(function (AggregatorSettlement $s) use (&$rows) {
                $rows[] = [$s->reference, $s->currency_code, $s->status, $s->gross_amount, $s->adjustments, $s->net_amount, $s->expected_amount, (string) ($s->actual_amount ?? ''), $s->created_at?->toDateTimeString()];
            });

        return [['reference', 'currency', 'status', 'gross', 'adjustments', 'net', 'expected', 'actual', 'created_at'], $rows];
    }

    /** @return array{0: array<string>, 1: array<int, array<int, mixed>>} */
    protected function riskRows(Aggregator $aggregator, array $agentIds): array
    {
        $rows = [];
        RiskAlert::where(function ($q) use ($aggregator, $agentIds) {
            $q->where(fn ($w) => $w->where('entity_type', 'agent')->whereIn('entity_id', $agentIds))
                ->orWhere(fn ($w) => $w->where('entity_type', 'aggregator')->where('entity_id', $aggregator->id));
        })->orderBy('created_at')->get()->each(function (RiskAlert $a) use (&$rows) {
            $rows[] = [$a->reference, $a->category, $a->severity, $a->status, (string) $a->risk_score, $a->message, $a->created_at?->toDateTimeString()];
        });

        return [['reference', 'category', 'severity', 'status', 'risk_score', 'message', 'created_at'], $rows];
    }

    /** @return array{0: array<string>, 1: array<int, array<int, mixed>>} */
    protected function kycRows(Aggregator $aggregator, array $agentIds): array
    {
        $rows = [];
        foreach (Agent::whereIn('id', $agentIds)->with('user')->get() as $agent) {
            $rows[] = [$agent->agent_code, $agent->user?->name ?? '', $agent->kyc_status, (string) ($agent->user?->kyc_status ?? 'unverified'), $agent->status, $agent->city ?? ''];
        }

        return [['agent_code', 'name', 'agent_kyc', 'user_kyc', 'status', 'city'], $rows];
    }

    /** @return array{0: array<string>, 1: array<int, array<int, mixed>>} */
    protected function growthRows(Aggregator $aggregator, ?string $from, $to): array
    {
        $rows = [];
        app(AggregatorInsightsService::class)->dailySeries($aggregator, $from, $to)
            ->each(function (array $day) use (&$rows) {
                $rows[] = [$day['date'], (string) $day['total_ops'], $day['volume'], (string) $day['active_agents'], (string) $day['new_agents'], $day['success_rate']];
            });

        return [['date', 'ops', 'volume', 'active_agents', 'new_agents', 'success_rate'], $rows];
    }

    protected function present(ReportJob $job): array
    {
        return [
            'id' => $job->id,
            'reference' => $job->reference,
            'type' => $job->type,
            'format' => $job->format,
            'date_from' => $job->date_from?->toDateString(),
            'date_to' => $job->date_to?->toDateString(),
            'status' => $job->status,
            'row_count' => $job->row_count,
            'error' => $job->error,
            'requested_by' => $job->requester?->name,
            'requested_at' => $job->requested_at?->toIso8601String(),
            'completed_at' => $job->completed_at?->toIso8601String(),
            'downloadable' => $job->status === ReportJob::STATUS_READY && $job->file_path !== null,
            'file_path' => $job->file_path,
        ];
    }
}
