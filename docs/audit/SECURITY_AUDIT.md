# KoriePay — Security Audit (SECURITY_AUDIT.md)

> Phase 1 deliverable · 2026-08-31 · Base commit `6a403eb`
> All findings verified against repository contents and live-host probing
> (passive checks only; no credentials were tested against the live app).

Severity: 🔴 Critical · 🟠 High · 🟡 Medium · 🔵 Low

---

## 🔴 C-1 — GitHub 2FA recovery codes committed to a public repo

File `github-recovery-codes.txt` (16 codes, GitHub format `aaaaa-bbbbb`) is in
`main`. Anyone with repo read access who compromises/phishes the account can
bypass 2FA.

**Phase 0 actions taken (working tree):** file removed; `.gitleaks.toml` rule
`koriepay-recovery-codes`; pre-commit gate; CI secret scan.
**Owner actions required (cannot be performed from this sandbox):**
1. Regenerate recovery codes in GitHub account settings (invalidates these).
2. Rotate any credential tied to the account.
3. Purge history: this repo has a single root commit, so rewrite via
   `git filter-repo --invert-paths --path github-recovery-codes.txt --path koriepay_lara878.sql`
   then force-push with collaborator coordination.
4. Enable GitHub secret scanning + push protection for the org/repo.

## 🔴 C-2 — Production database dump committed

`koriepay_lara878.sql` (phpMyAdmin export) contains 48 user rows: real names,
personal emails (gmail/icloud), a phone number, a Wema virtual account number,
bank-node account numbers (Zenith `1012233890`, Ecobank Senegal `5029988112`,
SC `0099887766`) with balances, wallet balances, transaction history, audit
logs with IPs, and **encrypted session payloads**.

**Impact:** credential-stuffing risk for listed users; financial-data exposure.
**Phase 0 actions:** removed from working tree; `.gitignore` now blocks `*.sql`;
gitleaks rule `koriepay-sql-dump`. **Owner actions:** purge history (above),
advise affected users to rotate passwords/PINs, treat DB state as compromised.

## 🔴 C-3 — Superadmin & owner password is `12345678` (cracked offline)

The bcrypt hash `$2y$12$UPVbdJlD8XsE57wYqEpRq…` (used by BOTH
`admin@koriepay.com` superadmin and `baiita@icloud.com`) was verified to match
`12345678` offline in <1 s. Hash is public in the dump → offline cracking is
unthrottled. The in-app rate limit protects only live attempts.

**Actions:** rotate both passwords immediately; enforce MFA for privileged
roles (Phase 4); add pepper/Argon2 for PINs (M-3).

## 🟠 H-1 — Unauthenticated `/clear-cache` route (confirmed live)

`routes/web.php` exposed `GET /clear-cache` → `Artisan::call('cache:clear'…
view:clear)`. Verified live: returns "Cache cleared successfully!".
**Fix applied (Phase 0):** route removed from `routes/web.php`.

## 🟠 H-2 — Fail-open webhook secrets & non-constant-time comparison

`Api/WebhookController` used `env('GATEWAY_WEBHOOK_SECRET', 'simulation_secret_123')`
with `!==` comparison. If env was unset, the secret was public → forged
`transfer.failed` events could trigger refunds (`balance` increments).
**Fix applied:** fail-closed (reject when secret unset), `hash_equals()`, row-lock
idempotency, `transfer.reversed` handled, notification isolated from settlement.

## 🟠 H-3 — KYC webhook: no signature, not routed

`Api/KycWebhookController::handle` accepts `user_id` + `ResultCode` and flips
`kyc_status = verified` with zero authentication. Route is currently NOT
registered (latent), and `SmileIDService` references `route('api.kyc.webhook')`
which does not exist. **Phase 5:** register only with Smile ID signature
verification (HMAC over payload) + nonce/replay protection.

## 🟡 M-1 — No security headers on live responses

Verified absent: HSTS, X-Frame-Options, CSP, X-Content-Type-Options,
Referrer-Policy. **Fix applied:** `SecurityHeaders` middleware (global).

## 🟡 M-2 — `app.koriepay.com` directory listing

LiteSpeed autoindex returns "Index of /". **Action:** disable `autoindex` in the
virtual host (host-side; documented in DEPLOYMENT.md).

## 🟡 M-3 — Transaction PIN: bcrypt over a 4-digit space

4-digit PINs have ≤10,000 values → offline cracking of leaked hashes is
feasible (~15–20 min/hash). `failed_pin_attempts`/`pin_locked_until` only
throttle live attempts. **Plan:** Argon2id + per-user pepper (see Phase 4),
or challenge-response with server-held secret; never store recoverable PINs.

## 🟡 M-4 — Hardcoded secrets/credentials in code

- `Agent/CashOut`: 6-digit auth code generated via `rand()` and stored **plaintext**
  in `transactions.auth_code`, compared with `!=`, and surfaced in a session
  flash ("Simulating SMS"). → Replace with server-side OTP (hashed, TTL, expiry,
  delivery via notification channel, constant-time compare).
- `Agent/CrossBorder`: hardcoded fallback FX rate `0.55` and 3% fee.
- `Customer/WithdrawVault`: hardcoded flat fees (₦50 / 150 XOF).
- `Customer/FundVault`: mock "API Pending" top-up (functional fraud vector if
  presented as real).
- `SendLiquidity`: hardcoded fallback rates `0.42`/`2.38` and 1.5% fee.

## 🟡 M-5 — Role model is a string column, no permissions

`users.role` drives all gating via `CheckAdmin`. Spatie Laravel Permission is
installed but unused. Any DB write flips privileges. **Phase 4:** RBAC with
`role_permissions` seed + `CheckPermission` middleware (added in Phase 0) +
policies; never trust client-supplied role.

## 🟡 M-6 — Insecure session surface / env usage

- `env()` used directly in controllers (`PAYSTACK_SECRET_KEY`, `DUSUPAY_WEBHOOK_SECRET`)
  — breaks under `config:cache`; switch to `config('services.*')` (Phase 4).
- `.env.example` ships `APP_DEBUG=true`, `SESSION_ENCRYPT=false`.
- Dump contains encrypted session payloads (decryption key = APP_KEY; treat as exposed).

## 🔵 L-1 — Mass-assignment drift

`User::$fillable` lists `pin_hash`/`pin_attempts` (schema has
`transaction_pin`/`failed_pin_attempts`); `FxRate` casts/query `rate`/`is_active`
but schema has `mid_market_rate`/`effective_rate`/`status`; Adashi engine writes
`user_id` on `transactions` (not in schema) — silently dropped by fillable guard.

## 🔵 L-2 — Error-handling opacity

`SendLiquidity`/`WithdrawVault` echo raw exception messages to the UI
(`'Transfer failed: ' . $e->getMessage()`), leaking internals. Route all
financial errors through a domain exception → opaque user message (Phase 3).

## ✅ Positive controls (retain)

Rate-limited login, PIN lockout, Paystack HMAC pattern, row-locked settlement
with sorted-node deadlock prevention, route-key binding + receipt ownership
check, per-portal role middleware, `fillable` whitelists.

---

## Phase 0 verification checklist (done in working tree)

- [x] `github-recovery-codes.txt` removed from tree
- [x] `koriepay_lara878.sql` removed from tree
- [x] `.gitignore` hardened (env, *.sql, keys, recovery codes)
- [x] `.gitleaks.toml` (defaults + incident rules)
- [x] GitHub Actions `security.yml` (gitleaks + composer audit + npm audit)
- [x] pre-commit secret gate (`scripts/pre-commit-secret-gate.sh`)
- [x] `/clear-cache` route removed
- [x] `SecurityHeaders` middleware (global)
- [x] Webhook controllers fail-closed + `hash_equals`
- [x] `.env.testing` with explicit (non-default) webhook secrets
- [ ] **Owner:** history purge + force-push (needs GitHub credentials)
- [ ] **Owner:** regenerate GitHub recovery codes
- [ ] **Owner:** enable GitHub secret scanning / push protection
- [ ] **Owner:** rotate admin & owner passwords; inform affected dump users
