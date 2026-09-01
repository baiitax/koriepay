# KoriePay — Architecture Audit (ARCHITECTURE.md)

> Phase 1 deliverable · 2026-08-31 · Base commit `6a403eb` (main)
> Ground rule: the repository, not the README, is the source of truth.

---

## 1. What the product actually is

KoriePay is a **Laravel 12 monolith** (Blade + Livewire 3 + Volt) implementing a
West-African cross-border wallet & agency-banking platform (NGN ↔ XOF). It
powers the public marketing site at koriepay.com **and** the application
portals from the same codebase.

There is no Next.js admin, no Flutter client, no PostgreSQL, no Redis in this
repository. The README describes an aspirational polyglot monorepo that does
not exist here. Verified by directory listing and dependency inspection.

## 2. Runtime topology (verified)

```
Browser (public site + portals)
        │  HTTPS (LiteSpeed at the edge for koriepay.com)
        ▼
Laravel 12.53 monolith  ── SQLite/MySQL (DB_CONNECTION=sqlite in .env.example)
   ├─ Livewire 3 components (5 portal groups)
   ├─ Volt routes (auth)
   ├─ Reverb websockets (installed; BROADCAST_CONNECTION=log by default)
   ├─ Queue: database driver (jobs for settlement/notifications)
   └─ External: Paystack (resolve/funding webhooks), Smile ID (KYC), DusuPay (aspired)
```

## 3. Portal / role map (routes/web.php verified)

| Portal | Prefix | Role gate | Components |
|---|---|---|---|
| Customer | `/customer` | `role:customer` | Dashboard, SendLiquidity, CashHub, FundVault, WithdrawVault, History, Beneficiaries, KycVerification, Adashi*, Bills, Cards, Referrals, Security, Support |
| Agent | `/agent` | `role:agent` | Dashboard, CashIn, CashOut, Ledger, CommissionDashboard, CrossBorder, Transfer, Settings, FundWallet |
| Regional agent | `/regional` | `role:regional_agent` | Dashboard, CaptureAgent, KycPipeline |
| Manager | `/manager` | `role:manager` | Dashboard, Agents, Kyc, Ledger, Treasury, Risk, Compliance, Forecaster, AuditLogs |
| Super admin | `/admin` | `role:superadmin` | Dashboard, TransactionLedger, TreasuryVault, MasterLedger, FxRates, KycHub, KycQueue, RevenueLedger, SettlementDashboard, Network, NodeManager, SystemSettings, Security |
| Public | `/` + sections | guest | Home, pricing, solutions, developers, trust, company, support |

**Critical routing issue:** the `role` alias maps to `CheckAdmin`, whose
implementation checks `auth()->user()->role === $role || === 'superadmin'`.
Role enforcement therefore relies on a **string column** on users, with no
permission granularity (see SECURITY_AUDIT.md M-5 / PHASE 4 plan).

## 4. Domain model inventory (from migrations + SQL dump)

```
users (role/status/kyc_status/region_id, virtual_account, referral fields,
       failed_pin_attempts, pin_locked_until, device-ish fields)
wallets (user_id, currency_code, balance, commission_balance, is_primary)
transactions (sender_id, receiver_id, source/dest currency+amount,
              exchange_rate, fee_charged, status, auth_code, reference)
bank_nodes · fx_rates · revenue_logs          (sovereign grid)
treasury_vaults · linked_vaults · settings
adashi_groups · adashi_members · adashi_cycles
audit_logs · support_tickets · notifications
sessions · jobs · cache · failed_jobs · password_reset_tokens
```

**Missing (required by the rebuild):** ledger_accounts, ledger_transactions,
ledger_entries, idempotency_keys, transaction_states, countries, currencies,
payment_rails, providers, webhook_events, commissions, reconciliation,
risk tables. → Phase 2/3 adds these.

## 5. Money flows (all verified by reading the source)

| Flow | Component | Mechanism today | Correct? |
|---|---|---|---|
| P2P transfer | `Customer/SendLiquidity::processTransfer` | Direct `balance` decrement/increment; single `transactions` row; `status=completed` immediately; 10s cache lock keyed by amount | ❌ single-entry, no provider, no reversal, weak idempotency |
| Deposit (card) | `Customer/FundVault::initiateCardPayment` | **Mock only** — flashes "Gateway initialized … (API Pending)"; no provider call | ❌ non-functional |
| Withdrawal | `Customer/WithdrawVault::processWithdrawal` | Decrements wallet, writes `completed` row — **no payout call to any provider** | ❌ money leaves ledger with nothing executed |
| Agent cash-in | `Agent/CashIn::processCashIn` | Agent float decrement ↔ customer wallet increment | ⚠️ correct direction, no cash/float reconciliation |
| Agent cash-out | `Agent/CashOut::authorizeDispense` | **DOUBLE-SETTLEMENT BUG**: customer debited twice, agent credited twice (outer + inner block). Auth code stored **plaintext** in `transactions.auth_code` | ❌ critical accounting bug |
| Agent cross-border | `Agent/CrossBorder::executeSettlement` | Agent NGN float → receiver XOF wallet at hardcoded-fallback rate; fee 3% hardcoded | ❌ no provider, hardcoded rate, FX schema mismatch |
| Adashi settlement | `Console/AdashiSettlementEngine` | Contribution deduction + payout to recipient; default → `AdashiRescueJob` | ⚠️ writes `user_id` column that doesn't exist (silently dropped), no ledger |
| FX rates | `Console/FetchLiveFxRates` | **Fabricated** `mt_rand(400,450)/1000` "live" rate cached 20 min | ❌ simulation in production |
| Settlement | `Services/SettlementEngine` + `Jobs/ProcessCrossBorderTransfer` | Job calls engine with **wrong arity** (5 args vs 6 params) → ArgumentCountError; refund path commented out | ❌ dead code |

**Architectural conclusion:** every balance field (`wallets.balance`,
`wallets.commission_balance`, `bank_nodes.balance`) is the source of truth,
mutated directly and non-atomically across flows. This violates the rebuild's
core principle — balances must be projections of an immutable ledger.

## 6. Strengths worth preserving

- Clean Livewire component organization (5 portals) — keep the structure.
- `lockForUpdate()` + sorted node locking in `SettlementEngine` (deadlock-safe pattern) — keep as reference.
- Login rate limiting (5/min per email+IP) via `RateLimiter` — keep.
- PIN lockout (3 failures → 24h) — keep, add pepper (see SECURITY_AUDIT M-3).
- Route-model binding on `transactions.reference` + ownership check on receipts — keep.
- Correct Paystack HMAC verification pattern in `PaystackWebhookController` — keep as template.
- Domain concepts (Adashi cycles, bank nodes, treasury vaults) — preserve models.

## 7. Target architecture (Phase 2+)

See `docs/engineering/ARCHITECTURE.md` (engineering deliverable) for the full
target: domain modules (`app/Domain/Accounting|Identity|Payments|…`), immutable
double-entry ledger, idempotency layer, state machine, payment-provider
abstraction, reconciliation engine, and observability.

## 8. Action map

| # | Gap | Phase |
|---|---|---|
| 1 | Secrets in history (recovery codes, SQL dump) | 0 (done in working tree; history purge is owner action) |
| 2 | `/clear-cache` public route | 0 (removed) |
| 3 | Security headers | 0 (middleware added) |
| 4 | Fail-closed webhooks | 0 (done) |
| 5 | Ledger tables + double-entry engine | 2–3 |
| 6 | Idempotency table + state machine | 3 |
| 7 | Country/currency/rail/provider config | 2 |
| 8 | RBAC permissions | 4 |
| 9 | Payment orchestration + real providers | 5 |
| 10 | Reconciliation engine | 8 |
