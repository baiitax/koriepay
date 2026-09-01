# KoriePay — Financial Flow Audit (FINANCIAL_FLOW_AUDIT.md)

> Phase 1 deliverable · 2026-08-31
> Every flow below was read from source. Line references are to the base
> commit. This document is the evidence base for Phases 3 (ledger) and 5–6.

**Headline:** No flow is safe as-is. All mutate balances directly; most are
single-entry; two are outright broken (double-debit cash-out; fabricated FX).

---

## F1. P2P transfer — `app/Livewire/Customer/SendLiquidity.php`

**Flow (verified):**
1. Recipient lookup by email/username/phone (excludes self).
2. Limits: sender daily = verified ? 5,000,000 : 50,000 (sum of `source_amount`
   for today by `sender_id`); receiver capacity = verified ? 10,000,000 : 300,000.
3. PIN verify via trait (bcrypt check; 3 fails → 24h lock).
4. Idempotency: `Cache::add("transfer_lock_{user}_{recipient}_{amount}_{from_currency}", 10s)`.
5. `DB::transaction`: `lockForUpdate` both wallets → `decrement` sender,
   `increment` receiver → single `transactions` row `status=completed` →
   notify recipient.

**Problems:**
- P1 Direct balance mutation; single-entry; no ledger entries.
- P2 "Completed" before any external settlement exists (internal-only, so OK for
  in-app transfers, but conflates status semantics — see state machine §11).
- P3 Idempotency keyed by (user, recipient, amount) — two legitimate identical
  transfers 10s apart are rejected (false positive), while two rapid DIFFERENT
  amounts are allowed; no server-generated idempotency_key; 10s TTL.
- P4 Limits use `whereDate(created_at, today())` on DB timezone; `sum('source_amount')`
  misses `fee` in some paths and mixes FX-converted amounts.
- P5 Receiver wallet `firstOrCreate` outside the lock (`validateLimits`) → race
  on first-credit; capacity check is off a stale projection.
- P6 Raw exception message echoed to the UI.
- P7 Fee hardcoded 1.5% in `calculateFX`; fallback FX rates 0.42/2.38; FX read
  from cache key `FX_NGN_XOF` (populated by the mock `fx:fetch` command).

## F2. Card deposit — `app/Livewire/Customer/FundVault.php`

**Verified:** `initiateCardPayment()` validates amount, generates a reference,
flashes `"Gateway initialized for ₦X. (API Pending)"`, resets. **No HTTP call,
no Paystack/Flutterwave SDK usage anywhere in the flow.** Money cannot be funded
by card today despite the UI implying it. → non-functional feature + risk of
being "finished" later with mock semantics. Phase 5 replaces with real
orchestration; until then the UI must not claim success.

## F3. Bank withdrawal — `app/Livewire/Customer/WithdrawVault.php`

**Flow (verified):** channel lists hardcoded (19 NGN banks, 4 XOF channels);
account resolve: NGN via Paystack `/bank/resolve` with `env('PAYSTACK_SECRET_KEY')`,
XOF via **simulated** DusuPay (`sleep(1)`, name = user name + "(REGIONAL USER)").
`processWithdrawal()`: PIN verify → `DB::transaction` → lock wallet → decrement
`balance` by amount+fee → insert `transactions` row `status=completed` → redirect
to receipt. **There is NO payout call.** The system marks money as "withdrawn"
and permanently debits the wallet without any transfer being executed.

**Problems:**
- P1 Funds exit the ledger with nothing executed at the provider (worse than
  mock — it looks real in reports/receipts).
- P2 Fees hardcoded ₦50 / 150 XOF.
- P3 No idempotency key; double-click → two completed withdrawals.
- P4 `digits:10` validation rejects 12-digit NUBAN variants? (10-digit ok; no
  alphanumeric support for some corridors).
- P5 `env()` direct; no provider abstraction; XOF path entirely fictional.
- P6 No reversal path if a real provider rejects later.

## F4. Agent cash-in — `app/Livewire/Agent/CashIn.php`

**Flow (verified):** verify customer → `DB::transaction` → lock agent NGN wallet
→ if agent float insufficient throw → `firstOrCreate` customer NGN wallet →
decrement agent `balance`, increment customer `balance` → single `transactions`
row (`type=cash_in`, completed) → `AuditLog::forceCreate`.

**Assessment:** Direction is correct (customer hands cash to agent → agent float
↓, customer wallet ↑). But: no physical-cash reconciliation, no float ledger, no
cash position tracking, no daily limits per agent, single-entry, `forceCreate`
audit (bypasses mass-assignment guards — deliberate but non-standard), no
commission for the agent, and no linkage between the customer's wallet and the
agent's cash register.

## F5. Agent cash-out — `app/Livewire/Agent/CashOut.php` ⚠️ DOUBLE-DEBIT BUG

**Flow (verified):** verify customer → `requestAuthorization()` generates
`rand(100000,999999)` code, stores it **plaintext** in `transactions.auth_code`
and shows it in a flash "AUTH CODE GENERATED: 123456 (Simulating SMS)" →
`authorizeDispense()` compares `$this->authCodeInput != $tx->auth_code`
(loose, non-constant-time) then:

```
DB::beginTransaction();
try {
    fee = source_amount * 0.001;            // comment says "1% Fee" but 0.001 = 0.1%
    totalDeduction = source_amount + fee;
    customerWallet.decrement(totalDeduction);
    agentWallet.increment(source_amount);
    agentWallet.increment(commission_balance, fee);
    tx.update(status=completed, fee_charged=fee, auth_code=null);
    AuditLog.forceCreate(...);
    try {                                   // ← SECOND SETTLEMENT (duplicate block)
        customerWallet.decrement(source_amount);   // debits customer AGAIN
        agentWallet.increment(source_amount);      // credits agent AGAIN
        tx.update(status=completed, auth_code=null);
        AuditLog.forceCreate(...);
        DB::commit(); reset(); flash success;
    } catch (...) { DB::rollBack(); ... }
} catch (...) { DB::rollBack(); ... }
```

**Verified consequences:** customer is debited **principal + fee + principal**,
agent is credited **principal × 2** (+ fee once) — a real money-loss bug if this
path executes. Additionally: auth code is plaintext, predictable via `rand()`,
never expires, never invalidated on wrong attempts (unlimited guesses), and is
delivered via a session flash instead of an out-of-band channel.

## F6. Agent cross-border — `app/Livewire/Agent/CrossBorder.php`

**Flow (verified):** `mount()` reads `FxRate::where('pair','NGN/XOF')->first()`
and casts nonexistent `rate` → effectively 0 if row exists; fallback `0.55`.
`updatedSourceAmount()` computes destination = amount × rate, fee = amount × 0.03
(hardcoded 3%). `executeSettlement()`: lock agent NGN wallet → `firstOrCreate`
receiver XOF wallet → decrement agent float, increment receiver wallet → row
`type=cross_border completed` → agent `commission_balance += fee`.

**Problems:** rate source broken (schema mismatch), rate hardcoded fallback,
3% fee hardcoded, no provider/FX lock, no `fx_quotes`, no market/timestamp
validation, single-entry, receiver capacity unchecked, agent can send to
anyone without customer authorization (no OTP).

## F7. FX rate production — `app/Console/Commands/FetchLiveFxRates.php`

**Verified:** "live" NGN/XOF rate is **`mt_rand(400,450)/1000`**, cached 20 min
under `FX_NGN_XOF`/`FX_XOF_NGN`, used directly by `SendLiquidity`. This is
fabricated market data driving real transfers. Phase 5: replace with a real
rate provider (or explicit halt) + quote/lock lifecycle.

## F8. Settlement engine + job — `app/Services/SettlementEngine.php`, `app/Jobs/ProcessCrossBorderTransfer.php`

**Verified:** engine signature `execute($senderId,$receiverId,$sourceNodeId,$destNodeId,$destCurrency,$amount)` (6 params) but the job calls
`$engine->execute($senderId,$receiverName,$sourceBankId,$destCurrency,$amount)`
(5 args) → **ArgumentCountError** whenever dispatched. Inside the engine:
locks sorted node ids (good), checks `sourceBank->balance < amount` (node
balance as float), queries `FxRate::where('pair')` + `$rate->rate` (broken
columns), logs revenue with `amount_usd` via `FxRate::where('pair',"NGN/USD")`.
Job catch block: "Here you would also update a Pending transaction to Failed
and refund" — **refund path is not implemented** (comment only).

## F9. Adashi settlement — `app/Console/Commands/AdashiSettlementEngine.php`

**Flow (verified):** for active groups due today: lock members; deduct
contribution from each member wallet (skip on insufficient funds → dispatch
`AdashiRescueJob` synchronously as "bailout"); payout `totalCollected` to the
cycle recipient; mark `has_received_payout`; complete group at last cycle.

**Problems:** transactions created with `user_id`/`amount`/`currency` keys not in
schema (dropped silently) → orphan records; no ledger; no idempotency (re-run
would double-deduct — no "processed cycle" marker used by the command); rescue
job semantics unclear; member deduction uses non-transactional `whereDate`-style
due logic; no member-capacity checks; single command process (no queue
isolation).

---

## Cross-cutting financial defects (all flows)

| # | Defect | Where |
|---|---|---|
| A1 | Direct balance mutation (no ledger) | all |
| A2 | `status=completed` set optimistically | F1,F3,F4,F5,F6,F9 |
| A3 | Floats used for money arithmetic | all |
| A4 | No idempotency table (cache-lock only in F1) | F1–F9 |
| A5 | No state machine / reversal path | all |
| A6 | Hardcoded fees/rates/commissions | F1,F3,F5,F6 |
| A7 | No provider abstraction; XOF paths simulated/fictional | F2,F3,F6,F7 |
| A8 | No per-request idempotency key from client | all |
| A9 | No maker/checker for manual ops | admin (N/A today) |
| A10 | No reconciliation hooks | all |

## Phase 3/5/6 remediation mapping

- Ledger engine + `LedgerService` (double-entry) — replaces A1/A3 for every flow.
- `IdempotencyService` + `idempotency_keys` table — A4/A8.
- `TransactionStateMachine` — A2/A5 (INITIATED→PROCESSING→AUTHORIZED→POSTED→SETTLED; FAILED/REVERSED).
- `CommissionEngine` + DB rules — A6.
- `PaymentOrchestrator` + `PaymentProviderInterface` + real adapters — A7.
- Cash-out rewrite (OTP out-of-band, single settlement, constant-time) — F5.
- Withdrawal rewrite (initiate → provider → webhook → settle) — F3.
- Deposit rewrite (real gateway intent + webhook verify + post) — F2.
- FX: real feed + `fx_quotes`/`fx_locks` — F6/F7.
