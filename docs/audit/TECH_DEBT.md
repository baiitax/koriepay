# KoriePay — Technical Debt Register (TECH_DEBT.md)

> Phase 1 deliverable · 2026-08-31
> Each item: severity (Blocker/Major/Minor/Nice), effort (S/M/L/XL), phase.

---

## Blockers (financial integrity)

| ID | Debt | Evidence | Effort | Phase |
|---|---|---|---|---|
| B-1 | No immutable ledger; balances mutated directly everywhere | FINANCIAL_FLOW_AUDIT A1 | XL | 3 |
| B-2 | Cash-out double-debit duplicate block | `Agent/CashOut::authorizeDispense` inner try | S | 6 |
| B-3 | Withdrawal completes with no provider call | `WithdrawVault::processWithdrawal` | M | 5 |
| B-4 | Card deposit is a mock ("API Pending") | `FundVault::initiateCardPayment` | M | 5 |
| B-5 | FX rates fabricated (`mt_rand`) and FX schema/code mismatch | `FetchLiveFxRates`; DATABASE_AUDIT 3.2 | M | 5 |
| B-6 | Settlement job arity mismatch → ArgumentCountError; refund unimplemented | `ProcessCrossBorderTransfer` vs `SettlementEngine::execute` | S | 5 |
| B-7 | Plaintext + predictable cash-out OTP, `!=` compare, no expiry | `Agent/CashOut::requestAuthorization` | S | 6 |

## Major

| ID | Debt | Evidence | Effort | Phase |
|---|---|---|---|---|
| M-1 | Secrets in git history (recovery codes, SQL dump) — owner purge required | SECURITY_AUDIT C-1/C-2 | S | 0 |
| M-2 | Role = string column; no permissions; spatie unused | `users.role`, `CheckAdmin` | M | 4 |
| M-3 | `env()` in controllers; missing `services.php` entries for smileid/paystack | controllers | S | 4 |
| M-4 | No test coverage for any financial logic (stock Breeze tests only) | `tests/` | XL | 3+ |
| M-5 | Money floats throughout (`(float)` casts) | all flows | M | 3 |
| M-6 | No idempotency table; cache-lock anti-pattern in SendLiquidity | F1 P3 | M | 3 |
| M-7 | Error messages leak internals to users | `SendLiquidity`, `WithdrawVault` | S | 3 |
| M-8 | Adashi engine writes nonexistent columns; no rerun safety | `AdashiSettlementEngine` | M | 6 |
| M-9 | Hardcoded fees/rates/commissions in 4+ places | F1,F3,F5,F6 | M | 6/20 |
| M-10 | No security headers (fixed in working tree; deploy needed) | live probe | S | 0 |
| M-11 | `app.koriepay.com` directory listing (host config) | live probe | S | 0 |
| M-12 | Limits via `whereDate` on DB tz; sum skips fee; races on `firstOrCreate` | `SendLiquidity::validateLimits` | M | 3 |
| M-13 | README describes architecture that doesn't exist (Next.js/Flutter/PG/Redis) | README vs tree | S | 1 |

## Minor

| # | Debt | Evidence | Effort | Phase |
|---|---|---|---|---|
| n-1 | Model/schema drift: `pin_hash` vs `transaction_pin`, `rate` vs `mid_market_rate` | `User`, `FxRate` | S | 2 |
| n-2 | `AuditLog::forceCreate` bypasses guards; metadata free-text | CashIn/CashOut | S | 4 |
| n-3 | `transactions.auth_code` plaintext column | schema | S | 6 |
| n-4 | `GET /clear-cache` removed; old caches may rely on it in ops | — | S | 0 |
| n-5 | Breeze scaffold leftovers (`welcome.blade.php`, profile pages) | views | S | 9 |
| n-6 | No structured logging / request IDs | — | M | 11 |
| n-7 | No health endpoint beyond `/up`; `/up` returns 200 for db-down | bootstrap | M | 11 |
| n-8 | `down()` in `sovereign_grid_tables` migration drops wrong name | migration | S | 2 |
| n-9 | Single commit history — no traceability | git log | S | 0 |
| n-10 | Duplicate `enable_2fa`/`enable_biometrics` columns without functionality | schema | M | 4 |

## Nice-to-have

| # | Debt | Evidence | Effort | Phase |
|---|---|---|---|---|
| N-1 | Host_Grotesk.zip (895 KB) committed; serve via CDN | repo root | S | 9 |
| N-2 | `phpunit.xml` fine; add parallel test config | — | S | 3 |
| N-3 | Admin dashboards query live tables per request (no aggregation tables) | admin components | XL | 10 |
| N-4 | No i18n beyond locale switch; UI strings hardcoded in Blade | views | L | 10 |

---

## Disposition

- **Phase 0 done:** B-1 note (audit), M-1 (tree-level), M-10, M-11 (tree-level),
  M-13 (README rewrite in progress), n-4.
- **Phase 2/3 (this build):** B-1 foundation (ledger engine + tables), M-4
  (ledger/idempotency/state-machine/concurrency tests), M-5, M-6, M-7, M-12,
  n-1, n-2 (partial), n-8.
- **Phase 4+:** B-2, B-3, B-4, B-5, B-6, B-7, M-2, M-3, M-8, M-9, n-3, n-10…
- **Phase 11:** n-6, n-7.
- **Retire:** none deleted — all preserved per "never lose financial history".
