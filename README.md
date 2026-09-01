Here is the complete, production-grade **`README.md`** file tailored for your GitHub repository ([`https://github.com/baiitax/koriepay_app`](https://github.com/baiitax/koriepay_app)).

You can copy and paste this directly into the root `README.md` of your project.

---

# KoriePay: Tier-1 Cross-Border Liquidity Engine & Agency Banking Platform

**Repository URL:** [https://github.com/baiitax/koriepay_app](https://github.com/baiitax/koriepay_app)

---

## 📌 Executive Overview

**KoriePay** is an enterprise-grade B2B2C liquidity engine and agency banking network engineered specifically for the West African Sahel trade corridor—connecting **Nigeria (NGN)** and **Niger / WAEMU region (XOF)**.

Unlike traditional consumer payment applications, KoriePay targets high-volume liquidity drivers:

1. **Bureau De Change (BDC) Clearing Desks:** Facilitating high-value, guaranteed rate-locked multi-currency settlements between commercial hubs (Kano, Katsina, Maradi, Niamey).
2. **Retail POS & Web Agency Network:** Enabling physical cash-in/cash-out liquidity distribution, offline cryptographic token redemption, and utility payments via mobile terminals and web desktop portals.
3. **Institutional Command Center:** Operating stateful risk management, Maker/Checker transaction hold pipelines, triple-entry accounting, and real-time bank reconciliation.

---

## 🏗 System Architecture & Monorepo Structure

KoriePay is built as a **Polyglot Monorepo Architecture**, isolating high-frequency transactional data loops from high-velocity web portals while maintaining a single source of truth across Git commits.

```
koriepay_app/
├── .github/              # Automation workflows & CI/CD deployment pipelines
├── backend-core/         # Core Financial API Engine (Laravel Octane 11 + FrankenPHP)
├── admin-portal/         # Agency Web Terminal & HQ Command Center (Next.js 14+ App Router)
├── mobile-client/        # Retail POS & Consumer Client Application (Flutter / Dart)
└── docker-compose.yml    # Local container orchestration (PostgreSQL 17 + Redis 7.2)

```

```
 NIGERIA (NGN Rails)                                             NIGER / WAEMU (XOF Rails)
┌──────────────────┐      REST API      ┌──────────────────┐      REST API      ┌──────────────────┐
│  PROVIDUS BANK   ├───────────────────►│  KORIEPAY CORE   │◄───────────────────┤   WAEMU BANK     │
│ (Virtual NUBANs) │◄───────────────────┤ ENGINE (Laravel) │───────────────────►│ (GIM-UEMOA/RTGS) │
└──────────────────┘   Webhooks (HMAC)  └────────┬─────────┘   Webhooks (HMAC)  └──────────────────┘
                                                 │
                                                 │ WebSockets (Laravel Reverb)
                                                 ▼
                        ┌─────────────────────────────────────────────────┐
                        │              HYBRID USER TERMINALS              │
                        ├────────────────────────┬────────────────────────┤
                        │    BDC & WEB AGENT     │      SUPER ADMIN       │
                        │  (Next.js / Flutter)   │  (Next.js Dashboard)   │
                        └────────────────────────┴────────────────────────┘

```

---

## 🛠 Technology Stack

| Ecosystem Tier | Technology Choice | Strategic Engineering Role |
| --- | --- | --- |
| **Backend API Core** | `Laravel Octane 11` + `FrankenPHP` | Go-like request throughput, persistent stateful workers, and native job queue management. |
| **Agency & Admin Web** | `Next.js 14+` (TypeScript, Tailwind) | Server-Side Rendered (SSR) portal featuring role-based smart rerouting and hotkey navigation. |
| **Mobile Client App** | `Flutter` (Dart) | Direct-to-ARM compilation running smoothly on low-spec Sahel Android smartphones. |
| **Relational Database** | `PostgreSQL 17` (Supabase PG) | Enforces atomic ACID transactions and strict `NUMERIC(20, 4)` precision against floating-point errors. |
| **Concurrency Guard** | `Redis Stack 7.2` (Upstash Serverless) | Atomic distributed locking (`SET NX`) preventing race conditions and double-spending attacks. |
| **Real-Time Data** | `Laravel Reverb` | High-concurrency WebSocket server pushing instant wallet updates directly to frontends. |

---

## 💡 Key Solution Features

* **15-Minute Guaranteed FX Rate Lock:** BDC operators can lock live parallel market rates for 15 minutes via Redis-backed TTL keys (`fx_lock:user_id:rate_hash`), neutralizing exchange rate slippage.
* **Smart Web Agent Rerouting:** Unified login gateway (`/login`) inspects incoming user JWT roles and automatically routes registered agents directly to the **Web Agency Terminal** (`/agent/terminal`).
* **Dual Wallet Accounting:** Strict database-level segregation between an agent's working capital (`Float Wallet`) and earned profits (`Commission Wallet`).
* **Offline Cryptographic Cash-Out:** Allows offline cash withdrawals in remote border zones using encrypted QR tokenization, verified against local client SQLite caches until connectivity restores.
* **Stateful Risk & "Hold" Engine:** Automatic interception of anomalous transactions into a `Hold` state, requiring dual-tier authorization (**Maker/Checker Pipeline**) before ledger clearance.
* **Triple-Entry Accounting:** Every balance movement generates three distinct records (Debit, Credit, System Ledger) accompanied by an immutable HMAC-SHA256 hash.

---

## 🚀 Deploying to Vercel

KoriePay runs on Vercel as a single PHP serverless function (`api/index.php`) via the
`vercel-php` runtime — with Neon Postgres for data and Cloudflare R2/S3 for document
& report artifacts. All deployment files (`vercel.json`, `api/index.php`,
`.vercelignore`, `.env.production.example`, CI) are already in the repo.

**➡️ Full step-by-step guide: [`docs/DEPLOY_VERCEL.md`](docs/DEPLOY_VERCEL.md)**

Quick summary:
1. Push the repo to GitHub.
2. Import into Vercel (Framework: *Other*, Output directory: `public`).
3. Add env vars: `APP_KEY`, `DB_*` (Neon Postgres), `AWS_*` (R2/S3) — see `.env.production.example`.
4. Run `php artisan migrate --force` against the production DB once.
5. Deploy → verify `/up` and `/login`.

> Serverless notes: sessions are cookie-driven, queues run synchronously, cron
> is done via Vercel Cron endpoints or a GitHub Actions schedule, and compiled
> caches go to `/tmp`. Balances/authorizations are never cached anywhere.

---

## 🎨 Design System & Visual Tokens

The platform implements the official KoriePay visual identity system using **Host Grostek** typography and high-contrast palette tokens optimized for high-glare environments.

| Color Name | RGB Token | HEX Code | UI Role |
| --- | --- | --- | --- |
| **Pale Blue** | `RGB(21, 137, 135)` | `#158987` | Primary brand identity, active focus rings, submit actions. |
| **Korie Green** | `RGB(41, 180, 117)` | `#29B475` | Settled transaction states, active floats, positive balances. |
| **Burnt Orange** | `RGB(248, 141, 037)` | `#F88D25` | System holds, rate lock timers, low-float warnings. |
| **Lemon** | `RGB(252, 203, 026)` | `#FCCB1A` | Dynamic processing indicators and active rate banners. |

---

## 🚀 Local Development Setup Guide

### 1. Prerequisites

Ensure you have the following installed on your host machine:

* [Docker Desktop](https://www.docker.com/) & Docker Compose
* [Node.js 20+](https://nodejs.org/) & `npm`
* [PHP 8.3+](https://www.php.net/) & [Composer](https://getcomposer.org/)
* [Flutter SDK](https://docs.flutter.dev/get-started/install) (Optional for mobile development)

### 2. Repository Initialization

Clone the repository to your local machine:

```bash
git clone https://github.com/baiitax/koriepay_app.git
cd koriepay_app

```

### 3. Start Local Database Infrastructure (Postgres + Redis)

Spin up the local containerized environment:

```bash
docker compose up -d

```

* **PostgreSQL 17** running at `127.0.0.1:5432` (`DB: koriepay_local`)
* **Redis 7.2** running at `127.0.0.1:6379`

### 4. Setup Backend Core (Laravel API Engine)

```bash
cd backend-core

# Install PHP dependencies
composer install

# Configure environment file
cp .env.example .env

# Generate application security key
php artisan key:generate

# Run database migrations and seeders
php artisan migrate --seed

# Start the Laravel Octane development server
php artisan octane:start --port=8000

```

The Core API will be available at `http://localhost:8000`.

### 5. Setup Web Agency & Admin Portal (Next.js)

Open a new terminal window:

```bash
cd admin-portal

# Install Node dependencies
npm install

# Start Next.js development server
npm run dev

```

The Web Portal will be available at `http://localhost:3000`.

* **Unified Login Gateway:** `http://localhost:3000/login`
* **Web Agency Terminal:** `http://localhost:3000/agent/terminal` (Auto-redirected for Agent roles)

---

## 🧪 Testing & Verification

* **286 tests / 1,172 assertions green** — `php artisan test` (SQLite in-memory, sync queue, array cache).
* `tests/Feature/VercelEntrySmokeTest.php` proves the serverless entrypoint boots the app.
* CI (`.github/workflows/ci.yml`): PHP 8.2/8.3 lint + PHPUnit matrix on every push/PR.

---

Execute backend unit, integration, and accounting precision tests:

```bash
cd backend-core

# Run Pest/PHPUnit Test Suite
php artisan test

# Verify Redis Distributed Lock Integrity
php artisan test --filter=DistributedLockTest

```

---

## 🔒 Security & Compliance

* **Device Binding:** Accounts cryptographically bound to hardware IMEI/UUID signatures.
* **Webhook Signature Guard:** All inbound bank hooks verified via HMAC-SHA512 time-attack safe execution (`hash_equals`).
* **Idempotency Gate:** Webhooks deduplicated using atomic Redis keys with a 24-hour expiration window.

---

## 📄 License & Proprietary Notice

**Copyright © 2026 KoriePay Technologies Ltd. All Rights Reserved.**

This repository contains highly confidential and proprietary source code. Unauthorized copying, distribution, or reverse engineering of any portion of this codebase via any medium is strictly prohibited without explicit written authorization from KoriePay executive management.
