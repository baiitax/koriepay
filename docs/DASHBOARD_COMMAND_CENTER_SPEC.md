# KoriePay Super Admin Command Center — Build Specification

> **Source directive:** "KORIEPAY SUPER ADMIN COMMAND CENTER — Tier-1 Fintech Executive Intelligence, Risk, Operations & Decision Platform" (100-point brief).
> **Status:** Captured as the governing spec for **Phase 9 (Dashboards)** of the 12-phase rebuild. Not yet implemented.
> **Date captured:** 2026-08-31

---

## 0. Mission Statement

Rebuild the KoriePay Super Admin dashboard as an **Executive Intelligence Operating System** — not a conventional SaaS admin panel. A Tier-1 financial institution's digital command center where executives, risk officers, finance officers, compliance teams and technical operators understand the entire KoriePay ecosystem and make high-quality decisions from one interface.

**Operating loop:** OBSERVE → UNDERSTAND → DETECT → DECIDE → ACT.
Every major KPI must answer: current value · historical trend · change % · benchmark · target · risk status · business interpretation · recommended action.

**Final quality bar:** a Super Admin at 02:00 during a payment incident can answer WHAT? WHY? WHERE? WHO? HOW MUCH? HOW SERIOUS? WHAT HAPPENS NEXT? WHAT SHOULD I DO? — and act safely within seconds.

**Hard rule:** optimize for operations, not screenshots. Never show fake "live" data. Never present mock functionality as production.

---

## 1. Design Language

| Rule | Requirement |
|---|---|
| 1.1 | Glassmorphism UX system with **restrained** transparency — never excessive blur/glow |
| 1.2 | Deep neutral background + soft glass surfaces + subtle 1px borders + controlled blur + minimal gradients |
| 1.3 | High-contrast typography; premium fintech × institutional banking × modern African technology |
| 1.4 | Brand colors semantic only: Teal `#158987` (informational), Green `#29B475` (healthy/positive), Orange `#F88D25` (operational warning), Gold `#FCCB1A` (attention) |
| 1.5 | Red = critical, gray = neutral. **Never use color as the only status indicator** (icons/labels accompany) |
| 1.6 | Responsive: Desktop ≥1440 · Laptop 1024–1439 · Tablet 768–1023 (reorganized cards) · Mobile 320–767 (mobile ops command center, not shrunk desktop) |

## 2. Global Layout & Navigation

| Rule | Requirement |
|---|---|
| 2.1 | Top Command Bar + collapsible Sidebar (240–280px ↔ 72px) + content area |
| 2.2 | Sidebar groups: COMMAND CENTER · FINANCIAL · NETWORK · RISK & COMPLIANCE · PAYMENTS · OPERATIONS · ANALYTICS · SYSTEM · INFRASTRUCTURE (full item list in §6 of directive) |
| 2.3 | Every sidebar item: icon, label, tooltip, active state, unread indicator, critical-alert indicator, submenu, keyboard navigation (e.g. `Risk Center 🔴 7` → fraud/AML/high-risk-agents/high-risk-customers/held/investigations) |
| 2.4 | Top bar: ☰ · KoriePay · Environment: Production · Country: All | Global search (customer/agent/aggregator/transaction/reference/phone/account/KYC/case) | Country selector, currency, notifications, security alerts, system status, help, admin profile |
| 2.5 | **Global Country Switcher**: ALL / 🇳🇪 Niger / 🇳🇬 Nigeria — every section re-filters (currency, volume, revenue, agents, customers, providers, risk, settlement, liquidity). Never mix NGN and XOF without clear labeling |
| 2.6 | Mobile: drawer / bottom navigation / command menu; priority = critical alerts, decisions, financial snapshot, risk, live ops, approvals, system health |

## 3. Executive Overview (Homepage order — mandated)

1. Top command bar → 2. System status → 3. Executive health score → 4. Critical decisions → 5. Executive KPI grid → 6. Transaction intelligence → 7. Financial performance → 8. Liquidity intelligence → 9. Agent network intelligence → 10. Risk & fraud → 11. Payment provider health → 12. Settlement & reconciliation → 13. Live operations → 14. System infrastructure → 15. Recent admin activity.

- 3.1 KPI set: Total Transaction Volume (₦/CFA, daily/weekly/monthly/YTD) · Gross Transaction Value · Net Revenue · Active Customers · Active Agents · Active Aggregators · Platform Liquidity · Settlement Exposure · Transaction Success Rate · Fraud Exposure · Outstanding Reconciliation · System Health.
- 3.2 Each KPI card: value, trend sparkline, change %, benchmark, target, risk status, interpretation, recommended action, drill-down link. Hover = deeper context.
- 3.3 **Platform Health Score** (0–100, e.g. 94/100 HEALTHY) broken into Financial / Operational / Risk / Infrastructure / Network sub-scores; computed from real signals, not fabricated.
- 3.4 **Live operations strip** (● Payments, Transfers, Agent Network, Niger Rail, Nigeria Rail, Reconciliation, Notifications Operational) — from real backend health checks; never fake.
- 3.5 **Critical alerts always win**: DB failure, provider outage, liquidity crisis, major reconciliation mismatch, security incident, fraud spike render above analytics. Dynamic priority engine: P0 Critical / P1 High / P2 Important / P3 Informational.

## 4. Transaction Intelligence

- 4.1 Real-time transaction monitor stream: Time, Reference, Type, Customer/Agent, Country, Amount, Provider, Risk, Status (WebSockets/Reverb where available; **graceful polling fallback**).
- 4.2 Analytics: volume (hourly/daily/weekly/monthly), count, average size, success/failure/pending/reversal/refund rates, provider distribution, type distribution.
- 4.3 **Anomaly detection** (volume spike/decline, unusual amounts, abnormal agent/customer behavior, repeated failures/reversals, provider degradation, geographic/velocity anomalies). Each anomaly: severity, affected entity, risk, probable explanation, recommended action.

## 5. Financial & Liquidity Intelligence

- 5.1 Financial: GTV, Net Revenue, Transaction Fees, Agent Commissions, Aggregator Commissions, Operating Costs, Net Contribution; ratios (revenue/transaction, revenue/agent, revenue/customer, commission ratio, cost-to-income).
- 5.2 **Revenue waterfall** (interactive): Gross Fees → Provider Costs → Agent Commission → Aggregator Commission → Operational Costs → Net Platform Revenue; filters: country, date, type, provider.
- 5.3 **Liquidity Command Center**: Digital Float, Physical Cash Estimate, Settlement Liquidity, Agent Float, Aggregator Float, Platform Liquidity; each with Available / Reserved / Pending / At Risk / Expected.
- 5.4 **Liquidity map** (Niger/Nigeria by country→region→state→city→agent) with gap alerts; **liquidity forecast** (current, +6h, +24h, +7d) with shortage warnings and recommended funding actions.

## 6. Network Intelligence

- 6.1 Agent network: totals by status (active/inactive/suspended/pending/high-risk), performance cohorts (top, fastest growing, declining, dormant, high complaint/failure/fraud).
- 6.2 **Agent Performance Score** (performance, risk, liquidity, satisfaction, success rate, monthly volume).
- 6.3 Aggregators: totals, statuses, network size, GMV, revenue, override commission, risk exposure; ranked by volume/revenue/growth/retention/liquidity/risk.
- 6.4 Customers: totals, active/new/dormant/high-value/high-risk, KYC pending/approved; behavior (avg size, tx/customer, retention, 30/90-day active, CLV).

## 7. Risk, Compliance & Fraud

- 7.1 **Risk Command Center**: active alerts, critical alerts, held transactions, blocked accounts, fraud exposure, AML cases; risk heatmap by country/region/agent/customer/provider/type.
- 7.2 **Fraud intelligence**: multi-account-same-device, multi-agent-same-device, phone-pattern, location switching, rapid deposit→withdrawal, velocity, circular txs, repeated failures, night activity → risk score, reason, entities, exposure, action.
- 7.3 **Investigation graph** (customer ↔ device/agent/aggregator/account/transactions, expandable).
- 7.4 **Transaction Hold Center**: hold list with amount, customer, agent, risk score, reason, created, SLA, assigned officer; actions Review / Release / Reject / Reverse / Escalate (high-risk actions need proper authorization).
- 7.5 **AML intelligence**: suspicious activity, velocity, high-value, high-risk accounts, structuring, rapid-movement. **Never label "fraud" solely from a rule** — use "Risk Indicator / Investigation Required" until formally reviewed.
- 7.6 KYC/KYB Command Center: queue by type (customer/agent/aggregator/business), statuses (pending/approved/rejected/expired/high-risk/manual review), aging buckets (<1h, 1–6h, 6–24h, 24–72h, >72h), SLA-breach highlighting.

## 8. Payments, Settlement & Reconciliation

- 8.1 **Provider Command Center**: provider × country × rail, status, success rate, P95 latency, failure rate, volume, cost.
- 8.2 **Routing intelligence**: current traffic split, capacity, health, cost, success, latency; authorized admins may reconfigure routing (audited change).
- 8.3 Settlement: pending / settled today / failed / exposure / next settlement; breakdown provider/country/currency/date/amount/status.
- 8.4 **Reconciliation Command Center**: matched, unmatched, amount mismatch, duplicate, missing provider, missing internal, pending; **Reconciliation Health %** KPI; every mismatch drillable.

## 9. System Health & Infrastructure

- 9.1 Monitor: API, Database, Redis, Queues, Storage, WebSockets, Workers, Cron, Providers, Webhooks → latency, availability, error rate, throughput.
- 9.2 API monitoring: req/min, P50/P95/P99, errors, 5xx/4xx, timeouts; top failing endpoints (failure %, requests, latency).
- 9.3 Queues: pending/processing/failed/delayed/throughput per queue (payments, webhooks, notifications, reconciliation, reports, risk).
- 9.4 Webhooks: received/processed/failed/duplicate/delayed/invalid-signature; failure investigation UI.
- 9.5 **Incident Center**: P1–P4 severity, lifecycle (Detected→Acknowledged→Investigating→Mitigating→Resolved→Closed), affected services, financial impact, customers/transactions affected, team, timeline.
- 9.6 Business continuity: provider failure ⇒ immediately show "Provider Degraded", affected transactions/regions, estimated financial impact, alternative provider, routing recommendation.

## 10. Decision Engine & Insights

- 10.1 **Executive Decision Engine**: surfaces most important decisions (e.g. Niger liquidity exposure → expected shortage CFA 31M → recommended: fund 3 aggregators → estimated prevention CFA 74M → [Review] [Approve]).
- 10.2 Decision ranking: CRITICAL / HIGH / MEDIUM / LOW / INFORMATIONAL — weighted by financial, customer, risk, urgency, confidence, regulatory, operational impact.
- 10.3 **Executive Insights** (daily summary): "Today at a glance" (volume ↑12%, revenue ↑8%, agent activity ↓3%, Niger liquidity pressure ↑, Provider B failures ↑, fraud alerts ↓4%, reconciliation healthy) + TOP 3 ACTIONS.
- 10.4 Every recommendation may show **Confidence % + "Why?"** (data used, rule/model, timestamp, admin action, outcome). Never present probabilistic predictions as facts.
- 10.5 **Auditability of decisions**: store recommendation, data used, rule/model, confidence, timestamp, administrator action, outcome.
- 10.6 **Business benchmarking**: agent/country/region/aggregator/provider comparisons vs regional/country averages.

## 11. Operations UX

- 11.1 **Approval Center** (unified inbox): agent approval, aggregator approval, KYC approval, transaction reversal, settlement approval, manual adjustment, commission change, risk release, account unfreeze — each with requester, action, amount, reason, risk, created, SLA.
- 11.2 **Maker–Checker**: clear separation of "MY PENDING REQUESTS" vs "REQUESTS REQUIRING MY APPROVAL"; maker can never approve their own action.
- 11.3 **Audit Intelligence**: searchable/filterable audit logs (admin, action, module, country, IP, device, date, risk) with before/after diffs, reason, approver.
- 11.4 **Security Center**: failed logins, new devices, suspicious sessions, privilege changes, API key events, admin actions, security alerts; Security Health Score.
- 11.5 Admin profile: personal info, role, permissions, MFA, devices, sessions, login history, security events; logout-all-devices, revoke session, enable MFA, change password.
- 11.6 Session security: current + other sessions, device, location, IP, last active, revoke.
- 11.7 **Global filter engine** (persist across dashboards): country, currency, date, region, state, aggregator, agent, transaction type, provider, status, risk — active filters always visible.
- 11.8 **Time intelligence**: Today/Yesterday/7D/30D/90D/YTD/Custom; comparisons vs previous period/year/target/forecast.
- 11.9 **Drill-down principle**: every KPI clickable — Revenue → Country → Region → Aggregator → Agent → Transaction. Never force manual search.
- 11.10 **Command palette** (Ctrl/⌘+K): find agent/customer/transaction, open risk center, view reconciliation, suspend agent, open provider health, view settlement — permissions enforced. Keyboard-first ops: `/` search, `g o` overview, `g r` risk, `g f` finance, `g a` agents, `g s` system.
- 11.11 Notifications center: All/Critical/Financial/Risk/Security/Operations/System; severity, title, description, timestamp, source, action.
- 11.12 Mode switcher: **Executive** (GMV/revenue/profitability/customers/agents/liquidity/risk/health/decisions for CEO/board/CFO/COO), **Operations**, **Risk**, **Finance**, **System/Infrastructure**.

## 12. Data, Tables & Presentation

- 12.1 Visualization: line, area, bar, stacked bar, waterfall, heatmap, geo map, sparkline, distribution, risk matrix, network graph. **No 3D, no decorative charts, no chart junk, minimal pie.**
- 12.2 Enterprise tables: sort, filter, column selection, pagination, search, export, sticky headers, responsive, row expansion, bulk actions, saved views.
- 12.3 Bulk actions: approve/suspend/assign/export/review — financial bulk actions require confirmation + authorization + audit log.
- 12.4 Exports: CSV/Excel/PDF; permission-controlled, audited, **queued for large datasets**.
- 12.5 Empty states (meaningful) and skeleton/progress loading states everywhere. Human-readable errors with [Retry]; financial-critical errors say "Transaction status is being verified. Do not retry yet."
- 12.6 Design system: GlassCard, MetricCard, TrendCard, AlertCard, RiskBadge, StatusBadge, DataTable, ChartContainer, CommandBar, FilterBar, Modal, Drawer, Timeline, ActivityFeed, ApprovalCard, DecisionCard, HealthIndicator — **no duplicated component styles across pages**.
- 12.7 Micro-interactions: subtle, KPI/alert/status/drawer/modal/chart updates; **reduced-motion support**; never animate financial numbers excessively.
- 12.8 Accessibility: keyboard nav, screen readers, focus states, contrast, reduced motion, semantic HTML, ARIA labels, accessible charts.

## 13. Architecture, Security & Performance

- 13.1 **Dashboard APIs separate from transactional APIs**: `/api/v1/admin/dashboard|financial|risk|liquidity|network|providers|reconciliation|system-health` — never expose raw DB queries to the frontend.
- 13.2 **Precomputed analytics cache**: `daily_platform_metrics`, `hourly_transaction_metrics`, `agent_daily_metrics`, `aggregator_daily_metrics`, `country_daily_metrics`, `risk_daily_metrics`, `provider_hourly_metrics`, `liquidity_snapshots` (Redis for hot short-lived metrics).
- 13.3 **Data freshness** shown on every card ("Updated 12 seconds ago" / "Data as of 20:15 WAT"). Never imply real-time when metric is delayed.
- 13.4 **Never trust frontend authorization** — all permissions validated server-side (URL, body, JS, API params, organization_id, country_id, user_id manipulation must not grant access).
- 13.5 **Data isolation** per country/organization/role/permission; Country Admin never sees another country's data unless explicitly authorized.
- 13.6 **Financial data protection**: mask sensitive fields (`+234 803 *** 7812`); only privileged users reveal; every sensitive-field access recorded.
- 13.7 **No direct balance editing**: only "Create Adjustment Request" → Ledger Entry + Maker + Checker + Reason + Audit Event (never an "Edit Balance" control).
- 13.8 Super Admin security: MFA, device verification, session management, IP/session intelligence, reauthentication (step-up) for critical actions, role-based permissions, maker-checker, audit logging.
- 13.9 **Alert engine**: configurable thresholds (failure >3%, liquidity below threshold, provider latency, fraud risk, reconciliation mismatch, queue backlog) with escalation path (Alert → Admin → Manager → Risk Officer → Super Admin) per severity.
- 13.10 **System configuration** (countries, currencies, limits, fees, commissions, risk rules, KYC rules, providers, rails, notification/alert rules) — operational, not in source code; sensitive changes require maker + checker + reason + effective date + audit log.
- 13.11 Real-time events (Reverb/WebSockets): `transaction.created/completed/failed`, `risk.alert`, `provider.degraded`, `liquidity.warning`, `settlement.completed`, `reconciliation.mismatch`, `incident.created`, `security.alert`; graceful polling fallback.
- 13.12 Performance targets: initial render <2s; server-side aggregation, caching, Redis, lazy loading, code splitting, virtualized tables, background analytics; never load raw transaction history into the browser; designed for 100k+ customers, 10k+ agents, 1k+ aggregators, millions of transactions.

## 14. Implementation Strategy (per directive §96)

| Stage | Scope |
|---|---|
| 1 Foundation | Layout, sidebar, topbar, auth, RBAC, theme, responsive framework, design system |
| 2 Executive | KPIs, health score, critical decisions, executive insights |
| 3 Financial | Transactions, revenue, commission, liquidity, settlement, reconciliation |
| 4 Network | Agents, aggregators, customers, geography, performance |
| 5 Risk | Fraud, AML, KYC, alerts, investigations |
| 6 Infrastructure | API, queue, database, Redis, providers, webhooks |
| 7 Intelligence | Forecasting, anomaly detection, decision engine, benchmarks, predictive liquidity |

## 15. Security & Testing Acceptance (per directive §98–99)

- **Security acceptance:** no secrets, no exposed credentials, no insecure endpoints, no IDOR, no privilege escalation, no client-side authorization, no direct balance editing, no unprotected admin routes, no unverified webhooks, no sensitive logs.
- **Testing:** automated tests for dashboard authorization, country isolation, RBAC, KPI calculations, financial/risk/liquidity/provider/reconciliation metrics, admin actions, audit logs; lower-level administrators must not access Super Admin functions.

---

*This document is the governing spec for Phase 9. Implementation happens in Phase 9 per the 12-phase mandate, built on the real data layer delivered by Phases 3–8. See `DASHBOARD_CURRENT_STATE_AND_GAP.md` for the current-state audit and the gap-closure map.*
