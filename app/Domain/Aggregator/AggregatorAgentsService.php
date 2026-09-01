<?php

namespace App\Domain\Aggregator;

use App\Domain\Agency\AgencyService;
use App\Domain\Accounting\LedgerAccount;
use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\AuditLog;
use App\Models\CommissionEntry;
use App\Models\KycSubmission;
use App\Models\RiskAlert;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * AGGREGATOR CONSOLE — Stage B (agents directory + profile, §14–22).
 *
 * Everything here is computed from REAL records scoped to the aggregator's
 * tenant (§3, §8) through AggregatorTenantService — never fabricated:
 *
 *   - directory rows carry live 30-day operation stats and ledger-sourced
 *     float balances;
 *   - the performance score (§17) is an EXPLAINABLE weighted composite with
 *     per-component points, weights and plain-language reasons, and returns
 *     null ("insufficient data") when there is nothing real to score;
 *   - dormancy (§19) is inactivity measured from posted operations only,
 *     and its volume projection is always labelled an estimate;
 *   - recruitment (§20) captures an agent with NO activation — pending until
 *     KYC — and goes through AgencyService so it is audited end-to-end;
 *   - the onboarding pipeline (§21–22) is real counts with a conversion
 *     rate that is honestly null on an empty network.
 */
class AggregatorAgentsService
{
    /** Performance-component weights — sum = 100. */
    private const COMPONENTS = [
        'activity' => ['label' => 'Activity · ops in 30 days', 'weight' => 35],
        'volume'   => ['label' => 'Posted volume · 30 days',  'weight' => 25],
        'success'  => ['label' => 'Success rate · 30 days',   'weight' => 20],
        'risk'     => ['label' => 'Risk posture',             'weight' => 10],
        'kyc'      => ['label' => 'KYC completeness',         'weight' => 10],
    ];

    /** Inactivity thresholds for dormancy intelligence (§19). */
    private const DORMANT_DAYS = 30;
    private const AT_RISK_DAYS = 14;

    public function __construct(
        private readonly AggregatorTenantService $tenant,
        private readonly AgencyService $agency,
    ) {
    }

    // ── Directory (§14) ─────────────────────────────────────────────────

    /**
     * Server-paginated agent directory with live stats per agent.
     *
     * @param  array{search?: string, status?: string, kyc?: string, region?: string, city?: string, sort?: string}  $filters
     */
    public function directory(Aggregator $aggregator, array $filters = [], int $perPage = 10, int $page = 1): array
    {
        $currency = $this->primaryCurrency($aggregator);

        $query = $this->tenant->agents($aggregator)
            ->with('user')
            ->where('agents.aggregator_id', $aggregator->id);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('agents.agent_code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%"));
            });
        }

        foreach (['status', 'kyc_status', 'region', 'city', 'tier'] as $field) {
            $value = trim((string) ($filters[$field] ?? ''));
            if ($value !== '') {
                $query->where('agents.'.$field, $value);
            }
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        $query->orderBy(
            match ($sort) {
                'name' => 'agents.agent_code',
                default => 'agents.created_at',
            },
            $sort === 'name' ? 'asc' : 'desc'
        );

        $total = (clone $query)->count();
        $agents = $query->forPage($page, $perPage)->get();

        // Live 30-day stats for the page's agents in ONE query.
        $since = now()->subDays(30);
        $stats = AgencyOperation::query()
            ->whereIn('agent_id', $agents->pluck('id'))
            ->where('created_at', '>=', $since)
            ->selectRaw(
                'agent_id,
                 count(*) as ops,
                 sum(case when status = ? then 1 else 0 end) as posted,
                 sum(case when status = ? then amount else 0 end) as volume,
                 max(case when status = ? then created_at else null end) as last_activity',
                ['posted', 'posted', 'posted']
            )
            ->groupBy('agent_id')
            ->get()
            ->keyBy('agent_id');

        $floats = LedgerAccount::query()
            ->where('owner_type', 'agent')
            ->whereIn('owner_id', $agents->pluck('id'))
            ->where('currency_code', $currency)
            ->where('is_active', true)
            ->get()
            ->keyBy('owner_id');

        $rows = $agents->map(function (Agent $agent) use ($stats, $floats, $currency) {
            $s = $stats->get($agent->id);
            $last = $s !== null && $s->last_activity !== null
                ? Carbon::parse($s->last_activity)
                : null;

            return [
                'agent' => $agent,
                'name' => $agent->user?->name ?? 'Unnamed agent',
                'email' => $agent->user?->email,
                'phone' => $agent->user?->phone_number,
                'status' => $agent->status,
                'tier' => $agent->tier,
                'kyc_status' => $agent->kyc_status,
                'location' => trim(implode(', ', array_filter([$agent->city, $agent->region]))),
                'ops_30d' => (int) ($s?->ops ?? 0),
                'posted_30d' => (int) ($s?->posted ?? 0),
                'failed_30d' => max(0, (int) ($s?->ops ?? 0) - (int) ($s?->posted ?? 0)),
                'volume_30d' => $s !== null ? number_format((float) ($s->volume ?? 0), 2, '.', '') : '0.00',
                'float' => ($f = $floats->get($agent->id)) !== null ? $f->balance : null,
                'float_currency' => $currency,
                'last_activity' => $last,
                'risk_score' => $agent->risk_score,
            ];
        });

        $paginator = new LengthAwarePaginator(
            $rows->values(),
            $total,
            $perPage,
            $page,
            ['path' => request()->path(), 'query' => request()->query()]
        );

        return [
            'paginator' => $paginator,
            'total' => $total,
            'currency' => $currency,
            'options' => $this->filterOptions($aggregator),
            'pipeline' => $this->pipeline($aggregator),
            'filters' => $filters,
        ];
    }

    /**
     * Distinct region/city/status/KYC values for filter dropdowns — derived
     * from the tenant's actual agents (honest options, never invented).
     */
    public function filterOptions(Aggregator $aggregator): array
    {
        $agents = $this->tenant->agents($aggregator)->get();

        return [
            'statuses' => $agents->pluck('status')->unique()->sort()->values()->all(),
            'kyc' => $agents->pluck('kyc_status')->unique()->sort()->values()->all(),
            'regions' => $agents->pluck('region')->filter()->unique()->sort()->values()->all(),
            'cities' => $agents->pluck('city')->filter()->unique()->sort()->values()->all(),
        ];
    }

    // ── Onboarding pipeline (§21–22) ────────────────────────────────────

    public function pipeline(Aggregator $aggregator): array
    {
        $agents = $this->tenant->agents($aggregator)->get();
        $total = $agents->count();
        $active = $agents->where('status', Agent::STATUS_ACTIVE)->count();
        $kycPending = $agents->where('kyc_status', 'pending')->count();

        return [
            'total' => $total,
            'active' => $active,
            'conversion_rate' => $total > 0 ? round($active / $total * 100, 1) : null,
            'stages' => [
                ['key' => 'recruited', 'label' => 'Recruited (pending)', 'count' => $agents->where('status', Agent::STATUS_PENDING)->count(), 'tone' => 'info'],
                ['key' => 'kyc_pending', 'label' => 'KYC pending', 'count' => $kycPending, 'tone' => 'warn'],
                ['key' => 'active', 'label' => 'Active', 'count' => $active, 'tone' => 'ok'],
                ['key' => 'inactive', 'label' => 'Inactive', 'count' => $agents->where('status', Agent::STATUS_INACTIVE)->count(), 'tone' => 'neutral'],
                ['key' => 'suspended', 'label' => 'Suspended', 'count' => $agents->where('status', Agent::STATUS_SUSPENDED)->count(), 'tone' => 'alert'],
                ['key' => 'terminated', 'label' => 'Terminated', 'count' => $agents->where('status', Agent::STATUS_TERMINATED)->count(), 'tone' => 'crit'],
            ],
        ];
    }

    // ── Agent profile (§15–22) ──────────────────────────────────────────

    /**
     * Full profile payload for one tab. Ownership is enforced here — an
     * aggregator asking for another tenant's agent gets a 404 (no leak).
     */
    public function agentProfile(Aggregator $aggregator, Agent $agent, string $tab): array
    {
        if (! $this->tenant->ownsAgent($aggregator, $agent)) {
            abort(404, 'Agent not found in this network.');
        }

        return match ($tab) {
            'overview' => $this->overview($agent),
            'kyc' => $this->kyc($agent),
            'transactions' => $this->transactions($agent),
            'liquidity' => $this->liquidity($agent),
            'commissions' => $this->commissions($agent),
            'performance' => $this->performanceTab($agent),
            'risk' => $this->risk($agent),
            'devices' => $this->devices($agent),
            'support' => $this->support($agent),
            'audit' => $this->audit($agent),
            default => $this->overview($agent),
        };
    }

    protected function overview(Agent $agent): array
    {
        $currency = $this->primaryCurrency($agent->aggregator);

        $since = now()->subDays(30);
        $ops30 = AgencyOperation::query()
            ->where('agent_id', $agent->id)
            ->where('created_at', '>=', $since)
            ->selectRaw(
                'count(*) as ops,
                 sum(case when status = ? then 1 else 0 end) as posted,
                 sum(case when status = ? then amount else 0 end) as volume',
                ['posted', 'posted']
            )
            ->first();

        $commissions = (string) CommissionEntry::query()
            ->where('beneficiary_type', 'agent')
            ->where('beneficiary_id', $agent->id)
            ->where('currency_code', $currency)
            ->sum('amount');

        $openAlerts = RiskAlert::query()
            ->where('entity_type', 'agent')
            ->where('entity_id', $agent->id)
            ->whereIn('status', [RiskAlert::STATUS_OPEN, RiskAlert::STATUS_ACKNOWLEDGED, RiskAlert::STATUS_INVESTIGATING])
            ->count();

        return [
            'agent' => $agent,
            'user' => $agent->user,
            'currency' => $currency,
            'floats' => $this->floatAccounts($agent),
            'counts' => [
                'ops_total' => AgencyOperation::where('agent_id', $agent->id)->count(),
                'ops_30d' => (int) ($ops30?->ops ?? 0),
                'posted_30d' => (int) ($ops30?->posted ?? 0),
                'failed_30d' => max(0, (int) ($ops30?->ops ?? 0) - (int) ($ops30?->posted ?? 0)),
                'volume_30d' => number_format((float) ($ops30?->volume ?? 0), 2, '.', ''),
                'commissions_30d' => $commissions,
                'open_alerts' => $openAlerts,
            ],
            'recent_ops' => $agent->operations()->latest('created_at')->limit(5)->get(),
            'last_activity' => AgencyOperation::where('agent_id', $agent->id)->where('status', 'posted')->max('created_at'),
            'dormancy' => $this->dormancy($agent),
        ];
    }

    protected function kyc(Agent $agent): array
    {
        $submission = KycSubmission::query()
            ->where('user_id', $agent->user_id)
            ->latest('submitted_at')
            ->first();

        $status = strtolower((string) $agent->kyc_status);
        if ($submission !== null && $submission->status === KycSubmission::STATUS_APPROVED) {
            $status = 'verified';
        } elseif ($submission !== null && $submission->status === KycSubmission::STATUS_PENDING) {
            $status = 'pending';
        }

        return [
            'status' => $status,
            'label' => match ($status) {
                'verified' => 'Verified',
                'pending' => 'Review in progress',
                default => 'Unverified',
            },
            'tier' => (int) ($agent->user?->kyc_tier ?? 0),
            'submission' => $submission !== null ? [
                'type' => $submission->type,
                'status' => $submission->status,
                'submitted_at' => $submission->submitted_at,
                'reference' => $submission->reference ?? null,
            ] : null,
            'note' => $status === 'verified'
                ? 'An approved identity submission is on file. This agent may be activated.'
                : 'No approved identity verification on file — activation is withheld until KYC is approved.',
        ];
    }

    protected function transactions(Agent $agent, int $perPage = 10, int $page = 1): array
    {
        $query = $agent->operations()->with('customer')->latest('created_at');
        $total = (clone $query)->count();
        $rows = $query->forPage($page, $perPage)->get();

        return [
            'rows' => $rows,
            'total' => $total,
            'paginator' => new LengthAwarePaginator($rows, $total, $perPage, $page, [
                'path' => request()->path(), 'query' => request()->query(),
            ]),
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    protected function liquidity(Agent $agent): array
    {
        return [
            'accounts' => $this->floatAccounts($agent),
            'currency' => $this->primaryCurrency($agent->aggregator),
            'note' => 'Balances are read from the ledger (authoritative) — no balance columns on the agent record.',
        ];
    }

    protected function commissions(Agent $agent, int $perPage = 10, int $page = 1): array
    {
        $query = CommissionEntry::query()
            ->where('beneficiary_type', 'agent')
            ->where('beneficiary_id', $agent->id)
            ->latest('id');

        $total = (clone $query)->count();
        $rows = $query->forPage($page, $perPage)->get();

        $byCurrency = CommissionEntry::query()
            ->where('beneficiary_type', 'agent')
            ->where('beneficiary_id', $agent->id)
            ->get()
            ->groupBy('currency_code')
            ->map(fn ($g) => number_format((float) $g->sum('amount'), 2, '.', ''))
            ->all();

        return [
            'rows' => $rows,
            'total' => $total,
            'totals' => $byCurrency,
            'paginator' => new LengthAwarePaginator($rows, $total, $perPage, $page, [
                'path' => request()->path(), 'query' => request()->query(),
            ]),
        ];
    }

    protected function performanceTab(Agent $agent): array
    {
        return [
            'score' => $this->performance($agent),
            'productivity' => $this->productivity($agent),
            'dormancy' => $this->dormancy($agent),
            'window' => 'Last 30 days',
        ];
    }

    protected function risk(Agent $agent, int $perPage = 10, int $page = 1): array
    {
        $query = RiskAlert::query()
            ->where('entity_type', 'agent')
            ->where('entity_id', $agent->id)
            ->latest('id');

        $total = (clone $query)->count();
        $rows = $query->forPage($page, $perPage)->get();

        return [
            'rows' => $rows,
            'total' => $total,
            'open' => $rows->whereIn('status', [RiskAlert::STATUS_OPEN, RiskAlert::STATUS_ACKNOWLEDGED, RiskAlert::STATUS_INVESTIGATING])->count(),
            'risk_score' => $agent->risk_score,
            'paginator' => new LengthAwarePaginator($rows, $total, $perPage, $page, [
                'path' => request()->path(), 'query' => request()->query(),
            ]),
        ];
    }

    /**
     * Devices — honest state. Agency agents do not have device telemetry in
     * this build; the module explains that instead of inventing rows.
     */
    protected function devices(Agent $agent): array
    {
        return [
            'note' => 'Device telemetry is not recorded for agency agents in this build. This module will populate when agent-side device attestation ships.',
            'devices' => [],
        ];
    }

    protected function support(Agent $agent): array
    {
        $tickets = SupportTicket::query()
            ->where('user_id', $agent->user_id)
            ->latest('id')
            ->limit(20)
            ->get();

        return ['tickets' => $tickets];
    }

    protected function audit(Agent $agent, int $perPage = 10, int $page = 1): array
    {
        $query = AuditLog::query()
            ->where(fn ($q) => $q->where('user_id', $agent->user_id)->orWhere('target_id', $agent->id))
            ->latest('id');

        $total = (clone $query)->count();
        $rows = $query->forPage($page, $perPage)->get();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'paginator' => new LengthAwarePaginator($rows, $total, $perPage, $page, [
                'path' => request()->path(), 'query' => request()->query(),
            ]),
        ];
    }

    // ── Performance score (§17) — explainable, honest ───────────────────

    /**
     * Explainable performance score, or null when there is no real signal
     * (no operations, no risk posture, no KYC state) — the UI then renders
     * an "insufficient data" state instead of a fabricated number.
     *
     * @return array{score: int, label: string, basis: string, window: string, components: list<array{key: string, label: string, points: int, max: int, weight: int, explanation: string}>}|null
     */
    public function performance(Agent $agent): ?array
    {
        $currency = $this->primaryCurrency($agent->aggregator);
        $since = now()->subDays(30);

        $ops = AgencyOperation::query()
            ->where('agent_id', $agent->id)
            ->where('created_at', '>=', $since)
            ->get(['status', 'amount']);

        $posted = $ops->where('status', 'posted');
        $total = $ops->count();
        $postedCount = $posted->count();
        $volume = (float) $posted->sum('amount');

        $riskScore = $agent->risk_score !== null ? (float) $agent->risk_score : null;

        // Insufficient data: no operational signal AND no risk posture to
        // weigh — the UI renders "insufficient data" rather than a score
        // built from nothing (KYC alone is not a performance signal).
        if ($total === 0 && $riskScore === null) {
            return null;
        }

        $components = [];
        $availableWeight = 0;

        $components['activity'] = [
            'points' => match (true) {
                $total >= 40 => 100,
                $total >= 15 => 75,
                $total >= 5 => 50,
                $total >= 1 => 25,
                default => 0,
            },
            'explanation' => $total === 0
                ? 'No operations in the window.'
                : "{$total} operations in the last 30 days.",
        ];

        $components['volume'] = [
            'points' => match (true) {
                $volume >= 2500000 => 100,
                $volume >= 1000000 => 80,
                $volume >= 500000 => 60,
                $volume >= 250000 => 45,
                $volume > 0 => 30,
                default => 0,
            },
            'explanation' => $volume > 0
                ? number_format($volume).' '.$currency.' posted volume in the window.'
                : 'No posted volume in the window.',
        ];

        if ($total > 0) {
            $rate = (int) round($postedCount / $total * 100);
            $components['success'] = [
                'points' => $rate,
                'explanation' => "{$postedCount} of {$total} operations succeeded ({$rate}%).",
            ];
        }

        $components['risk'] = [
            'points' => $riskScore !== null ? (int) max(0, min(100, 100 - $riskScore)) : 50,
            'explanation' => $riskScore !== null
                ? "Risk score {$riskScore} on file (penalty applied)."
                : 'No risk score on file — neutral posture.',
        ];

        $components['kyc'] = [
            'points' => match (strtolower((string) $agent->kyc_status)) {
                'verified' => 100,
                'pending' => 50,
                default => 0,
            },
            'explanation' => 'KYC '.strtolower((string) $agent->kyc_status).' on the agent record.',
        ];

        $scored = [];
        foreach (self::COMPONENTS as $key => $meta) {
            $c = $components[$key] ?? null;
            if ($c === null) {
                continue;
            }
            $scored[] = [
                'key' => $key,
                'label' => $meta['label'],
                'points' => $c['points'],
                'max' => 100,
                'weight' => $meta['weight'],
                'explanation' => $c['explanation'],
            ];
            $availableWeight += $meta['weight'];
        }

        if ($availableWeight === 0) {
            return null;
        }

        $weighted = 0.0;
        foreach ($scored as $s) {
            $weighted += $s['points'] * ($s['weight'] / $availableWeight);
        }
        $score = (int) round($weighted);

        return [
            'score' => $score,
            'label' => match (true) {
                $score >= 75 => 'Strong',
                $score >= 50 => 'Good',
                $score >= 30 => 'Needs attention',
                default => 'At risk',
            },
            'basis' => $availableWeight === 100 ? 'authoritative' : 'partial',
            'window' => 'Last 30 days',
            'components' => $scored,
        ];
    }

    /**
     * Productivity (§18) — real, computable signals from posted operations.
     */
    public function productivity(Agent $agent): array
    {
        $since = now()->subDays(30);
        $ops = AgencyOperation::query()
            ->where('agent_id', $agent->id)
            ->where('status', 'posted')
            ->where('created_at', '>=', $since)
            ->get();

        $activeDays = $ops->pluck('created_at')->map(fn (Carbon $d) => $d->format('Y-m-d'))->unique()->count();
        $avg = $ops->count() > 0 ? bcdiv((string) $ops->sum('amount'), (string) $ops->count(), 2) : '0';

        return [
            'ops_30d' => $ops->count(),
            'active_days_30d' => $activeDays,
            'avg_value' => $avg,
            'distinct_customers_30d' => $ops->pluck('customer_user_id')->unique()->count(),
            'ops_per_active_day' => $activeDays > 0 ? round($ops->count() / $activeDays, 2) : null,
        ];
    }

    /**
     * Dormancy intelligence (§19) — inactivity measured from POSTED
     * operations only; suspended/terminated agents are excluded (their
     * status is the explanation). Volume projection is a labelled estimate.
     */
    public function dormancy(Agent $agent): ?array
    {
        if (in_array($agent->status, [Agent::STATUS_SUSPENDED, Agent::STATUS_TERMINATED], true)) {
            return null;
        }

        $last = AgencyOperation::query()
            ->where('agent_id', $agent->id)
            ->where('status', 'posted')
            ->max('created_at');

        if ($last === null) {
            return [
                'status' => 'no_activity',
                'label' => 'No activity ever recorded',
                'days_since_last_activity' => null,
                'last_activity_at' => null,
                'note' => 'This agent has no posted operations on record yet.',
                'estimate' => null,
            ];
        }

        $lastDate = Carbon::parse($last);
        $days = (int) floor($lastDate->diffInDays(now(), false));

        if ($days <= self::AT_RISK_DAYS) {
            return null;
        }

        // Labelled estimate: the agent's own lifetime weekly pace, projected
        // forward. Never presented as a promise.
        $lifetimeOps = AgencyOperation::query()
            ->where('agent_id', $agent->id)
            ->where('status', 'posted')
            ->get(['amount', 'created_at']);

        $totalAmount = (float) $lifetimeOps->sum('amount');
        $spanDays = max(1, (int) $lifetimeOps->min('created_at')->diffInDays(now(), true));
        $weeklyPace = round($totalAmount / $spanDays * 7, 0);

        return [
            'status' => $days > self::DORMANT_DAYS ? 'dormant' : 'at_risk',
            'label' => $days > self::DORMANT_DAYS ? 'Dormant' : 'At risk of dormancy',
            'days_since_last_activity' => $days,
            'last_activity_at' => $lastDate,
            'note' => 'No posted operation in the last '.$days.' days.',
            'estimate' => [
                'weekly_volume' => number_format($weeklyPace, 0, '.', ''),
                'currency' => $this->primaryCurrency($agent->aggregator),
                'label' => 'estimate',
                'explanation' => 'Projected weekly volume from this agent\'s own historical pace — an estimate, not a forecast.',
            ],
        ];
    }

    // ── Recruitment (§20) — capture only, no activation ─────────────────

    /**
     * Capture a new agent into this network. The agent is created PENDING
     * with unverified KYC — activation is withheld until KYC is approved by
     * the verification team. Audited via AgencyService (§82).
     *
     * @param  array{name: string, email: string, phone: string, country_iso2: string, region?: ?string, city?: ?string, tier?: string}  $payload
     *
     * @throws ValidationException on duplicate email/phone
     */
    public function recruit(Aggregator $aggregator, array $payload, ?int $actorId = null): Agent
    {
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $phone = trim((string) ($payload['phone'] ?? ''));

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email already exists.',
            ]);
        }
        if (User::where('phone_number', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'An account with this phone number already exists.',
            ]);
        }

        $country = strtoupper((string) ($payload['country_iso2'] ?? 'NE'));

        $user = User::create([
            'name' => trim((string) $payload['name']),
            'email' => $email,
            'password' => \Illuminate\Support\Str::password(24),
            'phone_number' => $phone,
            'country_code' => $country === 'NE' ? 'NER' : 'NGA',
            'is_active' => true,
            'kyc_status' => 'unverified',
        ]);

        // Role is never mass-assignable — explicit guarded assignment.
        $user->forceFill(['role' => 'agent'])->save();

        $agent = $this->agency->registerAgent($user, [
            'country_iso2' => $country,
            'region' => $payload['region'] ?? null,
            'city' => $payload['city'] ?? null,
            'tier' => $payload['tier'] ?? 'bronze',
            'kyc_status' => 'unverified',
            'risk_score' => null,
        ], $actorId);

        $this->agency->assignAgentToAggregator($agent, $aggregator, $actorId);

        return $agent;
    }

    // ── Shared helpers ──────────────────────────────────────────────────

    /** @return list<array{currency: string, balance: string, name: string, updated_at: ?string}> */
    protected function floatAccounts(Agent $agent): array
    {
        return LedgerAccount::query()
            ->where('owner_type', 'agent')
            ->where('owner_id', $agent->id)
            ->where('is_active', true)
            ->orderBy('currency_code')
            ->get()
            ->map(fn (LedgerAccount $a) => [
                'currency' => $a->currency_code,
                'balance' => (string) $a->balance,
                'name' => $a->name,
                'updated_at' => $a->updated_at?->toIso8601String(),
            ])
            ->all();
    }

    protected function primaryCurrency(Aggregator $aggregator): string
    {
        return $aggregator->country_iso2 === 'NE' ? 'XOF' : 'NGN';
    }
}
