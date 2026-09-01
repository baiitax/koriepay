# KoriePay Customer Banking App — Current State & Gap

> Engineering deliverable — governed by the **KoriePay Customer Banking App** brief (136 points).
> Status: **Stages 1–5 COMPLETE ✅ (2026-08-31).** Stage 6 (Intelligence) and Stage 7 (Low-bandwidth/offline) remain.
> Scope: Niger (XOF/CFA) + Nigeria (NGN/₦), mobile-first, built on the Phases 2–8 data layer.
> Full suite: **214 tests / 814 assertions green**
> (`CustomerBankingStage1Test` 21, `CustomerTransferStage2Test` 20, `ExchangeExecutionStage3Test` 12,
> `CustomerTransactionStage4Test` 12, `CustomerProfileStage5Test` 17, plus the pre-existing Phase suite).

---

## 1. Current state (audit, 2026-08-31)

### 1.1 Routes (existing, customer group in `routes/web.php`)
`customer.dashboard`, `customer.fund`, `customer.send`, `customer.withdraw`, `customer.settings`,
`customer.history`, `customer.cash-hub`, `customer.vaults`, `customer.contacts`, `customer.bills/{category?}`,
`customer.cards`, `customer.referrals`, `customer.adashi.*`, `customer.kyc`, `customer.profile`,
`customer.support`, `customer.security`, plus `transaction.receipt` (ownership-checked).

**Stage 1 added the `/api/v1/customer/*` API surface** (brief §89): dashboard / wallets / wallet balance / wallet select / exchange quote — all `auth:sanctum`. The web routes remain Livewire-driven; the legacy 22 components still read `wallets.balance` on their old routes until rewired in later stages.

### 1.2 Components (22 Livewire customer components)
Dashboard, FundVault, SendLiquidity, WithdrawVault, History, CashHub, LinkedVaults, Beneficiaries,
BillPayment, Cards, Referrals, Adashi (dashboard/create/join/manage/ledger), KycVerification, Profile,
AgentSupport, Security, SecuritySettings, Settings, Receipt, TransactionReceipt, FindAgent, PersonalLedger.

### 1.3 Critical gaps vs the brief

| # | Brief requirement | Current state | Gap |
|---|---|---|---|
| §82/§10 | Ledger is the source of truth; double wallets = separate currency/ledger balance | `Dashboard` provisions `App\Models\Wallet` rows (legacy `wallets` table, 2023) with a **generic `balance` field**, one per currency, for **every** user | **VIOLATION** — balances not ledger-sourced; no `pending`; NGN+XOF forced for all countries |
| §75 | Backend decides which wallets a customer may hold | `ensureWalletsProvisioned()` creates NGN + XOF for everyone | **VIOLATION** — no country/KYC eligibility |
| §89–92 | `/api/v1/customer/*` optimized dashboard + quote + execute APIs | None | **MISSING** |
| §39–41 | Authoritative backend exchange quotes with expiry | No exchange service at all (legacy `SendLiquidity` is vault-to-vault) | **MISSING** |
| §13 | Balance visibility toggle, consistent hiding | None | **MISSING** |
| §2–3 | Mobile-first + floating glass bottom nav (Home/Pay/Transactions/Wallets/Me) | `layouts/customer.blade.php` is desktop-first, loads **Tailwind CDN** (not the design-system `app.css`), no bottom nav | **MISSING** |
| §87–88 | IDOR-safe wallet/transaction access | Receipt route is ownership-checked; wallet list is user-scoped | Partially OK; new API must enforce |
| §95/§94 | Honest empty/loading states; no mock balances | Legacy dashboard shows real (but non-ledger) balances | Fix via ledger read model |

### 1.4 Reusable foundation (do NOT duplicate)
- **Ledger** (`App\Domain\Accounting\LedgerService`, `LedgerAccount`) — Phases 2–3, 132 tests green. Customer wallets must map to liability ledger accounts.
- **Design system** `resources/views/components/kp/*` + `resources/css/app.css` (glass/panel/tone/skeleton utilities, brand teal `#158987`, semantic status colors) — from Command Center Stage 1. Reuse; extend mobile-first in the customer shell.
- **`fx_rates`** (base_currency/target_currency/rate/is_active, additive 000600) + `FxRate` model/observer — authoritative rate source for exchange quotes.
- **Identity/RBAC** — `users.country_code` (ISO3), `kyc_status/kyc_tier`, `countries` (iso2/iso3/currency_code/calling_code), `currencies` (minor_units) — country-aware eligibility config source.
- **Payment core** (Phases 5–8): `PaymentOrchestrator`, `Transaction` state machine, `transactions` (+ provider/rail/country), `transaction_attempts` — the transaction history + pending derivation source.
- **Sanctum** + `throttle:api` + `HasApiTokens` — auth for the new customer API.

---

## 2. Target architecture (Stage 1)

```text
LEDGER (source of truth)
   │  ledger_accounts (liability, owner user+currency)
   ▼
customer_wallets  (read model: wallet_id, currency, is_primary, status, limits; FK ledger_account)
   ▼
CustomerWalletService  →  balance available = ledger projection, pending = real in-flight txs
ExchangeQuoteService   →  authoritative fx_rates + config fee/spread → exchange_quotes (bound, expiring)
   ▼
/api/v1/customer/*  (auth:sanctum, ownership-checked, optimized dashboard read model)
   ▼
Customer app shell  (mobile-first glass, bottom nav, wallet switcher, balance hiding)
```

### Stage 1 deliverables (this phase) — ✅ DONE
1. **Migration `001500`** — `customer_wallets` (read model), `customer_wallet_configs` (country/currency/KYC eligibility — data-driven, §75), `exchange_quotes` (server-authoritative, expiring, §39/§91). Ran + rollback/forward smoke-verified on dev sqlite.
2. **Services** — `CustomerWalletService` (provision country-aware, list, balance details available/pending/total, selected-wallet session, masked phone, honest partial portfolio), `ExchangeQuoteService` (pair eligibility, KYC check, limits, authoritative rate, fee flat+%, 60s expiry; create + revalidate/expire/markUsed).
3. **API** — `GET /api/v1/customer/dashboard` (optimized read model §90), `GET /api/v1/customer/wallets`, `GET /api/v1/customer/wallets/{wallet}`, `GET /api/v1/customer/wallets/{wallet}/balance`, `POST /api/v1/customer/wallets/{wallet}/select`, `POST /api/v1/customer/exchange/quote`. All `auth:sanctum` + `throttle:api`, ownership-scoped, IDOR-safe.
4. **Customer shell** — `layouts/customer.blade.php` rebuilt mobile-first (floating glass bottom nav Home/Pay/History/Wallets/Me, desktop sidebar ≥1024px, safe-area insets), kp design system only (no Tailwind CDN), system font stack, Alpine toast listener.
5. **Dashboard rewrite** — `App\Livewire\Customer\Dashboard` rewritten on `CustomerWalletService`: ledger-backed balance hero, wallet switcher (session), balance hide toggle, clearly-labelled portfolio estimate (or honest "unavailable"), quick actions, recent activity, honest eligibility state when KYC blocks wallets, KYC nudge. `PayHub` placeholder for Stage 2.
6. **Tests** — `CustomerBankingStage1Test` (21 tests): country-aware provisioning, KYC gating, ledger-sourced balances (asserts `customer_wallets` has no balance column), pending derivation, authoritative+expiring quotes, limit enforcement, zero-minor-currency (XOF) amount format, quote double-use/expiry, IDOR 404s, masked phone, API shape, shell routes 200.
7. **Dev data** — `FxRatesSeeder` (XOF↔NGN, XOF↔USD, NGN↔USD authoritative dev rates) + `CustomerBankingSeeder` (enables NE→NGN + NG→XOF secondary configs simulating ops, funds demo.ne/demo.ng ledger accounts).
8. **Fixes landed** — `Money::fromDecimal` XOF zero-minor already handled; `guardAmountFormat` zero-minor regex crash fixed; `selectedWallet` now null-safe (honest no-wallet state); `portfolioSummary` partial-degradation + primary-currency default; pre-existing typo `];`→`};` in `kp/alert-card` match block (was a latent 500 for any view using it); `DashboardController` no longer references missing `PaymentProvider` model; legacy auth test user gets `kyc_tier`.

### Stage 2 deliverables (per brief §128 send/receive journeys) — ✅ DONE
1. **Migration `001600`** — `users.koriepay_id` (public receive identity, backfilled `KP-<base36>`, + `User::creating` hook so every new user gets one) and `customer_wallet_configs.transfer_fee_flat/transfer_fee_rate` (data-driven sender fees; seeded NE XOF 50 / NG NGN 20). Rollback/forward smoke verified.
2. **`CustomerTransferService`** — recipient resolution by KoriePay ID (case-insensitive) or phone (digits-only); server preview (fee + total debit, **nothing persisted**); idempotent `send()` through `PaymentOrchestrator` (Phase 5 state machine → SETTLED|FAILED); honest outcome mapping (`success|failed|processing|unknown`); guards all server-side: ownership, self-transfer, amount format (XOF integer-only), recipient wallet eligibility (§75), balance incl. fee, daily send limit, inactive recipients.
3. **Fee integrity** — transfer fee debited from sender + credited to Platform Revenue **in the same atomic ledger posting** as principal (`InternalLedgerProvider::transfer`, one debit entry per account), stored as `transactions.fee_charged`. Verified: sender −(amount+fee), receiver +amount, revenue +fee.
4. **API** — `POST /api/v1/customer/transfers/preview`, `POST /api/v1/customer/transfers` (requires `Idempotency-Key`; replay returns the original transaction), `GET /api/v1/customer/transfers/{reference}` (ownership-checked 403), `GET /api/v1/customer/receive` (identity + canonical `koriepay://pay/KP-…` payload + real server-rendered QR via `endroid/qr-code`, inline SVG data URI).
5. **Pay hub UI** — `App\Livewire\Customer\PayHub` rewritten as the real hub: Send journey (wallet picker → recipient/amount/note → **server preview with fee** → idempotent confirm → success/failed/processing result with reference; retry reuses the same idempotency key), Receive journey (identity card + scannable QR + copy ID), deep-linkable via `/customer/pay?view=send|receive` (dashboard quick actions wired). Honest loading overlay, error alert cards, no money-move without preview.
6. **Tests** — `CustomerTransferStage2Test` (20): resolution (ID/phone/case/inactive), preview-no-persist, ledger movement with fee + revenue, idempotent replay, every guard (self, balance incl. fee, XOF decimals, recipient eligibility, daily limit, foreign wallet), status ownership (403), outcome mapping, receive identity + QR API, send API + idempotency + missing key 422, pay hub shell renders hub/send/receive.
7. **Fixes landed** — `PaymentOrchestrator::transfer()` gained `$meta`; ledger provider fee entries (unique account+side per posting); `Money::fromDecimal` zero-minor accepts `.00` formatting; Laravel auth-guard user caching in tests (`forgetGuards()`); `User` model auto-generates `koriepay_id`; pay-hub `$showBalance` made a real Livewire property.

### Stage 3 deliverables — ✅ DONE (Exchange execution, brief §91/§92)
1. **Quote → execute** — `ExchangeQuoteService::execute()` is the ONLY path that moves money on a quote: serialized (`SELECT … FOR UPDATE` on the quote row), re-runs every guard after the lock (ownership, status, expiry, pair/KYC availability, daily limit, live balance incl. fee), idempotent (same key returns the original transaction even after the quote is consumed), and the quote is marked `used` atomically only on SETTLED. Expiry marking is persisted outside the rolled-back transaction.
2. **Ledger posting (per-currency balanced)** — `InternalLedgerProvider::exchange()`: DR source wallet (source+fee) / CR platform cash (source) + revenue (fee); DR platform cash (destination) / CR destination wallet. `transactions.exchange_rate` + `fee_charged` populated.
3. **Receipts** — `TransactionReceiptService`: HMAC-SHA256 over a canonical field string; `hash()`, `verify()`, `receipt()` with `verification_url`.
4. **API** — `POST /exchange/quote` (XOF integer, 403 foreign, 422 bad input), `POST /exchange/execute` (Idempotency-Key required; 409 expired/used, 422 domain failures, 404 unknown), receipts in the response.
5. **UI** — `customer.pay` renders the full Exchange journey (source/dest wallets, server quote, idempotent execute, honest result incl. receipt hash + verification link); dashboard quick action deep-links `?tab=exchange`.
6. **Tests** — `ExchangeExecutionStage3Test` (12): SETTLED movement + quote consumed, idempotent replay, expiry (409 + persisted state), foreign quote, insufficient balance, limit re-check at execute, pair disabled mid-quote, receipt HMAC verify/tamper, API e2e + 404/409, legacy transfer untouched.

### Stage 4 deliverables — ✅ DONE (Transactions & receipts)
1. **History API** — `CustomerTransactionService::history()` (ownership-scoped to sender), filters `type|currency|from|to|status|q`, explicit `filters` + `pagination` in the payload, validated (422 on unknown enum).
2. **Single transaction + receipt** — `GET /transactions/{reference}` returns the verified receipt (`hash`, `hash_algo`, `verification_url`); foreign reference ⇒ **403** (no data leak), unknown ⇒ 404.
3. **Verification endpoint** — `GET /transactions/{reference}/verify` recomputes the HMAC server-side and reports integrity.
4. **Tests** — `CustomerTransactionStage4Test` (12): empty history + filters shape, ownership scoping (never another user's rows), type/currency/status/q filters, invalid filter 422, receipt payload, 403 foreign / 404 unknown, verify endpoint, real exchange appears in history with rate/fee.

### Stage 5 deliverables — ✅ DONE (Profile / security / branding)
1. **PIN & biometric — no storage** — `Profile` Livewire card: 6-digit digit-pad PIN with a transient Alpine confirmation dialog (browser-local only, nothing transmitted); biometric toggle with honest WebAuthn support detection (`resources/js/customer/device.js` → `KoriePayDevice`) calling `POST /profile/security/biometric` — session-only, `persisted: false`. `POST /profile/pin/enroll` deliberately REJECTS PIN storage (`pin_storage_not_supported`, 422). The legacy `users.transaction_pin` column is never written by the customer app.
2. **Security center** — `CustomerSecurityService`: device list from the REAL `devices` table (honest "insufficient usage data" empty state), per-wallet limit rows from the country+currency config (unconfigured ⇒ "Not set"), daily spend today computed from real transactions, session-only limit editing (config tables untouched).
3. **KYC center** — `CustomerKycService::revaluate()` from real `kyc_submissions` (approved ⇒ autopass regardless of the users mirror), tier recommendation, per-tier ID-card actions routing to the real verification journey, transient PIN verify demo, digital identity (name/email persisted; DOB read-only from the submitted ID doc when on file).
4. **Language** — `LanguageSwitcher` en/fr/ha, session-persisted locale applied app-wide, `@lang()`/`__()` stub plumbing live (UI strings stay English), RTL attribute stub via `device.js`.
5. **Tests** — `CustomerProfileStage5Test` (17): locale default/switch/persist/reject, KYC revaluation (none/pending/approved-autopass), biometric session-only + boolean validation, PIN enroll refusal, profile PIN flow never persists, devices empty/real, wallet limits + real daily spend, session limit edits, page smoke 200s, identity save + duplicate-email rejection.

### Stages after this one (per brief §128, §134)
- **Stage 6 — Intelligence**: money insights, timeline, spending (real data only; honest "not enough data" empty states).
- **Stage 7 — Low-bandwidth/offline**: network recovery, debounced quotes, app-shell caching.

---

## 3. Guardrails carried forward (from the standing mandates)
- Balances are **never** read from `wallets.balance` — only from ledger projections (+ real pending derivation).
- Exchange rates come **only** from the authoritative `fx_rates` + server-side quote; frontend never calculates.
- No mock balances, no hardcoded transactions, no fake analytics.
- Country/KYC eligibility is **backend-enforced**; the frontend only renders what the API returns.
- All money movement keeps the mandated state machine + idempotency (Phase 5) — Stage 2+ wires `PaymentOrchestrator`.
- Sensitive fields masked (`+227 XXX XXX XXX`), access recorded.
