# KoriePay Super Admin — Current State Audit & Target Gap Map

> Pre-implementation inspection required by directive §95 ("Before implementation: inspect the repository, identify existing routes, components, APIs, tables, permissions, metrics, reusable components, broken functionality; map current dashboard to target architecture").
> Audit date: 2026-08-31 · Repo: `koriepay_rebuild` @ `6a403eb`

---

## 1. Current State Inventory (as-found)

### 1.1 Admin routes (`routes/web.php`, lines 174–~200)

All under `Route::middleware(['auth', 'role:superadmin'])->prefix('admin')->name('admin.')`:

| Route | Component | Purpose |
|---|---|---|
| `/admin/dashboard` | `App\Livewire\admin\Dashboard` | 4 KPIs + 7-day chart + 6-row live feed |
| `/admin/nodes` | `NodeManager` | Node management |
| `/admin/transactions` | `TransactionLedger` | Transaction list |
| `/admin/directory` | `AgentDirectory` | Agent directory |
| `/admin/treasury` | `TreasuryVault` | Treasury |
| `/admin/liquidity` | `LiquidityWallets` | Liquidity vaults |
| `/admin/fx-rates` | `FxRates` | FX rates |
| `/admin/settlements` | `SettlementDashboard` | Settlements |
| `/admin/master-ledger` | `MasterLedger` | Ledger |
| `/admin/revenue` | `RevenueLedger` | Revenue |
| `/admin/analytics` | `RevenueAnalytics` | Revenue analytics |
| `/admin/kyc-hub` | `KycHub` | KYC hub |
| `/admin/kyc-queue` | `KycQueue` | KYC queue |
| `/admin/network-health` | `Network` | Network health |
| `/admin/settings` | `SystemSettings` | Settings |
| `/admin/security` | `SecuritySettings` | Security settings |
| `/admin/audit-logs` | `AuditLogs` | Audit logs |

**17 Livewire components; 17 blade views** in `resources/views/livewire/admin/`. Layout: `resources/views/layouts/admin.blade.php` (173 lines; sidebar + topbar, tailwind/Alpine; Google Fonts external).

### 1.2 Current dashboard component (`app/Livewire/admin/Dashboard.php`)

- Mount: 7-day transaction volume chart (live `sum('source_amount')` per day on `transactions`).
- Render: 4 KPIs — `totalVolume` (`Transaction::where(status,'completed')->sum('source_amount')`), `totalRevenue` (`RevenueLog::sum('amount_usd')`), `activeLiquidity` (`BankNode::sum('balance')`), `successRate` (completed/total %).
- `#[On('echo-private:admin-grid,pulse.update')]` — Echo channel listener refreshes chart.
- **Assessment:** basic KPI board, live queries, no trend/benchmark/target, no drill-down, no country filter, single-role access, USD-labeled revenue (`amount_usd`), success-rate denominator includes non-terminal states.

### 1.3 Models available (`app/Models/`)

`AdashiCycle, AdashiGroup, AdashiMember, AuditLog, BankNode, FxRate, LinkedVault, RevenueLog, Setting, SupportTicket, Transaction, TreasuryVault, User, Wallet`.

### 1.4 Services available

`app/Services/`: `CircuitBreaker, FxService, SettlementEngine, SmileIDService` · `app/Domain/Accounting/`: ledger core (Money, LedgerService, LedgerAccount, LedgerTransaction, LedgerEntry, IdempotencyService, CommissionEngine, TransactionStateMachine, ReversalService, Exceptions).

### 1.5 Permissions / RBAC (foundation)

- `CheckPermission` middleware (`permission:{name}`) resolves via `Gate` against `role_permissions` table (seeded by Phase-2 migration `000600`). **Present but not yet wired to admin routes** — all 17 admin routes currently gate only on `role:superadmin` via `RoleMiddleware`.
- No granular per-action permissions on any dashboard route yet (Phase 4 scope).

### 1.6 APIs

- **No dashboard API routes exist** (`routes/api.php` has no admin/dashboard endpoints). Directive §78 (separate `/api/v1/admin/*` endpoints) is a greenfield requirement.

### 1.7 Precomputed metrics tables

- **NONE.** No `daily_platform_metrics`, `hourly_transaction_metrics`, `agent_daily_metrics`, `aggregator_daily_metrics`, `country_daily_metrics`, `risk_daily_metrics`, `provider_hourly_metrics`, or `liquidity_snapshots`. Directive §79 requires all of these — to be created (Phase 2-style migrations + aggregation jobs).

### 1.8 Reusable components / design system

- None shared. Layouts duplicate sidebar/topbar markup per portal (`admin`, `agent`, `customer`, `regional`, `manager`). Directive §69 design-system components (GlassCard, MetricCard, …) do not exist. Blade + Tailwind v3 + Alpine; charting via inline ApexCharts script in dashboard view (needs centralization).

### 1.9 Known broken / unsafe functionality observed

- Success-rate KPI counts all `transactions` rows including non-terminal states; `sum('source_amount')` on mixed NGN/XOF without currency separation.
- Revenue shown in USD (`amount_usd`) — directive §9 forbids mixing currencies without labeling.
- Live queries on `transactions` at dashboard load — will not scale to millions of rows (directive §97).
- Echo/pulse channel exists but has no authenticated permission check on the channel name beyond `auth` guard — verify channel authorization in Phase 9.
- External Google Fonts in admin layout — offline/low-bandwidth environments (directive §100) should use local assets.

---

## 2. Gap Map — Directive → Current State → Plan

Legend: ✅ exists · 🟡 partial/foundation · ❌ absent · 🔨 build in phase X

| Directive area | Current state | Target action |
|---|---|---|
| §6 Sidebar IA (9 groups, indicators, submenus) | 🟡 flat sidebar, 4 groups, no unread/critical indicators | Rebuild IA (Phase 9 Stage 1) |
| §8 Top command bar (search, country, notifications, status) | 🟡 minimal header (hamburger, title, profile) | Rebuild (Stage 1) |
| §9 Global country switcher | ❌ | Country context + server-side data isolation (Stage 1 + Phase 4) |
| §10–12 Executive KPI grid + Health Score | 🟡 4 KPIs, no trend/benchmark/health | Executive layer (Stage 2) + metrics tables (§13.2) |
| §13 Live operations strip | 🟡 single pulse channel | Real health checks (Stage 2/6) |
| §14 Real-time transaction monitor | ❌ (only 6-row static feed) | Stream + polling fallback (Stage 2/6) |
| §15–16 Transaction intelligence + anomalies | ❌ | Metrics + anomaly service (Stage 2/7) |
| §17–18 Financial intelligence + waterfall | 🟡 RevenueLedger/Analytics exist; no waterfall | Financial layer (Stage 3) |
| §19–21 Liquidity center/map/forecast | 🟡 LiquidityWallets exists; no map/forecast | Liquidity layer (Stage 3/7) |
| §22–25 Network intelligence (agents/aggregators/customers) | 🟡 AgentDirectory exists | Network layer (Stage 4) |
| §26 KYC/KYB center with aging | 🟡 KycHub/KycQueue exist | Enhance with aging/SLA (Stage 4/5) |
| §27–31 Risk/fraud/holds/AML/investigation graph | 🟡 SecuritySettings; no risk center | Risk layer (Stage 5, builds on Phase 7) |
| §32–33 Provider command + routing | ❌ | Payment layer (Stage 6, builds on Phase 5) |
| §34–35 Settlement + reconciliation centers | 🟡 SettlementDashboard exists; no reconciliation | Stage 3/6 (builds on Phase 8) |
| §36–40 System health/API/queue/webhook/incidents | ❌ | Infrastructure layer (Stage 6, builds on Phase 5/11) |
| §41–43 Approval center + maker-checker + audit intelligence | 🟡 AuditLogs exists | Operations layer (Stage 3/5, builds on Phase 7 maker-checker) |
| §44–46 Security center + decision engine | ❌ | Security + decision (Stage 2/7) |
| §47–48 Insights + drill-down | ❌ | Stage 2/7 |
| §49–56 Filters, time intelligence, command palette, keyboard | ❌ | Stage 1/2 UX |
| §57 Mobile super admin | ❌ | Stage 1 responsive |
| §58–71 Design system, a11y, glassmorphism, micro-interactions | ❌ (raw Tailwind per page) | Stage 1 (Blade components, Tailwind tokens) |
| §72–76 Mode switcher (Executive/Ops/Risk/Finance/System) | ❌ | Stage 2/5/6 views |
| §77–81 Exports, API separation, analytics cache, freshness, decision audit | ❌ (no /api/v1/admin, no metrics tables) | Stage 1/2/3 + migrations + jobs |
| §82–86 Confidence, benchmarking, alert engine/escalation, BCP | ❌ | Stage 7 + Phase 7 rules |
| §93–94 System configuration + change control | 🟡 SystemSettings exists; no maker-checker | Stage 1/3, builds on Phase 4/7 |
| §98–99 Security + testing acceptance | 🟡 CheckPermission/role_permissions foundation | Phase 4 RBAC + Phase 9 tests |

---

## 3. Implementation Plan (integrated with the 12-phase mandate)

The dashboard is **Phase 9 (Dashboards)** in the mandated order. It cannot be built on fabricated data: its KPIs, risk, liquidity, provider, reconciliation and RBAC surfaces depend on Phases 3–8. Sequence:

1. **Finish Phase 3** — Ledger core is green (36 Unit tests); close the remaining 100-worker ConcurrencyTest (50 OK / 50 FAIL acceptance), re-run full Unit suite.
2. **Phase 2 closure** — execute the migration set (`000100`–`000700`) + rollback smoke on a real database.
3. **Phases 4–8** — Identity/RBAC/KYC, Payment core, Agency banking, Risk, Reconciliation (each lands the tables + services the dashboard reads).
4. **Phase 9 — this Command Center build**, in the directive's Stage order:
   - Stage 1: layout/sidebar/topbar/design system/RBAC wiring/country switcher/mobile + `/api/v1/admin/*` skeleton.
   - Stage 2: metrics tables + aggregation jobs → KPIs, health score, live strip, critical alerts, decision cards.
   - Stage 3: financial/liquidity/settlement/reconciliation centers (revenue waterfall, liquidity map/forecast).
   - Stage 4: network intelligence (agents/aggregators/customers + benchmarks).
   - Stage 5: risk/fraud/AML/KYC/holds/investigations.
   - Stage 6: providers/routing/system health/API/queue/webhook/incident monitoring.
   - Stage 7: anomaly detection, forecasting, decision engine, predictive liquidity, confidence labels.
5. **Phase 10–12** — Intelligence, Hardening (incl. dashboard load/security tests), Production.

**Guardrails carried forward:** no mock "live" data; every metric shows freshness; every decision shows confidence + audit; country isolation enforced server-side; no direct balance editing anywhere in the admin UI; maker cannot approve own request; all financial bulk actions audited.

---

## 3.5 Stage 1 (Foundation) — DELIVERED ✅ (2026-08-31)

Pulled forward per executive decision; data-independent foundation is complete and tested.

**Built:**
- **Design tokens & theme** — `tailwind.config.js` (darkMode class strategy, semantic brand/status palette, glass shadows) + `resources/css/app.css` (RGB-triplet theme variables for light/dark, glass/panel/tone utilities, skeleton shimmer, reduced-motion support, command-center scrollbars).
- **Design system components** (`resources/views/components/kp/`): `icon` (curated heroicons set — single source), `glass-card`, `metric-card` (value + delta + sparkline + benchmark + target + risk badge + interpretation + action + freshness), `status-badge`, `risk-badge`, `health-indicator`, `section-header`, `alert-card` (P0–P3), `empty-state`, `skeleton`, `kbd`. No duplicated styles across pages (directive §69).
- **Command Center layout** (`resources/views/layouts/admin.blade.php`): glass top command bar (brand, env pill, country switcher, global search trigger, theme toggle, notifications/security/status/help/profile dropdowns with honest empty states, live clock), full §6 sidebar IA (9 groups, collapsible 264↔76px, unread/critical indicator slots, submenu accordions, P9 chips on not-yet-built sections), mobile drawer + bottom nav, command palette (Ctrl/⌘+K and `/`, ↑↓/Enter/Esc, permission note), g-key shortcuts (g o/f/a/s), skip-link + ARIA + focus-visible rings, data-freshness language everywhere.
- **Nav model** — `app/Support/AdminNav.php`: single source for sidebar/topbar-title/palette/bottom-nav (label, route, icon, permission, badge, critical, submenu, key).
- **RBAC Gate wiring** — `AppServiceProvider::wireRbacGates()`: superadmin wildcard + `role_permissions` matrix lookup (guarded by Schema::hasTable). Foundation for directive §13.4/§99 — routes keep server-side `auth` + `role:superadmin` middleware.
- **Legacy fix** — 7 admin Livewire components missing a `#[Layout]` attribute were 500-ing on the missing default `components.layouts.app`; all 17 admin pages now declare `layouts.admin` (verified: every route returns 200 in the new shell).

**Tests:** `tests/Feature/CommandCenterShellTest.php` (4 tests, 27 assertions) — shell chrome renders, all 17 admin routes render 200, non-superadmin → 403, guest → redirect. Full suite is now **68 passed / 201 assertions, zero failures** (was 7 failing pre-Stage-1, incl. 6 stock Breeze auth/profile tests rewritten against the real phone+OTP contracts).

**Preview:** `php artisan serve` running on port 8000 — log in with the dev-only sandbox superadmin (phone `08000000001` / password `SuperAdmin!2026` / OTP `123456`, which is the app's current temporary dev OTP). All data-dependent surfaces (KPIs, health score, live strip, risk, liquidity, reconciliation, providers) are honest empty states until their Phase 9 stages land on Phases 3–8 data.

**Still ahead (Stage 2 → 7)** per spec §14: metrics tables + aggregation jobs → KPIs/health score/live strip (2), financial/liquidity/settlement/reconciliation (3), network (4), risk (5), infrastructure (6), intelligence (7).

## 4. Immediate next steps (this week's execution)

1. Diagnose + fix the Phase 3 ConcurrencyTest (SQLite lock contention under 100 writers) — the only red test.
2. `php artisan migrate` + rollback smoke on Phase 2 migration set.
3. Begin Phase 4 Identity/RBAC (foundation for every dashboard permission check).
4. Stand up the Phase 9 metrics schema (`0008xx_*_metrics` migrations) + aggregation jobs early, since every dashboard card depends on them.
