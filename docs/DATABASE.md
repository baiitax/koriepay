# KoriePay Database Architecture

> Engineering deliverable #2 of 12 · Status: **living document** — updated as each phase lands.
> Scope: Niger (XOF/BCEAO) + Nigeria (NGN/CBN); additive for Ghana/Benin/Togo/Côte d'Ivoire/Senegal/Mali.

---

## 1. Principles

1. **Ledger is the source of truth.** No financial table's balance field is authoritative on its own — balances are projections reconciled against immutable `ledger_*` entries. Monetary movement originates only in `ledger_transactions` / `ledger_entries`.
2. **Money is never floating point.** Amounts are `DECIMAL` (or integer minor units) with bcmath at the domain layer; `currencies.minor_units` is authoritative (NGN=2, XOF=0).
3. **Country-aware by configuration, not code.** `countries` / `currencies` tables drive behavior; adding a market is an additive seed, never a code rewrite.
4. **Append-only where it matters.** Posted transactions and ledger entries are never edited or deleted; corrections are formal reversals (mirror entries).
5. **Everything is additive.** Corrective migrations add columns/tables (see `000700`, `000800`, `000900`) — existing data and deployed schema are never mutated destructively.
6. **Audit everything.** Identity and financial actions write to `audit_logs` with real, canonical columns (see §5).

---

## 2. Migration inventory

| Migration | Phase | Purpose |
|---|---|---|
| `2023_01_01_000001_create_users_table` | base | users, password_reset_tokens, sessions |
| `2026_03_06_031824_create_audit_logs_table` | base | audit_logs (original schema) |
| `2026_03_28…` / `2026_03_29…` | base | role column, username/phone, regional/kyc/status columns |
| `2026_04_04…` / `2026_04_05…` | base | referral, profile, security-preference, photo columns |
| `2026_08_31_000100_create_countries_and_currencies` | 2 | countries + currencies (NGN/XOF/USD seeded) |
| `2026_08_31_000200_create_ledger_tables` | 2 | ledger_accounts, ledger_transactions, ledger_entries |
| `2026_08_31_000300_create_idempotency_states_and_attempts` | 2 | idempotency_keys, transaction_states, transaction_attempts |
| `2026_08_31_000400_create_payment_infrastructure` | 2 | payment_providers, payment_rails, provider_configs, webhook_events |
| `2026_08_31_000500_create_commissions_and_balances` | 2 | commission_rules, commission_entries, balance_snapshots |
| `2026_08_31_000600_create_rbac_and_fx_fix` | 2 | role_permissions (+ seed matrix) + additive fx_rates columns |
| `2026_08_31_000700_fix_transactions_schema` | 2 | additive: receiver_id, fee_charged, type, description, bank fields, auth_code on transactions |
| `2026_08_31_000800_create_identity_layer` | 4 | users identity columns (country_code, is_active, last_login_at, kyc_tier), devices, kyc_submissions, login_events, admin view-permissions |
| `2026_08_31_000900_fix_audit_logs_schema` | 4 | additive: target_id, user_name, event_type, metadata, payload on audit_logs |
| `2026_08_31_001000_create_payment_core` | 5 | transactions += provider, rail, provider_reference, country_code, ledger_transaction_id, error_reason; **UNIQUE idempotency_key** + indexes; seeds internal `ledger` provider + WALLET_NG/WALLET_NE rails |
| `2026_08_31_001100_make_transactions_receiver_name_nullable` | 5 | receiver_name nullable (withdrawals have no receiver) |
| `2026_08_31_001200_create_agency_banking` | 6 | agents, aggregators, agency_operations + RBAC extras (agency.view/manage, agent.approve, aggregator.approve …) |
| `2026_08_31_001300_create_risk_layer` | 7 | risk_rules, risk_alerts, transaction_holds, approval_requests + users.risk_score |
| `2026_08_31_001400_create_reconciliation_layer` | 8 | settlements, settlement_items, reconciliation_runs, reconciliation_items + transaction_attempts.amount (additive) |
| `2026_08_31_105737_create_personal_access_tokens_table` | 5 | Sanctum API tokens |

All migrations run forward and `down()` cleanly on SQLite + MySQL (verified by rollback smoke in CI/dev). Known legacy exception: `2026_03_29_004033` `down()` cannot drop `users.username` while `users_username_unique` still references it (pre-existing SQLite index-order defect, untouched by rebuild phases).

---

## 3. Identity schema (Phase 4)

### 3.1 users (extended)

Key columns: `id, name, email, phone_number, password, role (default 'customer', NOT mass-assignable), status (default 'active'), country_code (ISO3, indexed), is_active, last_login_at, kyc_status, kyc_tier, region_id, referral_code, referred_by, virtual_account_number, virtual_bank_name, transaction_pin, pin_locked_until, …`

- `role` is **never** in `$fillable` (privilege-escalation guard); assigned only via explicit audited paths.
- `country_code` enables country data isolation (`User::forCountry(ISO3)` scope).
- `kyc_status` (`unverified|pending|verified|rejected`) is a denormalized mirror of the latest `kyc_submissions.status`.

### 3.2 devices

| Column | Notes |
|---|---|
| id, user_id (FK cascade) | |
| device_id (unique, 64) | stable fingerprint = `hash(ip \| user_agent)` — never trusts a client-supplied device id |
| platform, browser, ip_address, user_agent | derived server-side |
| is_trusted, is_current | trust requires explicit action |
| last_seen_at | refreshed on each login |

### 3.3 kyc_submissions

| Column | Notes |
|---|---|
| id, user_id (FK cascade) | |
| type | personal \| business |
| status (indexed) | pending \| approved \| rejected \| expired \| manual_review |
| tier | tier1 \| tier2 \| tier3 |
| country_code | ISO3 at submission |
| data (json) | submitted identity payload |
| reviewer_id (FK), reviewed_at, rejection_reason | decision trail |
| submitted_at | aging/SLA derived from this |

Workflow enforced via `App\Services\KycWorkflow` (approve/reject/manual_review/expire) — always keeps submission + user mirror + audit in sync. **A decision is a risk indicator, never a "fraud" label, until formally reviewed.**

### 3.4 login_events

`id, user_id (nullable FK), event (login_success|login_failed|logout|lockout), ip_address, user_agent, device_id, meta (json), created_at`. Indexed `[user_id, created_at]`. **Never stores credentials** — failed attempts persist only an identifier for lockout forensics.

---

## 4. RBAC (Phase 4)

`role_permissions(role, permission)` — unique `[role, permission]`, seeded matrix in `000600` (+ admin view-permissions in `000800`):

- `superadmin` → `*` (wildcard via `Gate::before`).
- `admin` → transaction.*, agent.*, kyc.*, wallet.*, settlement.*, user.*, + `dashboard.view, audit.view, fx.view, network.view, revenue.view, ledger.view, security.view, settings.view, system.view`.
- `manager` → view + kyc review subset.
- `aggregator`, `agent`, `customer` → scoped operational perms.

Enforcement: `CheckPermission` middleware (`permission:{name}`) via `Gate` — routes carry `auth` + `role:superadmin` + `permission:…`. Frontend visibility is never the security boundary.

---

## 5. Audit trail contract

`audit_logs` canonical columns (after `000900`):

- `admin_id` (NOT NULL) — acting user
- `user_id` (NOT NULL) — primary target
- `target_id` (nullable) — secondary target
- `user_name` — denormalized actor name
- `action` — machine key (`kyc.approved`, `user.suspended`, …)
- `event_type` — compliance \| financial \| security \| operations \| system
- `description` — human-readable
- `metadata` (json, array-cast) — structured before/after context
- `payload` (json, array-cast) — legacy field, backward compatible

Single write path: `AuditLog::record(action, actorId, targetId, context)`.

---

## 6. Financial schema (Phases 2–3, summary)

- `ledger_accounts` — debit-normal accounts; `account_type` (asset/liability/equity/revenue/expense), `currency_code`, `is_system`; balance projection updated atomically (compare-and-decrement for reducing legs).
- `ledger_transactions` — immutable postings; unique idempotency_key; reference; type; related_transaction_id (reversals).
- `ledger_entries` — legs; side debit/credit; amount DECIMAL; FK transaction + account.
- `idempotency_keys` — raw key store for replay-safe money movements.
- `transaction_states` / `transaction_attempts` — state machine transitions (INITIATED→PROCESSING→AUTHORIZED→POSTED→SETTLED + FAILED/REVERSED/REFUNDED/HELD/CANCELLED/EXPIRED) and per-attempt records.
- `commission_rules` / `commission_entries` — rule-driven commissions (flat_amount preferred over rate; no hardcoded economics).
- `balance_snapshots` — point-in-time projections for reconciliation.
- `payment_providers`, `payment_rails`, `provider_configs`, `webhook_events` — provider abstraction (Phase 5).
- `countries` / `currencies` — market configuration (Phase 2).

---

## 7. Payment core (Phase 5)

### 7.1 Entry point and invariant

Every payment flows through `App\Domain\Payments\PaymentOrchestrator` — there is no other way to move money. The orchestrator is **ledger-sourced**: the internal `ledger` provider executes movements exclusively via `LedgerService::post` (balanced debits/credits, no negative balances, idempotent). No fabricated external APIs; no provider `execute()` may touch balances directly.

### 7.2 State machine (mandated chain)

`INITIATED → PROCESSING → AUTHORIZED → POSTED → SETTLED`; provider failure → `FAILED` with `error_reason`; terminal states never re-enter. Every transition (including genesis `recordGenesis`) is persisted to `transaction_states`; every provider call to `transaction_attempts`. Enforcement: `TransactionStateMachine::ALLOWED` map — any transition not in the map throws `IllegalStateTransitionException`.

### 7.3 Idempotency contract

- `Idempotency-Key` header (1–64 chars) is **mandatory** on `POST /api/v1/payments/{deposit,withdraw,transfer}` (422 if missing).
- `transactions.idempotency_key` is **UNIQUE**; `ledger_transactions.idempotency_key` enforces the same at ledger level.
- Replay returns the original transaction row unchanged — never re-executes.
- Concurrent same-key callers race on the UNIQUE constraint; losers adopt the winner's row (`UniqueConstraintViolationException` catch) — exactly one ledger posting.

### 7.4 Provider routing & rails

`payment_providers` (code, capabilities, supported countries/currencies, health_score) + `payment_rails` (WALLET_NG, WALLET_NE …) + `provider_configs`. Resolution: provider must support country + currency + requested rail and be available (live DB probe → `health()`). No matching provider → `ProviderUnavailableException` (503).

### 7.5 Webhook trust boundaries

- `WebhookService::ingestExternal` — HMAC verified, **fail closed**: no external providers registered yet, so unauthenticated webhooks → 401.
- `ingestInternal` — authenticated internal confirmations only; persists the event to `webhook_events` **before** processing; dedupe on unique `(provider, event_id)`; replay returns stored status, never double-settles.

### 7.6 API surface (live)

| Route | Auth | Notes |
|---|---|---|
| `POST /api/v1/payments/deposit` | `auth:sanctum` + `throttle:api` | Idempotency-Key required |
| `POST /api/v1/payments/withdraw` | same | wallet → platform cash |
| `POST /api/v1/payments/transfer` | same | DR sender / CR receiver |
| `GET /api/v1/payments/{reference}` | same | sender/receiver ownership → 403 otherwise |
| `POST /api/v1/webhooks/{provider}` | signature = auth | fail-closed |

Error mapping: 422 invalid request/amount/country/currency, 503 provider unavailable, 401 bad webhook signature, 403/404 status endpoint. JSON errors only — no stack traces.

### 7.7 Custodial ledger rails

`Platform Cash` = asset account named `Platform Cash`, currency-specific; wallet = liability account `owner_type=user, owner_id, currency_code`. Movements: deposit DR cash/CR wallet; withdraw DR wallet/CR cash; transfer DR sender/CR receiver. Missing account → `ProviderExecutionException` (fail loudly, never fabricate).

---

## 8. Agency banking (Phase 6)

### 8.1 Entities

- `agents` — `user_id` (unique FK), `agent_code` (unique, `AGT-…`), `status` (pending|active|suspended|inactive|terminated), `tier` (bronze|silver|gold), `country_iso2`, `region`, `city`, `aggregator_id`, `kyc_status`, `risk_score` (Phase 7 fills this), `commission_override_rate`.
- `aggregators` — `user_id` (nullable), `code` (unique, `AGG-…`), `name`, `status`, `country_iso2`, `region`, `city`, `kyc_status`, `commission_override_rate`. An agent belongs to one aggregator.
- `agency_operations` — the auditable record of every agent cash-in / cash-out: `agent_id`, `aggregator_id` (denormalized), `customer_user_id`, `operation_type` (cash_in|cash_out), `currency_code`, `amount`, `fee`, `commission_amount`, `status` (posted|failed), `reference` (unique), `idempotency_key` (unique). The metrics source for Stage 3 liquidity and Stage 4 network intelligence.

### 8.2 Money movement (ledger-sourced, custodial)

Agent floats and aggregator floats are **LIABILITY** accounts (`owner_type` = `agent` / `aggregator`), exactly like customer wallets. `AgencyService` is the only entry point:

- **cash-in** (customer hands cash to agent) → DR agent float / CR customer wallet.
- **cash-out** → DR customer wallet / CR agent float.

Both are idempotent (`agency_operations.idempotency_key` UNIQUE + ledger-level `LEDGER:<key>` idempotency), atomic (DB transaction; a failure rolls back the postings and records a `failed` operation row), and audited (`audit_logs` `agency.cash_in` / `agency.cash_out`). Missing float/customer-wallet accounts fail loudly — never fabricated.

### 8.3 Commission engine

`commission_rules` are **data, never code** (000500): matched by country / transaction_type / channel / agent_tier / amount band, lowest `priority` wins, `flat_amount` preferred over `rate`. `CommissionEngine::accrue` posts through the ledger:

- DR Commission Expense (expense, system, per currency) / CR Agent Commission Accrual (liability, owner agent)
- records `commission_entries` (status `accrued`) + `commission_accruals`; payout is a later step (`accrued → paid → reversed`).

### 8.4 Lifecycle & RBAC

Status transitions (activate/suspend/reactivate/terminate) are guarded (illegal transitions throw `DomainException`) and each writes an `audit_logs` row (`agent.registered/activated/suspended/reactivated/terminated`, `aggregator.registered/activated`, `agent.assigned.aggregator`). Role assignment (`agent` / `aggregator`) is explicit + audited — never mass-assignable. Admin permissions extended in 001200: `agency.view`, `agency.manage`, `agent.approve`, `agent.suspend`, `agent.reactivate`, `aggregator.approve`, `aggregator.manage`, `commission.manage`; manager gets `agency.view`, `agent.approve`.

---

## 9. Risk layer (Phase 7)

### 9.1 Rules are data, alerts are indicators

- `risk_rules` — code, category (`fraud|aml|velocity|geographic|anomaly`), entity_type (`transaction|agent|customer|aggregator|provider`), condition_type + JSON condition_config, severity (`P0–P3`), risk_score contribution, priority, optional country + dedupe window.
  Condition schema (evaluated by `RiskService::matches`, never per-rule code):
  `amount_exceeds {amount}`, `failed_attempts_exceed {count}`, `velocity_count_exceeds {count}`, `success_rate_below {rate}`.
- `risk_alerts` — one row per detection: reference (`ALRT-…`), rule, severity, entity, transaction link, country, message, matched facts (`details`), risk_score, status (`open|acknowledged|investigating|resolved|false_positive`). **An alert is a risk indicator, never a fraud label**, until formally reviewed (§31).
- Dedupe: per transaction+rule always; otherwise within `dedupe_window_minutes` or while unresolved.
- `RiskService::scoreEntity` projects a 0–100 score (severity-weighted: P0=40, P1=25, P2=15, P3=5, capped at 100) into `agents.risk_score` / `users.risk_score` (001300 additive column).

### 9.2 Transaction holds (state machine)

`transaction_holds` (unique per transaction) records reason, reason_code, SLA (`sla_due_at`), and the decision trail. The state machine stays authoritative: `hold` = transition to `HELD` (legal from PROCESSING/AUTHORIZED/POSTED); `release` = `HELD → POSTED`; `reject` = `HELD → CANCELLED`. Illegal hold states throw `IllegalStateTransitionException`; every hold/release/reject writes an `audit_logs` row.

### 9.3 Maker–checker approvals

`approval_requests` — reference (`APR-…`), maker_id, action_type (e.g. `commission.change`, `settlement.approve`, `risk.release`, `limit.change`), entity, payload (before/after), reason, status (`pending|approved|rejected`), decided_by/at/note, SLA. `ApprovalService` enforces **server-side**:

- the maker can never approve their own request (`DomainException`),
- a request can be decided exactly once,
- `inboxFor(user)` = pending requests NOT made by that user (the "REQUESTS REQUIRING MY APPROVAL" rail) vs `mine(user)` (the "MY PENDING REQUESTS" rail).

Side effects of an approved request are executed by the caller after a decision — Phase 8/9 wire settlement, adjustments and commission changes to this inbox.

---

## 10. Reconciliation & settlement (Phase 8)

### 10.1 Settlement

- `settlements` — batch record of money the platform moves via a provider/rail: reference (`STL-…`), provider_code, rail_code, country_iso2, currency_code, amount, settled_amount, provider_reference, status (`scheduled|pending|processing|settled|failed|cancelled`), scheduled_at (the "Next Settlement" KPI), settled_at, period bounds, created_by.
- `settlement_items` — the transactions composing a settlement (unique per settlement+transaction; `pending|included|excluded|reversed`). Settlement exposure = Σ amounts of non-settled settlements per scope.
- **Accrual accounting through the ledger** (`SettlementService`):
  - `schedule()` → DR Settlement Expense / CR Settlement Payable (recognizes the liability; payable is never debited below zero).
  - `settle(…, postLedger: true)` → DR Settlement Payable / CR Platform Cash. Missing Platform Cash fails loudly.
  - Every transition is guarded (illegal transitions throw `DomainException`) and audited (`settlement.scheduled/pending/processing/settled/failed/cancelled`). Provider owners on ledger accounts use `crc32(provider_code)` (owner_id is BIGINT).

### 10.2 Reconciliation

- `reconciliation_runs` — one run per period, optionally scoped (provider/country/currency): reference (`REC-…`), counts (internal, provider, matched, unmatched_internal, unmatched_provider, amount_mismatch, duplicate), internal/provider amounts, difference, **health_score (0–100)** — `round(100·matched/internal − 2·mismatch − duplicates − unmatched_provider, 2)` clamped to [0,100], status, timestamps.
- `reconciliation_items` — item-level evidence per run: match_key (provider_reference), status (`matched|unmatched_internal|unmatched_provider|amount_mismatch|duplicate`), internal/provider amounts, discrepancy, and resolution (`accepted|rejected|adjusted` + resolver/note, audited).
- Matching is against **real persisted records only**: internal = `transactions.provider_reference`; provider = `transaction_attempts.provider_reference` (attempts now carry `amount`, additive 001400; legacy rows fall back to the transaction's amount). Nothing is invented.
- `reconciliationHealth()` — latest completed run's health + open-exception count (the Command Center "Reconciliation Health" KPI).

### 10.3 Balance-snapshot comparison

`balance_snapshots` (000500) stores the frozen comparison; `ReconciliationService::takeBalanceSnapshot()` computes it: derived = Σ ledger_entries (debit-normal for asset/expense, credit-normal for liability/equity/income) vs projected = `ledger_accounts.balance`; `MATCHED` when difference is 0, else `MISMATCH`. This is the operational guard against direct balance mutation ("no Edit Balance" rule).

---

## 11. Conventions for new work

- New financial columns: `DECIMAL(18,2)` (NGN-style) or integer minor units for XOF; never FLOAT.
- Every new table: `timestamps()`, explicit indexes for the queries the dashboard runs (metrics queries aggregate — see Phase 9 metrics tables).
- Rollback smoke is mandatory: `php artisan migrate:rollback --step=N --force` on a scratch SQLite DB.
- Never mutate a migration that already ran on a deployed DB — ship an additive corrective migration (`YYYY_MM_DD_HHMMSS_…`).
