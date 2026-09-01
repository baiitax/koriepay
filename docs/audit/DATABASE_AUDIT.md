# KoriePay — Database Audit (DATABASE_AUDIT.md)

> Phase 1 deliverable · 2026-08-31
> Sources: `database/migrations/*` and the committed `koriepay_lara878.sql`
> (phpMyAdmin export of the `koriepay_lara878` MariaDB database).

---

## 1. Existing tables (26) and health

| Table | Purpose | Issues |
|---|---|---|
| users | accounts, role, KYC, virtual account, PIN lockout, referral | role = string; PIN = bcrypt(4-digit); PII present in public dump |
| wallets | per-user per-currency balance + commission | **balance = source of truth (anti-pattern)**; no ledger link |
| transactions | single-row record of all money movement | no debit/credit entries; `auth_code` plaintext column; type mismatch across writers |
| bank_nodes | settlement bank accounts + balance | account numbers in dump; balance mutated directly |
| fx_rates | FX table | **schema/code mismatch** (see 3.2); table EMPTY in dump |
| revenue_logs | platform revenue | `amount_usd` float; no link to transactions |
| treasury_vaults | platform liquidity | — |
| linked_vaults | user↔treasury links | — |
| adashi_groups/members/cycles | group savings | engine writes nonexistent `user_id` column |
| audit_logs | admin/agent action trail | metadata free-text; no before/after structured diff; deletable |
| support_tickets | support | minimal |
| notifications | Laravel notifications | fine |
| settings | platform config (siteName, maxTransactionLimit, platformFee) | values untyped; fee as text |
| sessions/jobs/cache/failed_jobs/password_reset_tokens | framework | fine |

## 2. Money-precision audit

- Money columns are `decimal` in the dump schema (good): `transactions.source_amount decimal(15,2)`, `fee_charged decimal(20,2)`, `wallets.balance` (default decimal in migration), `bank_nodes.balance decimal(20,2)`.
- **BUT** application code casts to `(float)` everywhere: `SendLiquidity`, `WithdrawVault`, `CrossBorder`, `FxService`, `SettlementEngine` all do `(float)` arithmetic on amounts before writing → float drift risk (e.g., 0.1+0.2). Violates "never use floats for money".
- `revenue_logs.amount_usd decimal(15,2)` is a single-currency approximation — insufficient for multi-currency revenue (NGN/XOF).
- `fx_rates.mid_market_rate decimal(18,6)` — adequate precision; `corporate_spread`/`volatility_buffer decimal(5,2)` are percentages, fine.

**Decision (Phase 2):** keep `decimal` columns; introduce a `Money` value object
(minor units as integers via `brick/money`) at the service boundary; DB stays
decimal with explicit scale (NGN/XOF both use 2 dp minor units).

## 3. Structural problems

### 3.1 No ledger (single-entry everything)
Every flow writes one `transactions` row and mutates `balance` fields directly.
There is no `ledger_accounts`, no `ledger_entries`, no debit/credit double-entry.
Consequences: no way to prove `Σ debits = Σ credits`; reversals are edits
(not opposite postings); reconciliation is impossible.

### 3.2 FX schema vs code mismatch (verified)
- Migration `2026_03_29_054952_create_sovereign_grid_tables.php` creates
  `fx_rates(pair, mid_market_rate, corporate_spread, volatility_buffer, effective_rate, status)`.
- `App\Models\FxRate` `$fillable = [pair, mid_market_rate, corporate_spread, volatility_buffer, effective_rate, status]` and casts `rate`/`is_active` (nonexistent).
- `Services/FxService` queries `where('base_currency',…)->where('target_currency',…)` (nonexistent columns).
- `Agent/CrossBorder` reads `FxRate::where('pair','NGN/XOF')->first()` then `(float)$rateRecord->rate` (nonexistent) → returns 0 if a row exists, else fallback 0.55.
- `SettlementEngine` reads `FxRate::where('pair',$pair)` then `$rate->rate`.
- `fx_rates` table in the dump contains **zero rows**.

### 3.3 Transactions table can't express the domain
Single table mixes p2p, deposit, withdrawal, cash-in/out, FX, adashi with
inconsistent writers; `sender_id/receiver_id` both user ids (no wallet/account
ids); `receiver_name` duplicated; no `transaction_type` normalization; no
`idempotency_key`; no `state` machine table; no `attempts`; no `provider_ref`.

### 3.4 users table is overgrown
~40 columns (role/status/kyc/region/device-ish/referral/biometric booleans).
Separation of concerns lost; KYC docs paths (`id_document_path`, `utility_bill_path`)
live on users instead of a `kyc_documents` table.

### 3.5 Referential integrity & indexes
- Dump schema has FK-less InnoDB tables (e.g., `transactions.sender_id` has no FK to `users.id`; `adashi_*` FKs absent). Orphan risk confirmed: faker-generated users (ids 8–42) exist alongside transactions referencing missing ids (tx 6 references sender 13 which exists, but receiver ids are null/empty).
- No indexes on `transactions(reference)`, `transactions(status,created_at)`, `wallets(user_id,currency_code)` composite, `adashi_members(group_id)` — the exact hot paths.

### 3.6 Adashi orphan writes
`AdashiSettlementEngine` creates transactions with `user_id`/`amount`/`currency`
fields that are not in the schema → silently dropped by `$fillable`; payout
transactions lose their user linkage.

## 4. Legacy → target mapping (Phase 2)

| Legacy | Target |
|---|---|
| users | `users` (identity) + `customers`/`agents`/`aggregators` profiles + `kyc_profiles` |
| wallets.balance | `ledger_accounts` (authoritative) + `wallet_accounts`/`balance_snapshots` (projection) |
| transactions | `transactions` (request/state machine) + `transaction_attempts` + `ledger_transactions` + `ledger_entries` (double-entry) |
| bank_nodes | `payment_rails` + `providers` + `provider_accounts` + `settlement_accounts` |
| fx_rates | `fx_rates` (corrected schema) + `fx_quotes` + `fx_locks` |
| revenue_logs | `commissions` + `commission_entries` + `revenue` (per-currency) |
| adashi_* | preserve, FK-fix, ledger-wired payouts |
| audit_logs | keep + add `before/after/reason` JSON + immutability flag |
| — | NEW: `countries`, `currencies`, `payment_rails`, `providers`, `webhook_events`, `idempotency_keys`, `transaction_states`, `risk_*`, `reconciliation*`, `disputes`, `devices`, `sessions`(already) |

## 5. Migration strategy (Phase 2)

1. **Backup:** dump current schema + data (documented, encrypted, off-repo).
2. **Preserve IDs:** users/transactions keep existing PKs where practical; add
   `legacy_reference` columns where mapping changes.
3. **Add new financial tables** in additive migrations (no destructive drops).
4. **Backfill ledger:** create opening `ledger_accounts` per wallet with an
   opening balance entry derived from current `wallets.balance` (flagged
   `opening_balance`), then mark old `transactions` as legacy (not re-posted).
5. **Switch writers** flow-by-flow (Phase 3+), keeping legacy reads working via
   compatibility accessors until cutover.
6. **Reconcile:** `ledger_accounts.balance == wallets.balance` job; mismatch →
   critical alert (Phase 8 engine).
7. Only after all writers are migrated, drop legacy columns (never financial rows).

## 6. Required new schema (summary)

`countries, currencies, payment_rails, providers, provider_accounts,
ledger_accounts, ledger_transactions, ledger_entries, ledger_postings,
balance_snapshots, idempotency_keys, transaction_states, transaction_attempts,
webhook_events, commissions, commission_rules, commission_entries,
kyc_profiles, kyc_documents, kyc_checks, risk_profiles, risk_rules,
risk_events, risk_alerts, settlements, reconciliations, reconciliation_items,
devices, device_sessions, disputes, transaction_cases, daily_transaction_metrics,
agent_metrics, aggregator_metrics, country_metrics, system_events` — implemented
in Phase 2 migration files (see `database/migrations/2026_08_31_*`).
