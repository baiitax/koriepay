<?php

namespace Database\Seeders;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Domain\Aggregator\AggregatorLiquidityService;
use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\AuditLog;
use App\Models\CommissionEntry;
use App\Models\CommissionRule;
use App\Models\LiquidityRequest;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * AGGREGATOR CONSOLE — dev data (Stage A).
 *
 * Seeds two tenants so isolation is demonstrable:
 *   - Ibrahim & Partners (AGG-00281, Niger/XOF): 5 agents across Maradi,
 *     Niamey and Zinder, funded XOF floats, cash-in/cash-out operations
 *     spread over today + past 8 days (series + dormancy + liquidity
 *     signals), aggregator commission entries, one risk alert.
 *   - Chidi & Co (AGG-00012, Nigeria/NGN): 1 agent with 2 operations —
 *     must NEVER appear in Ibrahim's console.
 *
 * Idempotent: every record is keyed on reference/code/email.
 */
class AggregatorSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensurePlatformCash('XOF');
        $this->ensurePlatformCash('NGN');
        // Capital injections so operational cash stays positive after the
        // float funding postings (DR Platform Cash / CR Agent Float) below.
        $this->ensureCapital('XOF');
        $this->ensureCapital('NGN');

        $ibrahim = $this->aggregator(
            email: 'ibrahim.agg@koriepay.test',
            name: 'Ibrahim & Partners',
            code: 'AGG-00281',
            country: 'NE',
            region: 'Maradi',
            city: 'Maradi',
        );

        $chidi = $this->aggregator(
            email: 'chidi.agg@koriepay.test',
            name: 'Chidi & Co',
            code: 'AGG-00012',
            country: 'NG',
            region: 'Kano',
            city: 'Kano',
        );

        // ── Ibrahim's network ──────────────────────────────────────────────
        $customers = User::where('role', 'customer')->pluck('id')->all();
        if ($customers === []) {
            $customers = [$this->customer('customer.ne@koriepay.test', 'NE')->id];
        }

        $aminu = $this->agent($ibrahim, 'AGT-00391', 'Aminu Musa', 'NE', 'Maradi', 'Maradi', Agent::STATUS_ACTIVE, 'verified', '2000000');
        $aisha = $this->agent($ibrahim, 'AGT-00412', 'Aisha Bello', 'NE', 'Niamey', 'Niamey', Agent::STATUS_ACTIVE, 'verified', '900000');
        $sani = $this->agent($ibrahim, 'AGT-00433', 'Sani Yusuf', 'NE', 'Maradi', 'Maradi', Agent::STATUS_ACTIVE, 'verified', '450000');
        $fatima = $this->agent($ibrahim, 'AGT-00454', 'Fatima Adamou', 'NE', 'Zinder', 'Zinder', Agent::STATUS_ACTIVE, 'pending', '120000');
        $danladi = $this->agent($ibrahim, 'AGT-00475', 'Danladi Sule', 'NE', 'Maradi', 'Maradi', Agent::STATUS_SUSPENDED, 'verified', '300000');

        // Operations across today + history (deterministic times for tests).
        $ops = [
            // [agent, type, amount, fee, commission, status, hoursAgo, daysAgo]
            [$aminu, 'cash_in', '900000', '4500', '1200', 'posted', 1, 0],
            [$aminu, 'cash_out', '500000', '2500', '800', 'posted', 3, 0],
            [$aminu, 'cash_out', '650000', '3250', '1000', 'posted', 5, 0],
            [$aminu, 'cash_in', '400000', '2000', '600', 'posted', 8, 0],
            [$aminu, 'cash_in', '1250000', '6250', '1800', 'posted', 10, 0],
            [$aisha, 'cash_in', '700000', '3500', '900', 'posted', 2, 0],
            [$aisha, 'cash_out', '300000', '1500', '450', 'posted', 6, 0],
            [$aisha, 'cash_in', '550000', '2750', '750', 'posted', 12, 0],
            [$sani, 'cash_in', '250000', '1250', '350', 'posted', 4, 0],
            [$sani, 'cash_out', '120000', '600', '180', 'posted', 9, 0],
            [$sani, 'cash_in', '400000', '2000', '550', 'posted', 11, 0],
            [$sani, 'cash_in', '300000', '1500', '420', 'failed', 7, 0],
            [$fatima, 'cash_in', '80000', '400', '120', 'posted', 13, 0],
            // History — yesterday and older (series, dormancy signals).
            [$aminu, 'cash_in', '1100000', '5500', '1500', 'posted', 0, 1],
            [$aminu, 'cash_out', '900000', '4500', '1200', 'posted', 0, 2],
            [$aisha, 'cash_in', '600000', '3000', '800', 'posted', 0, 2],
            [$sani, 'cash_in', '350000', '1750', '480', 'posted', 0, 4],
            [$aminu, 'cash_in', '950000', '4750', '1300', 'posted', 0, 6],
            [$aisha, 'cash_in', '450000', '2250', '620', 'posted', 0, 8],
        ];

        $i = 0;
        foreach ($ops as [$agent, $type, $amount, $fee, $commission, $status, $hoursAgo, $daysAgo]) {
            $created = now()->subDays($daysAgo)->subHours($hoursAgo);
            $reference = 'KPA-'.strtoupper(Str::random(8)).'-'.$i;
            $idem = 'agg-seed-'.($i++);

            AgencyOperation::firstOrCreate(
                ['idempotency_key' => $idem],
                [
                    'agent_id' => $agent->id,
                    'aggregator_id' => $ibrahim->id,
                    'customer_user_id' => $customers[array_rand($customers)],
                    'operation_type' => $type,
                    'currency_code' => 'XOF',
                    'amount' => $amount,
                    'fee' => $fee,
                    'commission_amount' => $commission,
                    'status' => $status,
                    'reference' => $reference,
                    'idempotency_key' => $idem,
                    'description' => 'Seeded '.$type.' (dev)',
                    'created_at' => $created,
                    'updated_at' => $created,
                ]
            );
        }

        // Commission accruals for Ibrahim — seeded only when none exist yet
        // (idempotent anchor; commission rows have no natural reference).
        if (! CommissionEntry::where('beneficiary_type', 'aggregator')->where('beneficiary_id', $ibrahim->id)->exists()) {
            foreach ($ops as [$agent, $type, $amount, $fee, $commission, $status, $hoursAgo, $daysAgo]) {
                if ($status !== 'posted') {
                    continue;
                }
                $created = now()->subDays($daysAgo)->subHours($hoursAgo);
                $entry = CommissionEntry::create([
                    'beneficiary_type' => 'aggregator',
                    'beneficiary_id' => $ibrahim->id,
                    'currency_code' => 'XOF',
                    'amount' => $commission,
                    'rule_id' => 'dev-agg-rate',
                    'status' => 'accrued',
                ]);
                $entry->forceFill(['created_at' => $created])->save();
            }
        }

        // Agent commission accruals — production accrues BOTH agent and
        // aggregator splits (AgencyService::execute → CommissionEngine);
        // the dev seed mirrors both so the profile's commissions tab (§16)
        // has real data. Idempotent: guarded on any agent entry existing.
        if (! CommissionEntry::where('beneficiary_type', 'agent')
            ->whereIn('beneficiary_id', $ibrahim->agents()->pluck('id'))->exists()) {
            foreach ($ops as [$agent, $type, $amount, $fee, $commission, $status, $hoursAgo, $daysAgo]) {
                if ($status !== 'posted') {
                    continue;
                }
                $created = now()->subDays($daysAgo)->subHours($hoursAgo);
                $entry = CommissionEntry::create([
                    'beneficiary_type' => 'agent',
                    'beneficiary_id' => $agent->id,
                    'currency_code' => 'XOF',
                    'amount' => $commission,
                    'rule_id' => 'dev-agg-rate',
                    'status' => 'accrued',
                ]);
                $entry->forceFill(['created_at' => $created])->save();
            }
        }

        // A risk alert on Danladi (suspended) for demo of the attention panel.
        RiskAlert::firstOrCreate(
            ['reference' => 'ALR-AGG-DEMO-001'],
            [
                'rule_id' => null,
                'category' => 'agent_restricted',
                'severity' => 'high',
                'entity_type' => 'agent',
                'entity_id' => $danladi->id,
                'risk_score' => 42,
                'status' => RiskAlert::STATUS_OPEN,
                'message' => 'Agent suspended pending review (seeded demo).',
                'details' => ['note' => 'Seeded demo: agent suspended pending review.'],
            ]
        );

        // ── Chidi's network (isolation proof) ───────────────────────────────
        $john = $this->agent($chidi, 'AGT-00901', 'John Okoro', 'NG', 'Kano', 'Kano', Agent::STATUS_ACTIVE, 'verified', '1500000');
        foreach ([['cash_in', '2000000', 'posted', 2], ['cash_out', '800000', 'posted', 5]] as $idx => [$type, $amount, $status, $hoursAgo]) {
            $ref = 'KPN-'.strtoupper(Str::random(8)).'-'.$idx;
            AgencyOperation::firstOrCreate(
                ['idempotency_key' => 'agg-seed-chidi-'.$idx],
                [
                    'agent_id' => $john->id,
                    'aggregator_id' => $chidi->id,
                    'customer_user_id' => $customers[array_rand($customers)],
                    'operation_type' => $type,
                    'currency_code' => 'NGN',
                    'amount' => $amount,
                    'fee' => '0',
                    'commission_amount' => '0',
                    'status' => $status,
                    'reference' => $ref,
                    'description' => 'Seeded '.$type.' (dev)',
                    'created_at' => now()->subHours($hoursAgo),
                    'updated_at' => now()->subHours($hoursAgo),
                ]
            );
        }

        // ── Liquidity workflow demo (Stage C, §25–26) ──────────────────────
        // A mix of request states for the liquidity command center. The
        // approved request EARMARKS operational cash (DR Platform Cash /
        // CR Pending Liquidity) — it never touches agent floats, keeping
        // the command-center float math deterministic. Funding states are
        // exercised by the Stage C tests through the real service.
        $liquidity = app(AggregatorLiquidityService::class);
        $this->liquidityRequest($liquidity, $ibrahim, $aminu, 'LRQ-SEED-001', '500000', 'approved', 26, 'Approved — within the agent\'s recorded cash-out pace.');
        $this->liquidityRequest($liquidity, $ibrahim, $sani, 'LRQ-SEED-002', '90000', 'rejected', 20, 'Duplicate of an earlier fulfilled request.');
        $this->liquidityRequest($liquidity, $ibrahim, $fatima, 'LRQ-SEED-003', '75000', 'pending', 5, null);
        $this->liquidityRequest($liquidity, $ibrahim, $aminu, 'LRQ-SEED-004', '100000', 'cancelled', 48, 'Agent withdrew the request.');

        // ── Stage E/F/G demo data ──────────────────────────────────────────
        // Failure cause recorded on the one failed operation (Sani, cash_in)
        // so failure intelligence groups by a real cause.
        AgencyOperation::where('idempotency_key', 'agg-seed-11')->update([
            'failure_reason' => 'insufficient_cash',
            'failure_details' => ['cause' => 'insufficient_cash', 'note' => 'Seeded demo: agent float below the required cash-out threshold.'],
        ]);

        // Commission rule definition (version 1) referenced by the seeded
        // accruals — gives the §41 audit trail a versioned rule on record.
        CommissionRule::firstOrCreate(
            ['name' => 'dev-agg-rate'],
            [
                'country_iso2' => 'NE', 'transaction_type' => 'cash_in', 'channel' => 'agent',
                'agent_tier' => 'bronze', 'rate' => '0.0012', 'flat_amount' => '0',
                'priority' => 1, 'is_active' => true,
            ]
        );

        // One adjustment and one reversal (negative accruals, seeded 5 days
        // ago so TODAY's commission KPI stays unchanged). Rule-id namespaces
        // adj:… / rev:… keep them out of the commission gross.
        if (! CommissionEntry::where('rule_id', 'adj:dev-agg')->where('beneficiary_id', $ibrahim->id)->exists()) {
            foreach ([['adj:dev-agg', '-3000', 'Adjustment for a disputed commission entry.'], ['rev:dev-agg', '-2000', 'Reversal of a duplicated commission accrual.']] as [$rule, $amount, $description]) {
                $entry = CommissionEntry::create([
                    'beneficiary_type' => 'aggregator',
                    'beneficiary_id' => $ibrahim->id,
                    'currency_code' => 'XOF',
                    'amount' => $amount,
                    'rule_id' => $rule,
                    'status' => 'accrued',
                ]);
                $entry->forceFill(['created_at' => now()->subDays(5)->startOfDay(), 'updated_at' => now()->subDays(5)->startOfDay()])->save();
            }
        }

        // Settlement batches — created through the REAL service so the
        // breakdown (gross/adjustments/reversals/net) is computed from the
        // seeded entries. No settled batch is seeded: settlement is exercised
        // by the Stage E tests (a settled payout would move the aggregator
        // float and shift the deterministic position math).
        $settlements = app(\App\Domain\Aggregator\AggregatorSettlementsService::class);
        $this->settlementBatch($settlements, $ibrahim, 'XOF', 'ASL-SEED-001', now()->subDays(10)->startOfDay(), now()->endOfDay(), 'pending');
        $this->settlementBatch($settlements, $ibrahim, 'XOF', 'ASL-SEED-002', now()->subDays(20)->startOfDay(), now()->subDays(12)->startOfDay(), 'under_review', 'Pre-settlement audit flagged a fee discrepancy.');
        $this->settlementBatch($settlements, $ibrahim, 'XOF', 'ASL-SEED-003', now()->subDays(8)->startOfDay(), now()->subDays(6)->startOfDay(), 'failed', 'Failed at the payout rail.');
        $this->settlementBatch($settlements, $chidi, 'NGN', 'ASL-SEED-004', now()->subDays(7)->startOfDay(), now()->endOfDay(), 'pending');

        // Risk alerts — P0–P3 severity (column is varchar(3)), mixed statuses
        // for the alert center + workflow demo (§52–57).
        $riskAlertData = [
            ['ALR-AGG-DEMO-002', 'velocity', 'P1', $sani, \App\Models\RiskAlert::STATUS_OPEN, 68, 'Unusually high operation velocity at Sani Yusuf (AGT-00433) — 14 ops in the last hour.', ['metric' => 'ops_per_hour', 'observed' => 14, 'threshold' => 5]],
            ['ALR-AGG-DEMO-003', 'collusion_pattern', 'P2', $aisha, 'investigating', 45, 'Repeated same-customer activity across Aisha Bello (AGT-00412) and another network agent.', ['metric' => 'shared_customer', 'window_minutes' => 60]],
            ['ALR-AGG-DEMO-004', 'kyc_drift', 'P3', $fatima, 'resolved', 22, 'KYC document on file is close to expiry — resubmission advised.', ['metric' => 'document_expiry']],
            ['ALR-AGG-DEMO-005', 'informational', 'P3', null, 'open', 10, 'Network crossed five agents — monitoring and velocity baselines enabled.', ['metric' => 'network_growth']],
        ];
        foreach ($riskAlertData as [$ref, $category, $severity, $entity, $status, $score, $message, $details]) {
            $alert = RiskAlert::firstOrCreate(
                ['reference' => $ref],
                [
                    'category' => $category,
                    'severity' => $severity,
                    'entity_type' => $entity !== null ? 'agent' : 'aggregator',
                    'entity_id' => $entity?->id ?? $ibrahim->id,
                    'risk_score' => $score,
                    'status' => $status,
                    'message' => $message,
                    'details' => $details,
                ]
            );
            if ($alert->status === 'resolved' && $alert->resolved_at === null) {
                $alert->forceFill(['resolved_by' => $ibrahim->user_id, 'resolved_at' => now()->subDays(2), 'resolution_note' => 'Seeded demo: resolved after document renewal.'])->save();
            }
        }

        // ── Stage H/I demo data (support, documents, reports, read model) ──
        $this->supportFixtures($ibrahim, $chidi);
        $this->documentFixtures($ibrahim);
        $this->reportFixtures($ibrahim);

        // Analytical read model: backfill every day from the earliest seeded
        // operation to today (idempotent per date). Gaps inside the window
        // are recorded as honest zero rows (is_empty).
        $earliest = AgencyOperation::where('aggregator_id', $ibrahim->id)->min('created_at');
        if ($earliest !== null) {
            app(\App\Domain\Aggregator\AggregatorInsightsService::class)
                ->backfill($ibrahim, \Illuminate\Support\Carbon::parse($earliest)->startOfDay(), now());
        }

        $this->command?->info('Aggregator dev data ready: AGG-00281 (XOF) + AGG-00012 (NGN) with agents, floats, operations, commissions, alerts, liquidity requests, settlements, risk alerts, support, documents, reports, daily metrics.');
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function aggregator(string $email, string $name, string $code, string $country, string $region, string $city): Aggregator
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt('password123'),
                'country_code' => $country === 'NE' ? 'NER' : 'NGA',
                'kyc_tier' => 2,
                'kyc_status' => 'verified',
                'phone_number' => $country === 'NE' ? '+22790000999' : '+23490000999',
                'is_active' => true,
            ]
        );
        $user->forceFill(['role' => 'aggregator'])->save();

        $aggregator = Aggregator::firstOrCreate(
            ['code' => $code],
            [
                'user_id' => $user->id,
                'name' => $name,
                'status' => Aggregator::STATUS_ACTIVE,
                'country_iso2' => $country,
                'region' => $region,
                'city' => $city,
                'kyc_status' => 'verified',
            ]
        );
        $aggregator->forceFill(['user_id' => $user->id])->save();

        // Aggregator float (ledger liability) in the country currency.
        $this->ensureFloat($aggregator, $country === 'NE' ? 'XOF' : 'NGN', $aggregator->name.' Float');

        return $aggregator;
    }

    private function agent(Aggregator $aggregator, string $code, string $name, string $country, string $region, string $city, string $status, string $kyc, string $float): Agent
    {
        $email = strtolower(str_replace(' ', '.', $name)).'@koriepay.test';
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt('password123'),
                'country_code' => $country === 'NE' ? 'NER' : 'NGA',
                'kyc_tier' => 1,
                'kyc_status' => $kyc === 'verified' ? 'verified' : 'pending',
                'phone_number' => '+227'.mt_rand(90000000, 99999999),
                'is_active' => true,
            ]
        );
        $user->forceFill(['role' => 'agent'])->save();

        $fresh = ! Agent::where('agent_code', $code)->exists();

        $agent = Agent::firstOrCreate(
            ['agent_code' => $code],
            [
                'user_id' => $user->id,
                'aggregator_id' => $aggregator->id,
                'status' => $status,
                'tier' => 'bronze',
                'country_iso2' => $country,
                'region' => $region,
                'city' => $city,
                'kyc_status' => $kyc,
                'risk_score' => null,
            ]
        );
        $agent->forceFill(['aggregator_id' => $aggregator->id])->save();

        // Mirror production's audit trail (AgencyService::registerAgent +
        // assignAgentToAggregator) so the profile's audit tab has real data.
        if ($fresh) {
            AuditLog::record('agent.registered', null, $user->id, [
                'description' => 'Agent registered ('.$code.', '.$country.') — seeded dev network.',
                'event_type' => 'operations',
                'metadata' => ['agent_id' => $agent->id, 'agent_code' => $code, 'country_iso2' => $country],
            ]);
            AuditLog::record('agent.assigned.aggregator', null, $user->id, [
                'description' => 'Agent '.$code.' assigned to aggregator '.$aggregator->code.'.',
                'event_type' => 'operations',
                'metadata' => ['agent_id' => $agent->id, 'aggregator_id' => $aggregator->id],
            ]);
        }

        $currency = $country === 'NE' ? 'XOF' : 'NGN';
        $account = $this->ensureFloat($agent, $currency, $agent->agent_code.' Float');
        if (bccomp((string) $account->balance, '0', 2) <= 0) {
            $this->fundFloat($account, $currency, $float, 'agg-seed-float-'.$agent->agent_code);
        }

        return $agent;
    }

    private function customer(string $email, string $country): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Dev Customer',
                'password' => bcrypt('password123'),
                'country_code' => $country,
                'phone_number' => '+227'.mt_rand(90000000, 99999999),
                'is_active' => true,
            ]
        );
    }

    private function ensurePlatformCash(string $currency): void
    {
        LedgerAccount::firstOrCreate(
            ['account_type' => 'asset', 'currency_code' => $currency],
            ['name' => 'Platform Cash', 'is_system' => true, 'balance' => '0']
        );
    }

    private function ensureFloat($owner, string $currency, string $name): LedgerAccount
    {
        return LedgerAccount::firstOrCreate(
            [
                'owner_type' => $owner instanceof Agent ? 'agent' : 'aggregator',
                'owner_id' => $owner->id,
                'currency_code' => $currency,
            ],
            [
                'account_type' => 'liability',
                'name' => $name,
                'balance' => '0',
                'is_active' => true,
            ]
        );
    }

    private function fundFloat(LedgerAccount $float, string $currency, string $amount, string $idem): void
    {
        $cash = LedgerAccount::query()
            ->where('account_type', 'asset')->where('currency_code', $currency)->first();

        if ($cash === null) {
            return;
        }

        try {
            app(LedgerService::class)->post(
                [
                    ['account_id' => $cash->id, 'side' => 'debit', 'amount' => $amount],
                    ['account_id' => $float->id, 'side' => 'credit', 'amount' => $amount],
                ],
                'deposit',
                description: 'AggregatorSeeder float funding (dev)',
                idempotencyKey: $idem,
            );
        } catch (\Throwable $e) {
            $this->command?->warn('Float funding skipped for '.$idem.': '.$e->getMessage());
        }
    }

    /** Capital injection so operational cash stays positive (DR Cash / CR Capital). */
    private function ensureCapital(string $currency): void
    {
        $cash = LedgerAccount::query()
            ->where('account_type', 'asset')->where('currency_code', $currency)->first();
        if ($cash === null) {
            return;
        }

        $capital = LedgerAccount::firstOrCreate(
            ['code' => 'CAPITAL-'.$currency],
            [
                'owner_type' => 'system', 'account_type' => 'liability', 'currency_code' => $currency,
                'name' => 'Capital Reserve '.$currency, 'is_system' => true, 'balance' => '0',
            ]
        );

        // Balance-anchored idempotency (TTL-proof across re-seeds).
        if (bccomp((string) $capital->balance, '0', 2) > 0) {
            return;
        }

        $amount = $currency === 'XOF' ? '5000000' : '2000000';

        try {
            app(LedgerService::class)->post(
                [
                    ['account_id' => $cash->id, 'side' => 'debit', 'amount' => $amount],
                    ['account_id' => $capital->id, 'side' => 'credit', 'amount' => $amount],
                ],
                'capital_injection',
                description: 'AggregatorSeeder capital injection (dev)',
                idempotencyKey: 'agg-seed-capital-'.$currency,
            );
        } catch (\Throwable $e) {
            $this->command?->warn('Capital injection skipped for '.$currency.': '.$e->getMessage());
        }
    }

    /**
     * Idempotent demo liquidity request (Stage C). Drives the REAL workflow
     * service so states, ledger postings and audit rows stay consistent.
     * Approved requests only earmark operational cash — agent floats are
     * never modified here.
     */
    private function liquidityRequest(
        AggregatorLiquidityService $service,
        Aggregator $aggregator,
        Agent $agent,
        string $reference,
        string $amount,
        string $finalStatus,
        int $hoursAgo,
        ?string $note,
    ): LiquidityRequest {
        $existing = LiquidityRequest::where('reference', $reference)->first();
        if ($existing !== null) {
            return $existing;
        }

        $request = $service->submit($aggregator, $agent, [
            'amount' => $amount,
            'currency_code' => $agent->country_iso2 === 'NE' ? 'XOF' : 'NGN',
            'reason' => LiquidityRequest::REASON_CASH_OUT_DEMAND,
            'requested_by_type' => 'agent',
        ], $agent->user_id, $reference);

        $request->forceFill([
            'reference' => $reference,
            'created_at' => now()->subHours($hoursAgo),
            'updated_at' => now()->subHours($hoursAgo),
        ])->save();

        match ($finalStatus) {
            'approved' => $service->review($request, true, $note, $aggregator->user_id),
            'rejected' => $service->review($request, false, $note, $aggregator->user_id),
            'cancelled' => $service->cancel($request, $aggregator->user_id, $note),
            default => null, // pending
        };

        return $request->refresh();
    }
    /**
     * Stage H support fixtures — tickets with deterministic SLA states
     * relative to now, replies, plus a foreign ticket for isolation demo.
     */
    private function supportFixtures(Aggregator $ibrahim, Aggregator $chidi): void
    {
        $service = app(\App\Domain\Aggregator\AggregatorSupportService::class);

        $tickets = [
            // [aggregator, priority, category, subject, message, slaOffsetHours, status, actorUser]
            [$ibrahim, 'high', 'settlement', 'Settlement payout delay', 'The August settlement batch has not been paid out yet.', -2, 'open', $ibrahim->user],
            [$ibrahim, 'medium', 'technical', 'Agent terminal sync issue', 'Two agents report the terminal is slow to sync balances.', 20, 'in_progress', $ibrahim->user],
            [$ibrahim, 'low', 'kyc', 'KYC document guidance', 'Asked for the current list of accepted ID documents.', -6, 'resolved', $ibrahim->user],
            [$chidi, 'low', 'commission', 'Commission statement query', 'Foreign ticket — must never appear for AGG-00281.', 30, 'open', $chidi->user],
        ];

        foreach ($tickets as [$aggregator, $priority, $category, $subject, $message, $slaOffset, $status, $actor]) {
            $existing = \App\Models\SupportTicket::where('subject', $subject)->where('aggregator_id', $aggregator->id)->first();
            if ($existing !== null) {
                continue;
            }

            $ticket = $service->raise($aggregator, $actor, $category, $subject, $message, $priority);
            $ticket->forceFill(['sla_due_at' => now()->addHours($slaOffset)])->save();

            if ($status !== 'open') {
                $service->setStatus($ticket, $actor, $status);
            }
        }

        // Replies on the in-progress technical ticket (one public, one internal).
        $tech = \App\Models\SupportTicket::where('subject', 'Agent terminal sync issue')->where('aggregator_id', $ibrahim->id)->first();
        if ($tech !== null && $tech->replies()->count() === 0) {
            $service->reply($tech, $ibrahim->user, 'We are investigating — can you share the affected agent codes?', false);
            $service->reply($tech, $ibrahim->user, 'Internal: likely a stale sync job; check the queue worker.', true);
        }
    }

    /**
     * Stage H document fixtures — real small text files so downloads work.
     * System doc published to every aggregator; own docs tenant-scoped.
     */
    private function documentFixtures(Aggregator $ibrahim): void
    {
        $store = function (string $title, string $category, string $content, string $path) use ($ibrahim): void {
            $exists = \App\Models\AggregatorDocument::where('title', $title)
                ->where(fn ($q) => $q->where('aggregator_id', $ibrahim->id)->orWhere('is_system', true))
                ->exists();
            if ($exists) {
                return;
            }

            \Illuminate\Support\Facades\Storage::disk((string) config('filesystems.default'))->put($path, $content);

            \App\Models\AggregatorDocument::create([
                'aggregator_id' => $ibrahim->id,
                'category' => $category,
                'title' => $title,
                'file_path' => $path,
                'file_name' => basename($path),
                'mime' => 'text/plain',
                'size_bytes' => strlen($content),
                'visibility' => 'network',
                'is_system' => false,
                'uploaded_by' => $ibrahim->user_id,
            ]);
        };

        $store(
            'Rate card — XOF (dev)',
            'rate_card',
            "KoriePay XOF rate card (dev seed)\nCash-in fee: 0.5%\nCash-out fee: 0.5%\nAgent commission: variable by tier\n",
            'documents/'.$ibrahim->code.'/rate-card-xof-dev.txt'
        );
        $store(
            'Agent onboarding pack (dev)',
            'agent_onboarding',
            "KoriePay agent onboarding pack (dev seed)\n1. Submit KYC documents\n2. Fund your float\n3. Start serving customers\n",
            'documents/'.$ibrahim->code.'/agent-onboarding-pack-dev.txt'
        );

        // System-published notice visible to every aggregator (aggregator_id null).
        if (! \App\Models\AggregatorDocument::where('title', 'Compliance notice — transaction limits')->exists()) {
            $content = "KoriePay compliance notice (dev seed)\nAggregator networks must verify agent KYC before activation.\n";
            $path = 'documents/system/compliance-notice-limits.txt';
            \Illuminate\Support\Facades\Storage::disk((string) config('filesystems.default'))->put($path, $content);
            \App\Models\AggregatorDocument::create([
                'aggregator_id' => null,
                'category' => 'compliance',
                'title' => 'Compliance notice — transaction limits',
                'file_path' => $path,
                'file_name' => 'compliance-notice-limits.txt',
                'mime' => 'text/plain',
                'size_bytes' => strlen($content),
                'visibility' => 'network',
                'is_system' => true,
                'uploaded_by' => null,
            ]);
        }
    }

    /**
     * Stage H report fixture — one completed CSV artifact, generated through
     * the real service (idempotent by reference).
     */
    private function reportFixtures(Aggregator $ibrahim): void
    {
        $service = app(\App\Domain\Aggregator\AggregatorReportsService::class);
        $existing = \App\Models\ReportJob::where('reference', 'RPT-SEED-001')->first();
        if ($existing !== null) {
            return;
        }

        $job = $service->request($ibrahim, $ibrahim->user, 'agent', 'csv', now()->subDays(7)->toDateString(), now()->toDateString());
        $job->forceFill(['reference' => 'RPT-SEED-001'])->save();
        // refresh(): the sync dispatch already ran the job, so the in-memory
        // instance must be re-read before the idempotent generate() call —
        // otherwise generate() sees stale 'queued' and runs twice.
        $service->generate($job->refresh());
    }

    /**
     * Idempotent demo settlement batch (Stage E). Driven through the real
     * service so the gross/adjustments/reversals/net breakdown is computed
     * from seeded commission entries. Never settles — settlement payouts are
     * exercised by tests to keep the deterministic position math intact.
     */
    private function settlementBatch(
        \App\Domain\Aggregator\AggregatorSettlementsService $service,
        Aggregator $aggregator,
        string $currency,
        string $reference,
        \Illuminate\Support\Carbon $from,
        \Illuminate\Support\Carbon $to,
        string $finalStatus,
        ?string $note = null,
    ): void {
        $existing = \App\Models\AggregatorSettlement::where('reference', $reference)->first();
        if ($existing !== null) {
            return;
        }

        $batch = $service->create($aggregator, $currency, $from, $to, $aggregator->user_id);
        $batch->forceFill(['reference' => $reference])->save();

        match ($finalStatus) {
            'under_review' => $service->underReview($batch, $aggregator->user_id, $note),
            'failed' => $service->fail($batch, $aggregator->user_id, $note),
            default => null, // pending
        };
    }
}
