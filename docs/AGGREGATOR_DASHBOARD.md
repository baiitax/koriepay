# KoriePay Aggregator Command & Network Banking Dashboard — Audit & Build Plan

> Engineering deliverable — governed by the **KoriePay Aggregator Command & Network Banking Dashboard** brief (150 points).
> Status: **Stage A — COMPLETE ✅ · Stage B (Agents §14–22) — COMPLETE ✅ · Stage C (Liquidity §23–28) — COMPLETE ✅ · Stage E (Commissions & settlement) — COMPLETE ✅ · Stage F (Network intelligence) — COMPLETE ✅ · Stage G (Risk & alerts) — COMPLETE ✅ (all 2026-08-31).** Next: Stage D (Transactions & investigation §30–37).
> Suite: `php artisan test` — **256 passed / 1044 assertions / 0 failures** (adds `AggregatorAgentsStageBTest`: 29 tests — RBAC + permission gates, tenant-scoped directory/profile/commissions/audit, explainable performance score, dormancy with labelled estimates, capture-only recruitment, backend-controlled status changes, IDOR 404s).
> Stack: Laravel 12 + Livewire 3 + Tailwind glassmorphism design system (shared `kp/*` components).
> Markets: Niger (XOF) + Nigeria (NGN).

---

## 1. Audit findings (per brief §148)

### What already exists (backend, Phase 4–7)
| Layer | Existing | Notes |
|---|---|---|
| RBAC | `role_permissions` table + `RoleMiddleware`/`CheckPermission` | `aggregator` role exists with only 4 perms: `agent.view, agent.recruit, network.view, commission.view` |
| Org model | `aggregators` + `agents` + `agency_operations` tables (migration `001200_create_agency_banking`) | `agents.aggregator_id` FK exists; **nullable** — no tenant scoping enforced anywhere |
| Agent model | `Agent` (status/tier/country/region/city/kyc_status/risk_score) | statuses: pending/active/suspended/inactive/terminated |
| Aggregator model | `Aggregator` (code/name/status/country/region/city/kyc_status/commission_override_rate) | no profile extras (phone/email live on linked `User`) |
| Ledger | `LedgerService` (double-entry, per-currency balanced, immutability, guarded balance writes) | the authoritative money layer; agent/aggregator float = `ledger_accounts owner_type=agent/aggregator` |
| Agency ops | `AgencyService` (registerAgent, cash-in/out, idempotent, audited) + `CommissionEngine` (accrued splits: agent/aggregator/platform) | `agency_operations` is the network metrics source |
| Commissions | `CommissionEntry`/`CommissionRule` | versioned rules, per-operation accrual |
| Settlement | `Settlement`/`SettlementItem` + `SettlementService` (Domain/Reconciliation) | scheduled→settled lifecycle |
| Reconciliation | `ReconciliationService` + `ReconciliationRun`/`ReconciliationItem` | matched/pending/exception |
| Risk | `RiskService` + `RiskAlert` + `RiskRule` + `ApprovalService` (maker–checker) | alerts + approvals exist |
| Support | `SupportTicket` model | no aggregator-facing UI |
| Audit | `AuditLog` (`record()` helper) | written by AgencyService etc. |

### What is missing (to build)
1. **Aggregator console UI entirely**: no `layouts/aggregator`, no `app/Livewire/Aggregator/*`, no `aggregator.*` routes.
2. **Tenant isolation (§3)**: nothing scopes agents/operations/commissions by `aggregator_id`; no server-side ownership guard for aggregators.
3. **Aggregator RBAC perms (§83)**: `liquidity.*`, `transaction.investigate`, `risk.view`, `support.*`, `report.generate`, `settlement.view` etc. absent.
4. **Dev data**: 0 aggregators, 1 agent — need a dev seeder.
5. **Read-model/analytics (§99–100)**: aggregator KPIs must be computed from real tables now; `aggregator_daily_metrics` projections are a later-stage optimization.
6. Honest-state conventions from the Customer App carry over (estimates labelled, no fabricated data).

---

## 2. Staged build plan (maps brief sections)

### Stage A — Foundation + Command Center home ✅ (this build)
- RBAC expansion (additive migration): `aggregator` perms per §83.
- `AggregatorTenantService` — resolves the current aggregator from the user; scoped agent-id resolver; every query server-side scoped (§3, §94).
- `AggregatorMetricsService` — real KPIs (§10–11): total/active agents, transactions today, network volume, commission, network liquidity; attention items (§12); daily brief derived from data (§13); 7/30-day network performance series (§44); top agents.
- `layouts.aggregator` — glass sidebar (§7) + header (§8: greeting, AGG code, system status, notifications).
- Home dashboard Livewire + view (§5, §145): KPI cards, ACTION REQUIRED panel, network performance chart, liquidity snapshot, top agents, recent activity, quick actions (§111), global filter bar stub (§9).
- Seeder: dev aggregator (Ibrahim, AGG-…) + 4 agents + funded floats + cash-in/cash-out operations + commissions + settlements + risk alert.
- Tests: `AggregatorDashboardStageATest` — RBAC 403, tenant isolation (two aggregators, no leakage), KPI math vs seeded data, honest empty state, page render 200, dashboard uses ledger (no balance columns).

### Stage B — Agents (§14–22) ✅ (this build)
- `AggregatorAgentsService` — single Stage B domain service: server-paginated directory with live 30-day stats + ledger floats; per-tab profile payloads; explainable weighted performance score (§17, honest `null` on no signal); productivity (§18); dormancy measured from posted ops with labelled estimates (§19); onboarding pipeline with real counts + honestly-null conversion on empty networks (§21–22); capture-only recruitment via AgencyService (audited, pending until KYC, §20).
- `Agents` Livewire (directory: filters — status/KYC/region/city/search/sort — pipeline strip, pagination, honest empty states), `RecruitAgent` Livewire (validated capture form, duplicate email/phone rejection, no-activation guarantee), `AgentProfile` Livewire (10 tabs; status actions go through AgencyService and are Gate-gated `agent.suspend`/`agent.reactivate`; IDOR-safe foreign agents 404).
- Routes: `/aggregator/agents` (+ `permission:agent.view`), `/agents/recruit` (`agent.recruit`), `/agents/{agent:agent_code}` (`agent.profile.view`) — sidebar Agents item now live.
- Seeder extended: agent commission accruals (mirrors production splits) + audit logs for registrations/assignments; still idempotent (verified twice).

### Stage C — Liquidity (§23–28) ✅ (this build)
- `AggregatorLiquidityService` — single Stage C domain service:
  - **Command center** — per-currency network position from the LEDGER ONLY: agent wallets (sum of agent float liabilities), aggregator wallet, pending (earmarked), platform cash pool (gross asset), available operational cash (= pool − agent/aggregator floats − pending, i.e. the unencumbered reserve), platform settlement exposure; 7-day cash-in/out demand (labelled estimate + basis); forecast 6h/24h/7d extrapolated from 7-day posted cash-out history (labelled estimate; honest 0 when no history — never fabricated); per-agent Healthy/Watch/Low/Critical via the same buffer buckets as the Stage A home (float ÷ average daily cash-out demand, no-history and suspended overrides); currency alerts (agent low/critical coverage, thin operational cash vs projected demand).
  - **Request workflow** (agent → review → risk/limit → approval → settlement → ledger, audited): `submit` (validates amount + currency matches the agent's country; tenant-scoped), `review` (risk assessment — amount >6× average daily cash-out demand is auto-blocked as high-risk with labelled-estimate notes; 3–6× flagged medium; non-active agents blocked; reject does NOT move money), `approve` EARMARKS operational cash on the ledger (DR Platform Cash / CR Pending Liquidity — idempotent), `fund` releases the earmark to the agent float (DR Pending / CR Agent Float — replay-safe, never double-credits), `cancel` releases the earmark for approved requests and moves nothing for pending. Every transition writes an `AuditLog` row (`liquidity.*`).
  - **Ledger concepts are separate accounts** (`code`-discriminated): `PENDING-{CCY}` pending liquidity (liability), `CAPITAL-{CCY}` capital reserve, `REV-{CCY}` platform revenue, plus agent/aggregator float liabilities and the platform cash asset — balances never collide.
- `LiquidityRequest` model + `liquidity_requests` migration: reference (LRQ-…), agent/aggregator, currency, amount, reason, status (pending|in_review|approved|rejected|funded|cancelled), risk_level + risk_notes (JSON), requested_by/reviewed_by, review_note, ledger_transaction_id, funded_at/cancelled_at.
- `Liquidity` Livewire + view (`/aggregator/liquidity`, `permission:liquidity.view`): currency filter (XOF/NGN never mixed), position cards per ledger concept, forecast/demand panels with Estimate labels, agent status table with cash-out risk, requests list with status filters + pagination, approve/reject (with note) / fund / cancel actions gated server-side by `liquidity.review`, raise-on-behalf form gated by `liquidity.request`, toasts via the shared listener.
- Seeder extended (idempotent): capital injections (5,000,000 XOF / 2,000,000 NGN, balance-anchored) so operational cash stays positive; demo requests LRQ-SEED-001…004 (approved/rejected/pending/cancelled) driven through the REAL service so states, ledger postings and audit rows stay consistent — agent floats are never touched by the demo.
- Tests: `AggregatorLiquidityStageCTest` — 29 tests / 125 assertions: RBAC (customer/agent/admin 403), honest not-provisioned state, position math vs ledger accounts, estimate labels + basis, forecast equals posted cash-out total, honest-zero forecast without history, per-agent buckets (healthy/no-demand/suspended + custom low agent), tenant isolation + IDOR (cross-tenant request id → 404 on approve/fund), full workflow incl. ledger earmark/release/fund postings, idempotent replay, high-risk auto-block, cancel semantics, Livewire actions with notes + toasts, permission guards on actions, seeded demo states, audit lifecycle, and balanced-postings invariant.

### Stage D — Transactions & investigation (§30–37)
Transaction center with full filter set, statuses, detail panel, investigation timeline (request/processing/provider/settlement/final), UNKNOWN state honesty (§34, no force-success), reversals with compensating entries (§35), reconciliation dashboard (matched/pending/exception + aging §36–37, §90–92).

### Stage E — Commissions & settlement (§38–43, §66–67) ✅ (this build)
- `AggregatorCommissionsService` — commission intelligence from REAL `commission_entries` (aggregator-scoped): today / rolling 7-day / month windows (gross, paid, pending, count); earnings split gross / adjustments / reversals / net / paid / pending — gross is NEVER labelled net (formula disclosed in the payload); product breakdown by rule with rule-version details from `commission_rules` (§41); per-agent commission table; full audit trail labelling commission vs adjustment vs reversal (namespaced rule ids `adj:*` / `rev:*`) with version data where the rule is on record.
- `AggregatorSettlementsService` — settlement lifecycle (`create → markProcessing → settle / underReview / fail`): batch breakdown computed from the period's ledger-authoritative entries (gross, combined adjustments incl. reversals, fees, commission, net); `expected_amount = net` (what the aggregator should receive); `settle()` posts a REAL idempotent ledger payout (DR Settlement Expense / CR Aggregator Float, `ASL-SETTLE-{reference}`), marks the period's entries paid, records `actual_amount`, and reconciliation() honestly reports matched / difference / unreconciled (delta shown, never silently absorbed); settled/failed batches are final.
- Routes: `aggregator/commissions` + `aggregator/settlements` (permission-gated), sidebar entries live.
- Tests: `AggregatorCommissionsStageETest` (16 tests / 88 assertions) — exact KPI alignment (today 8,700.00 = Stage A lock), earnings-never-gross, idempotent settle with ledger assertions, reconciliation difference case, tenant IDOR, RBAC.

### Stage F — Network intelligence (§44–51) ✅ (this build)
- `AggregatorNetworkService` — analytics derived from REAL posted/failed operations with hourly / daily / weekly / monthly ranges (volume, count, active agents, average per agent, success/failure rates, reversals from reversal postings — honest zeros where no data); failure intelligence grouped by the RECORDED `failure_reason` with an honest "cause not recorded" bucket for rows without one; coverage by city from agent records + 7-day posted cash-in/out volume (aggregated — no customer PII), thin-coverage and recruitment recommendations from measurable demand vs presence (labelled `estimate` + `basis`, never guesses); weighted, explained network health score (success 30 / activity 30 / coverage 20 / stability 20, per-component formula disclosure, honest `null` "No signal" on empty networks).
- Routes: `aggregator/network` (permission-gated), sidebar entry live.
- Tests: `AggregatorNetworkStageFTest` (12 tests / 61 assertions) — exact seeded-op analytics, cause-not-recorded honesty, coverage math, reachable recruit recommendation, empty-network honesty, tenant scoping.

### Stage G — Risk & alerts (§52–57, §142–143) ✅ (this build)
- `AggregatorRiskService` — velocity monitoring (§52: >5 ops in the trailing hour, and cash-out in 24h > 3× the agent's 7-day daily cash-out average, both labelled estimates with thresholds disclosed); collusion signals ALWAYS framed as "Risk signal (pattern)" with an explicit "not a fraud conclusion" disclaimer (§53); KYC inconsistencies from real agent/user records (§54); alert center with severity mapping P0→critical … P3→low and what / affected / timestamp / status fields; STRICT audited workflow detected→assigned→investigating→resolved|false-positive with every transition written to `audit_logs` (`risk.alert.*`) and terminal states immutable; grouped + deduplicated notifications (category|severity, each alert counted once, severity-ordered).
- Routes: `aggregator/risk` (permission-gated), sidebar entry live.
- Tests: `AggregatorRiskStageGTest` (15 tests / 76 assertions) — velocity thresholds, pattern-not-fraud wording, strict state machine (skip/terminal rejected), audit context, notification dedup/grouping, tenant IDOR.

### Stage H — Support, documents, reports (§59–63)
Support cases (categories, priority, SLA countdown), document center (authorized docs only), report center (agent/transaction/commission/liquidity/settlement/risk/KYC/network growth; CSV/Excel/PDF; async generation; report audit log).

### Stage I — Hardening & polish (§64–65, §96–97, §100–110, §113–116, §135–144)
Aggregator profile + backend-sourced limits, observability indicators, analytical read models (`aggregator_daily_metrics` etc.), cache strategy (never cache balances/authorizations), pagination/search, error boundaries + skeletons, refresh indicator (authoritative/estimated/cached), mobile aggregator experience, EOD summary, network growth/retention/productivity, command search (Ctrl/Cmd+K), data quality center.

---

## 3. Hard rules carried from the brief
- Ledger is authoritative; frontend never shows a fabricated number (§147 P1–P2).
- Unknown ≠ failed/success (§130, §34).
- Posted entries immutable; corrections = compensating entries (§81).
- All financial ops idempotent (§75); all privileged ops audited (§82).
- Tenant isolation by `aggregator_id` on every query (§3, §8).
- Fees/commissions versioned + server-controlled (§86–87, §40–41).
- Estimates and forecasts always labelled as estimates (§25–26).
- Agent status changes are backend-controlled, never arbitrary frontend writes (§15).
