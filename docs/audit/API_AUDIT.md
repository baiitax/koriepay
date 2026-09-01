# KoriePay — API Audit (API_AUDIT.md)

> Phase 1 deliverable · 2026-08-31

---

## 1. Existing API surface (routes/api.php + controllers)

```
POST /api/webhooks/gateway        → Api\WebhookController::handleGatewayWebhook
POST /api/webhooks/paystack       → Api\InterbankWebhookController::handlePaystack
POST /api/webhooks/dusupay        → Api\InterbankWebhookController::handleDusuPay
```

Plus web (non-API) endpoints: `POST /webhook/paystack` (CSRF-exempt), auth
routes (login/register/forgot/reset), and the legacy `GET /clear-cache`
(removed in Phase 0).

## 2. Findings

| # | Finding | Severity |
|---|---|---|
| A-1 | No `/api/v1` versioning; no consumer-facing API (everything is Livewire). The "API Reference" marketing page advertises `api.koriepay.com/v2/transfers` which does not exist. | 🟠 |
| A-2 | Webhook routes have **no auth middleware** — security rests entirely on in-controller signature checks (H-2/H-3 in SECURITY_AUDIT). | 🟠 |
| A-3 | No request `id`/`trace_id`/correlation ID support; logging is ad-hoc `Log::info` strings. | 🟡 |
| A-4 | No rate limiting on webhook endpoints (global throttle added in Phase 0 via `throttleApi('60,1')` — verify semantics per endpoint in Phase 5). | 🟡 |
| A-5 | No structured response envelope; no consistent error codes; raw exception text leaks to clients in Livewire flows. | 🟡 |
| A-6 | `PaystackWebhookController` (web) uses `hash_hmac('sha512', content, secret)` correctly; `InterbankWebhookController::handlePaystack` also correct pattern; `handleDusuPay` compares `Authorization` header to `env('DUSUPAY_WEBHOOK_SECRET')` with `!==`. | 🔵 |
| A-7 | `Api/WebhookController` refund path used `$lockedTx->user` / `user_id` which don't exist on the model → latent runtime error (patched in Phase 0 to use `sender_id`/`source_currency`). | 🔵 |
| A-8 | No OpenAPI spec; no API keys / HMAC request signing for privileged integrations. | 🟡 |
| A-9 | KYC webhook unsigned + unrouted (H-3) — must be added only with signature verification. | 🟠 |

## 3. Target API contract (Phase 5)

- Base path: `/api/v1` (auth, customers, agents, wallets, transactions,
  transfers, deposits, withdrawals, payments, providers, webhooks, admin ops).
- Response envelope (all endpoints):

```json
{ "success": true, "message": "Transaction initiated",
  "data": {}, "meta": { "request_id": "…", "trace_id": "…" } }

{ "success": false, "code": "INSUFFICIENT_FUNDS",
  "message": "Insufficient available balance",
  "trace_id": "…" }
```

- Idempotency: every money-moving endpoint requires `Idempotency-Key` header
  (UUID) or generates one server-side; duplicates return the original response.
- Auth: Bearer access tokens (Sanctum) + refresh rotation; API keys for machine
  clients with HMAC request signing; webhooks signed per provider (HMAC over
  raw body + timestamp window + replay nonce).
- Errors: machine codes, no stack traces in production.
- Docs: OpenAPI 3.1 generated from route metadata; per-endpoint auth,
  permissions, idempotency, rate limits documented (Phase 5 deliverable).
