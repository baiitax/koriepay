<?php

namespace App\Domain\Aggregator;

use App\Domain\Accounting\Exceptions\InsufficientFundsException;
use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\AuditLog;
use App\Models\LiquidityRequest;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * AGGREGATOR CONSOLE — Stage C (liquidity command center + request
 * workflow, §23–28).
 *
 * Money facts come ONLY from the ledger — this service never reads a
 * balance column on the agent/aggregator records. Liquidity requests move
 * money through REAL double-entry postings:
 *
 *   approve (earmark):  DR Platform Cash {CCY}   / CR Pending Liquidity {CCY}
 *   fund    (settle):   DR Pending Liquidity {CCY} / CR Agent Float {CCY}
 *   cancel  (release):  DR Pending Liquidity {CCY} / CR Platform Cash {CCY}
 *
 * Every posting is idempotent and audited; the ledger's balance guards
 * make it impossible to earmark more than operational cash or fund more
 * than was earmarked. Forecasts and demand projections are ALWAYS labelled
 * estimates with their basis stated.
 */
class AggregatorLiquidityService
{
    public function __construct(
        private readonly AggregatorTenantService $tenant,
        private readonly LedgerService $ledger,
    ) {
    }

    // ── Ledger concepts (agent wallet / aggregator wallet / operational
    //    cash / settlement / pending) ────────────────────────────────────

    protected function platformCash(string $currency): LedgerAccount
    {
        return LedgerAccount::firstOrCreate(
            ['account_type' => 'asset', 'currency_code' => strtoupper($currency)],
            ['name' => 'Platform Cash', 'is_system' => true, 'balance' => '0']
        );
    }

    protected function pendingAccount(string $currency): LedgerAccount
    {
        $currency = strtoupper($currency);

        return LedgerAccount::firstOrCreate(
            ['code' => 'PENDING-'.$currency],
            [
                'owner_type' => 'system', 'account_type' => 'liability', 'currency_code' => $currency,
                'name' => 'Pending Liquidity '.$currency, 'is_system' => true, 'balance' => '0',
            ]
        );
    }

    protected function agentFloat(Agent $agent, string $currency): ?LedgerAccount
    {
        return $agent->floatAccount($currency);
    }

    /** @return Collection<int, LedgerAccount> */
    protected function agentFloatAccounts(Aggregator $aggregator): Collection
    {
        return LedgerAccount::query()
            ->where('owner_type', 'agent')
            ->whereIn('owner_id', $this->tenant->agentIds($aggregator))
            ->where('is_active', true)
            ->get();
    }

    protected function aggregatorFloat(Aggregator $aggregator, string $currency): ?LedgerAccount
    {
        return $aggregator->floatAccount($currency);
    }

    // ── Command center (§23–24) ─────────────────────────────────────────

    /**
     * @param  array{currency?: string}  $filters
     */
    public function commandCenter(Aggregator $aggregator, array $filters = []): array
    {
        $agentIds = $this->tenant->agentIds($aggregator);
        $currencies = $this->networkCurrencies($aggregator);
        $currencyFilter = strtoupper((string) ($filters['currency'] ?? ''));

        $position = [];
        $demand = [];
        $forecast = [];
        $alerts = [];

        foreach ($currencies as $currency) {
            if ($currencyFilter !== '' && $currency !== $currencyFilter) {
                continue;
            }
            $position[$currency] = $this->position($aggregator, $currency);
            $demand[$currency] = $this->demand($agentIds, $currency);
            $forecast[$currency] = $this->forecast($aggregator, $currency);
            $alerts[$currency] = $this->currencyAlerts($aggregator, $currency);
        }

        return [
            'currencies' => array_values($currencies),
            'primary_currency' => $this->primaryCurrency($aggregator),
            'position' => $position,
            'demand' => $demand,
            'forecast' => $forecast,
            'alerts' => $alerts,
            'agents' => $this->agentPositions($aggregator),
            'requests_summary' => $this->requestsSummary($aggregator),
            'requests' => $this->requests($aggregator, ['status' => $filters['status'] ?? 'open'], 10, 1),
        ];
    }

    /** Network cash position per ledger concept, one currency at a time. */
    public function position(Aggregator $aggregator, string $currency): array
    {
        $currency = strtoupper($currency);

        $agentFloats = $this->agentFloatAccounts($aggregator)
            ->where('currency_code', $currency)
            ->sum('balance');

        $aggregatorFloat = $this->aggregatorFloat($aggregator, $currency);
        $platform = $this->platformCash($currency);
        $pending = $this->pendingAccount($currency);

        $pendingAmount = (string) $pending->balance;
        $platformCash = (string) $platform->balance;
        $aggregatorFloatAmount = (string) ($aggregatorFloat?->balance ?? 0);
        // Available operational cash = asset pool − agent/aggregator floats −
        // earmarked (pending) — i.e. the unencumbered equity reserve.
        $netOperational = bcsub(bcsub(bcsub($platformCash, $agentFloats, 2), $aggregatorFloatAmount, 2), $pendingAmount, 2);

        return [
            'currency' => $currency,
            'agent_wallets' => number_format((float) $agentFloats, 2, '.', ''),
            'aggregator_wallet' => number_format((float) $aggregatorFloatAmount, 2, '.', ''),
            'platform_gross' => $platformCash,
            'pending' => $pendingAmount,
            'operational_cash' => $netOperational,
            'settlement_exposure' => number_format((float) $this->settlementExposure($currency), 2, '.', ''),
        ];
    }

    /**
     * Cash-in / cash-out demand from posted operations — labelled estimate.
     */
    public function demand(array $agentIds, string $currency): array
    {
        $since = now()->subDays(7)->startOfDay();
        $cashIn = (float) AgencyOperation::query()
            ->whereIn('agent_id', $agentIds)->where('currency_code', $currency)
            ->where('operation_type', AgencyOperation::TYPE_CASH_IN)
            ->where('status', 'posted')->where('created_at', '>=', $since)->sum('amount');
        $cashOut = (float) AgencyOperation::query()
            ->whereIn('agent_id', $agentIds)->where('currency_code', $currency)
            ->where('operation_type', AgencyOperation::TYPE_CASH_OUT)
            ->where('status', 'posted')->where('created_at', '>=', $since)->sum('amount');

        return [
            'currency' => $currency,
            'cash_in_7d' => number_format($cashIn, 2, '.', ''),
            'cash_out_7d' => number_format($cashOut, 2, '.', ''),
            'net_7d' => number_format($cashIn - $cashOut, 2, '.', ''),
            'estimate' => true,
            'basis' => '7-day posted operation history',
        ];
    }

    /**
     * Forecast — 6h / 24h / 7d expected net cash-out, ALWAYS labelled
     * estimate with its basis. Extrapolates the 7-day posted cash-out pace.
     */
    public function forecast(Aggregator $aggregator, string $currency, ?string $horizon = null): array
    {
        $agentIds = $this->tenant->agentIds($aggregator);
        $since = now()->subDays(7)->startOfDay();

        $cashOut = (float) AgencyOperation::query()
            ->whereIn('agent_id', $agentIds)->where('currency_code', $currency)
            ->where('operation_type', AgencyOperation::TYPE_CASH_OUT)
            ->where('status', 'posted')->where('created_at', '>=', $since)->sum('amount');

        $daily = $cashOut / 7;
        $points = [
            '6h' => ['amount' => number_format($daily / 24 * 6, 2, '.', ''), 'label' => 'Next 6 hours'],
            '24h' => ['amount' => number_format($daily, 2, '.', ''), 'label' => 'Next 24 hours'],
            '7d' => ['amount' => number_format($daily * 7, 2, '.', ''), 'label' => 'Next 7 days'],
        ];

        $result = [
            'currency' => $currency,
            'estimate' => true,
            'basis' => '7-day posted cash-out history, extrapolated',
        ];
        foreach ($points as $key => $point) {
            $result[$key] = $point['amount'];
            $result[$key.'_label'] = $point['label'];
        }

        if ($horizon !== null && isset($points[$horizon])) {
            $result['horizon'] = $horizon;
            $result['amount'] = $points[$horizon]['amount'];
            $result['label'] = $points[$horizon]['label'];
        }

        return $result;
    }

    /**
     * Per-agent liquidity status: Healthy / Watch / Low / Critical, using
     * the same buffer buckets as the command-center home (§10). Demand is a
     * labelled estimate; suspended agents are shown with a status override.
     */
    public function agentPositions(Aggregator $aggregator): array
    {
        $rows = [];
        foreach ($this->tenant->agents($aggregator)->with('user')->get() as $agent) {
            $currency = $agent->country_iso2 === 'NE' ? 'XOF' : 'NGN';
            $float = $this->agentFloat($agent, $currency);
            $floatAmount = $float !== null ? (string) $float->balance : '0';
            $demand7d = $this->agentDemand7d($agent->id, $currency);
            $dailyDemand = bcdiv($demand7d, '7', 2);
            $ratio = bccomp($dailyDemand, '0', 2) > 0 ? bcdiv($floatAmount, $dailyDemand, 2) : null;

            if ($agent->status !== Agent::STATUS_ACTIVE) {
                $bucket = 'suspended';
                $statusLabel = ucfirst($agent->status);
            } elseif ($ratio === null) {
                $bucket = 'no_demand';
                $statusLabel = 'No cash-out history';
            } else {
                $bucket = $this->bucket($ratio);
                $statusLabel = ucfirst($bucket);
            }

            $rows[] = [
                'agent_id' => $agent->id,
                'agent_code' => $agent->agent_code,
                'name' => $agent->user?->name,
                'status' => $agent->status,
                'currency' => $currency,
                'float' => $floatAmount,
                'demand_7d' => $demand7d,
                'buffer_ratio' => $ratio,
                'bucket' => $bucket,
                'status_label' => $statusLabel,
                'estimate' => true,
                'cash_out_risk' => $this->cashOutRisk($floatAmount, $dailyDemand, $bucket),
            ];
        }

        return $rows;
    }

    protected function cashOutRisk(string $float, string $dailyDemand, string $bucket): array
    {
        if (bccomp($dailyDemand, '0', 2) <= 0) {
            return ['level' => 'none', 'label' => 'No cash-out demand on record', 'estimate' => true];
        }
        $daysCovered = bcdiv($float, $dailyDemand, 2);

        return [
            'level' => match (true) {
                bccomp($daysCovered, '2', 2) >= 0 => 'low',
                bccomp($daysCovered, '1', 2) >= 0 => 'medium',
                default => 'high',
            },
            'label' => 'Float covers ~'.$daysCovered.' days of average cash-out demand',
            'estimate' => true,
        ];
    }

    protected function agentDemand7d(int $agentId, string $currency): string
    {
        $total = AgencyOperation::query()
            ->where('agent_id', $agentId)->where('currency_code', $currency)
            ->where('operation_type', AgencyOperation::TYPE_CASH_OUT)
            ->where('status', 'posted')
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->sum('amount');

        return number_format((float) $total, 2, '.', '');
    }

    protected function bucket(string $ratio): string
    {
        if (bccomp($ratio, '2', 2) >= 0) {
            return 'healthy';
        }
        if (bccomp($ratio, '1', 2) >= 0) {
            return 'watch';
        }
        if (bccomp($ratio, '0.5', 2) >= 0) {
            return 'low';
        }

        return 'critical';
    }

    /**
     * Currency-level alerts: agents at low/critical coverage and thin
     * operational cash vs projected 7-day demand (labelled estimate).
     */
    public function currencyAlerts(Aggregator $aggregator, string $currency): array
    {
        $alerts = [];

        $low = collect($this->agentPositions($aggregator))
            ->where('currency', $currency)
            ->filter(fn ($r) => in_array($r['bucket'], ['low', 'critical'], true));

        foreach ($low as $row) {
            $alerts[] = [
                'type' => 'agent_'.$row['bucket'],
                'severity' => $row['bucket'] === 'critical' ? 'critical' : 'high',
                'message' => $row['name'].' ('.$row['agent_code'].') has '.ucfirst($row['bucket']).' float coverage — buffer '.($row['buffer_ratio'] ?? 'n/a'),
                'estimate' => true,
                'agent_id' => $row['agent_id'],
            ];
        }

        $position = $this->position($aggregator, $currency);
        $forecast7d = $this->forecast($aggregator, $currency)['7d'];
        if (bccomp($position['operational_cash'], bcdiv($forecast7d, '2', 2), 2) < 0) {
            $alerts[] = [
                'type' => 'operational_cash_thin',
                'severity' => 'high',
                'message' => 'Operational cash ('.$position['operational_cash'].' '.$currency.') covers less than half of projected 7-day cash-out demand ('.$forecast7d.' '.$currency.')',
                'estimate' => true,
            ];
        }

        return $alerts;
    }

    /** Unsettled platform settlement exposure in a currency (platform-level). */
    protected function settlementExposure(string $currency): string
    {
        return (string) Settlement::query()
            ->where('currency_code', $currency)
            ->whereIn('status', [Settlement::STATUS_SCHEDULED, Settlement::STATUS_PENDING, Settlement::STATUS_PROCESSING])
            ->sum('amount');
    }

    // ── Requests (§25) ──────────────────────────────────────────────────

    public function requestsSummary(Aggregator $aggregator): array
    {
        $rows = LiquidityRequest::query()
            ->where('aggregator_id', $aggregator->id)
            ->get();

        return [
            'open' => $rows->filter(fn (LiquidityRequest $r) => $r->isOpen())->count(),
            'pending' => $rows->where('status', LiquidityRequest::STATUS_PENDING)->count(),
            'in_review' => $rows->where('status', LiquidityRequest::STATUS_IN_REVIEW)->count(),
            'approved' => $rows->where('status', LiquidityRequest::STATUS_APPROVED)->count(),
            'funded' => $rows->where('status', LiquidityRequest::STATUS_FUNDED)->count(),
            'rejected' => $rows->where('status', LiquidityRequest::STATUS_REJECTED)->count(),
            'cancelled' => $rows->where('status', LiquidityRequest::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * @param  array{status?: string}  $filters  status: open|all|pending|approved|funded|rejected|cancelled
     */
    public function requests(Aggregator $aggregator, array $filters = [], int $perPage = 10, int $page = 1): array
    {
        $query = LiquidityRequest::query()
            ->where('aggregator_id', $aggregator->id)
            ->with(['agent.user'])
            ->latest('created_at');

        $status = (string) ($filters['status'] ?? 'open');
        if ($status === 'open') {
            $query->whereIn('status', [LiquidityRequest::STATUS_PENDING, LiquidityRequest::STATUS_IN_REVIEW, LiquidityRequest::STATUS_APPROVED]);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $total = (clone $query)->count();
        $rows = $query->forPage($page, $perPage)->get();

        return [
            'rows' => $rows,
            'total' => $total,
            'filter' => $status,
            'page' => $page,
            'per_page' => $perPage,
            'paginator' => new LengthAwarePaginator($rows, $total, $perPage, $page, [
                'path' => request()->path(), 'query' => request()->query(),
            ]),
        ];
    }

    // ── Workflow (agent → review → risk/limit → approval → funding, §26) ─

    /**
     * Submit a liquidity request (agent-side entry point; the aggregator
     * console can raise one on an agent's behalf for the demo).
     *
     * @param  array{amount: string, currency_code?: ?string, reason?: string, requested_by_type?: string}  $payload
     */
    public function submit(Aggregator $aggregator, Agent $agent, array $payload, ?int $actorId = null, ?string $reference = null): LiquidityRequest
    {
        if (! $this->tenant->ownsAgent($aggregator, $agent)) {
            abort(404, 'Agent not found in this network.');
        }

        $amount = (string) $payload['amount'];
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $amount) || (float) $amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be a positive decimal.']);
        }

        $currency = strtoupper((string) ($payload['currency_code'] ?? '')) ?: ($agent->country_iso2 === 'NE' ? 'XOF' : 'NGN');
        if ($currency !== ($agent->country_iso2 === 'NE' ? 'XOF' : 'NGN')) {
            throw ValidationException::withMessages(['currency_code' => 'Currency must match the agent\'s country currency.']);
        }

        $request = LiquidityRequest::create([
            'reference' => $reference ?? 'LRQ-'.strtoupper(Str::random(8)),
            'aggregator_id' => $aggregator->id,
            'agent_id' => $agent->id,
            'currency_code' => $currency,
            'amount' => $amount,
            'reason' => $payload['reason'] ?? LiquidityRequest::REASON_CASH_OUT_DEMAND,
            'status' => LiquidityRequest::STATUS_PENDING,
            'risk_level' => LiquidityRequest::RISK_LOW,
            'requested_by_type' => $payload['requested_by_type'] ?? 'agent',
            'requested_by' => $actorId,
        ]);

        AuditLog::record('liquidity.requested', $actorId, $agent->user_id, [
            'description' => "Liquidity request {$request->reference} for {$amount} {$currency} ({$agent->agent_code}).",
            'event_type' => 'operations',
            'metadata' => ['liquidity_request_id' => $request->id, 'reference' => $request->reference, 'amount' => $amount, 'currency' => $currency],
        ]);

        return $request;
    }

    /**
     * Review a request — risk/limit checks first, then approve (earmark on
     * the ledger) or reject. Audited; approve is idempotent per request.
     *
     * @throws InsufficientFundsException propagated only for truly invalid states
     */
    public function review(LiquidityRequest $request, bool $approve, ?string $note = null, ?int $actorId = null): LiquidityRequest
    {
        $this->guardOpen($request);

        $agent = $request->agent;
        $risk = $this->assessRisk($request, $agent);

        if (! $approve) {
            return $this->reject($request, $note ?: 'Rejected by the aggregator.', $risk, $actorId);
        }

        if ($risk['level'] === LiquidityRequest::RISK_HIGH) {
            return $this->reject($request, $note ?: $risk['blocker'] ?? 'High-risk request blocked by policy.', $risk, $actorId);
        }

        // Earmark operational cash — the ledger guard rejects overdraw.
        $platform = $this->platformCash($request->currency_code);
        $pending = $this->pendingAccount($request->currency_code);

        try {
            $tx = $this->ledger->post(
                [
                    ['account_id' => $platform->id, 'side' => 'debit', 'amount' => (string) $request->amount],
                    ['account_id' => $pending->id, 'side' => 'credit', 'amount' => (string) $request->amount],
                ],
                type: 'liquidity_earmark',
                reference: 'LEDGER-'.$request->reference.'-EARMARK',
                description: "Liquidity earmark for {$request->reference} ({$request->amount} {$request->currency_code})",
                idempotencyKey: 'LRQ-EARMARK-'.$request->reference,
                createdBy: $actorId,
            );
        } catch (InsufficientFundsException $e) {
            return $this->reject($request, 'Insufficient operational cash to approve — '.$e->getMessage(), $risk, $actorId);
        }

        $request->forceFill([
            'status' => LiquidityRequest::STATUS_APPROVED,
            'risk_level' => $risk['level'],
            'risk_notes' => $risk['notes'],
            'reviewed_by' => $actorId,
            'reviewed_at' => now(),
            'review_note' => $note ?: 'Approved by the aggregator.',
            'ledger_transaction_id' => $tx->id,
        ])->save();

        AuditLog::record('liquidity.approved', $actorId, $agent->user_id, [
            'description' => "Liquidity request {$request->reference} approved (earmark {$request->amount} {$request->currency_code}).",
            'event_type' => 'financial',
            'metadata' => ['liquidity_request_id' => $request->id, 'ledger_transaction_id' => $tx->id],
        ]);

        return $request;
    }

    /**
     * Fund an approved request: release the earmark to the agent float.
     * Idempotent — replay returns the original funding posting.
     */
    public function fund(LiquidityRequest $request, ?int $actorId = null): LiquidityRequest
    {
        if ($request->status === LiquidityRequest::STATUS_FUNDED) {
            return $request;
        }

        if ($request->status !== LiquidityRequest::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'status' => 'Only approved requests can be funded (current: '.$request->status.').',
            ]);
        }

        $pending = $this->pendingAccount($request->currency_code);
        $float = $this->agentFloat($request->agent, $request->currency_code);
        if ($float === null) {
            throw ValidationException::withMessages([
                'status' => 'Agent float account not provisioned for '.$request->currency_code.'.',
            ]);
        }

        $tx = $this->ledger->post(
            [
                ['account_id' => $pending->id, 'side' => 'debit', 'amount' => (string) $request->amount],
                ['account_id' => $float->id, 'side' => 'credit', 'amount' => (string) $request->amount],
            ],
            type: 'liquidity_funding',
            reference: 'LEDGER-'.$request->reference.'-FUND',
            description: "Liquidity funding for {$request->reference} ({$request->amount} {$request->currency_code}) to agent float",
            idempotencyKey: 'LRQ-FUND-'.$request->reference,
            createdBy: $actorId,
        );

        $request->forceFill([
            'status' => LiquidityRequest::STATUS_FUNDED,
            'funded_at' => now(),
            'ledger_transaction_id' => $tx->id,
        ])->save();

        AuditLog::record('liquidity.funded', $actorId, $request->agent->user_id, [
            'description' => "Liquidity request {$request->reference} funded ({$request->amount} {$request->currency_code}).",
            'event_type' => 'financial',
            'metadata' => ['liquidity_request_id' => $request->id, 'ledger_transaction_id' => $tx->id],
        ]);

        return $request;
    }

    /** Cancel a pending/approved request; releases the earmark if any. */
    public function cancel(LiquidityRequest $request, ?int $actorId = null, ?string $note = null): LiquidityRequest
    {
        if (! in_array($request->status, [LiquidityRequest::STATUS_PENDING, LiquidityRequest::STATUS_IN_REVIEW, LiquidityRequest::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only open requests can be cancelled (current: '.$request->status.').',
            ]);
        }

        $wasApproved = $request->status === LiquidityRequest::STATUS_APPROVED;
        if ($wasApproved) {
            $platform = $this->platformCash($request->currency_code);
            $pending = $this->pendingAccount($request->currency_code);
            $this->ledger->post(
                [
                    ['account_id' => $pending->id, 'side' => 'debit', 'amount' => (string) $request->amount],
                    ['account_id' => $platform->id, 'side' => 'credit', 'amount' => (string) $request->amount],
                ],
                type: 'liquidity_release',
                reference: 'LEDGER-'.$request->reference.'-RELEASE',
                description: "Liquidity release for cancelled request {$request->reference}",
                idempotencyKey: 'LRQ-RELEASE-'.$request->reference,
                createdBy: $actorId,
            );
        }

        $request->forceFill([
            'status' => LiquidityRequest::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'review_note' => $note ?: 'Cancelled by the aggregator.',
        ])->save();

        AuditLog::record('liquidity.cancelled', $actorId, $request->agent->user_id, [
            'description' => "Liquidity request {$request->reference} cancelled.",
            'event_type' => 'operations',
            'metadata' => ['liquidity_request_id' => $request->id],
        ]);

        return $request;
    }

    protected function reject(LiquidityRequest $request, string $note, array $risk, ?int $actorId): LiquidityRequest
    {
        $request->forceFill([
            'status' => LiquidityRequest::STATUS_REJECTED,
            'risk_level' => $risk['level'],
            'risk_notes' => $risk['notes'],
            'reviewed_by' => $actorId,
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        AuditLog::record('liquidity.rejected', $actorId, $request->agent->user_id, [
            'description' => "Liquidity request {$request->reference} rejected — {$note}",
            'event_type' => 'operations',
            'metadata' => ['liquidity_request_id' => $request->id],
        ]);

        return $request;
    }

    protected function guardOpen(LiquidityRequest $request): void
    {
        if (! $request->isOpen() || $request->status === LiquidityRequest::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'status' => 'Request is not open for review (current: '.$request->status.').',
            ]);
        }
    }

    /**
     * Risk/limit assessment at review time — every flag is derived from
     * REAL records; demand-based flags are labelled estimates.
     */
    protected function assessRisk(LiquidityRequest $request, Agent $agent): array
    {
        $notes = [];
        $level = LiquidityRequest::RISK_LOW;
        $blocker = null;

        if ($agent->status !== Agent::STATUS_ACTIVE) {
            $notes[] = 'Agent is not active ('.$agent->status.') — request blocked.';
            $level = LiquidityRequest::RISK_HIGH;
            $blocker = 'Agent is not active ('.$agent->status.').';
        }

        $dailyDemand = bcdiv($this->agentDemand7d($agent->id, $request->currency_code), '7', 2);
        if (bccomp($dailyDemand, '0', 2) > 0) {
            $multiple = bcdiv((string) $request->amount, $dailyDemand, 2);
            if (bccomp($multiple, '6', 2) >= 0) {
                $notes[] = "Amount is {$multiple}x the agent's average daily cash-out demand (estimate) — spike flagged.";
                if ($level === LiquidityRequest::RISK_LOW) {
                    $level = LiquidityRequest::RISK_HIGH;
                    $blocker = 'Request amount exceeds 6x the agent\'s average daily cash-out demand (labelled estimate).';
                }
            } elseif (bccomp($multiple, '3', 2) >= 0) {
                $notes[] = "Amount is {$multiple}x the agent's average daily cash-out demand (estimate).";
                $level = $level === LiquidityRequest::RISK_LOW ? LiquidityRequest::RISK_MEDIUM : $level;
            }
        } else {
            $notes[] = 'No cash-out history on record — demand basis unavailable (honest: cannot estimate spike risk).';
        }

        return ['level' => $level, 'notes' => $notes, 'blocker' => $blocker];
    }

    // ── Shared ──────────────────────────────────────────────────────────

    protected function networkCurrencies(Aggregator $aggregator): array
    {
        $agents = $this->tenant->agents($aggregator)->get();
        $currencies = $agents->pluck('country_iso2')
            ->map(fn ($c) => $c === 'NE' ? 'XOF' : 'NGN')
            ->unique()->sort()->values()->all();

        return $currencies ?: ['XOF'];
    }

    protected function primaryCurrency(Aggregator $aggregator): string
    {
        return $aggregator->country_iso2 === 'NE' ? 'XOF' : 'NGN';
    }
}
