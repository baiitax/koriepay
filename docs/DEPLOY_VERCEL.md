# Deploying KoriePay to Vercel — Complete Guide

> **Version:** 1.0 · Applies to the `koriepay_rebuild` monolith (Laravel 12, Livewire 3, Vite, PHP 8.2+).
> **Bottom line:** Vercel runs this app as **one PHP serverless function** (`api/index.php`) behind their CDN. The Laravel kernel boots per request. Database, object storage and queues **must** live outside Vercel.

---

## Table of contents

1. [How it works — architecture on Vercel](#1-how-it-works)
2. [What the repo already contains](#2-what-the-repo-already-contains)
3. [Prerequisites](#3-prerequisites)
4. [Quick start (dashboard, ~10 min)](#4-quick-start-dashboard)
5. [Environment variables (every single one)](#5-environment-variables)
6. [External services — database](#6-database-neon-postgres)
7. [External services — object storage](#7-object-storage-documents--report-artifacts)
8. [Deploy from CLI (optional)](#8-deploy-from-cli)
9. [GitHub Actions CI](#9-github-actions-ci)
10. [Scheduled jobs (EOD backfill)](#10-scheduled-jobs)
11. [What works / what doesn't on serverless](#11-what-works-vs-not)
12. [Costs & limits](#12-costs--limits)
13. [Security checklist](#13-security-checklist)
14. [Troubleshooting](#14-troubleshooting)
15. [Rolling back](#15-rolling-back)

---

## 1. How it works

Vercel does **not** provide persistent PHP-FPM, a writable filesystem, or long-running workers. The community-maintained [`vercel-php`](https://github.com/vercel-community/php) runtime gives you PHP 8.2 inside a serverless function.

```
Browser ──▶ Vercel CDN ──▶ routes (vercel.json)
                              ├─ /build/*      → public/build/*   (Vite assets, static)
                              ├─ /favicon.ico  → public/favicon.ico
                              ├─ /robots.txt   → public/robots.txt
                              └─ /*            → api/index.php    (PHP serverless function)
                                                    └─ boots Laravel kernel
                                                       └─ handles the request like public/index.php
```

Every request boots the framework inside the function — that's why we cache compiled config/routes/views to `/tmp` and keep sessions stateless (cookie driver). The DB and file storage are external so state survives cold starts.

---

## 2. What the repo already contains

Everything marked ✅ is **already committed and ready** — you only need to configure the Vercel project + env vars.

| File | Purpose | Status |
|---|---|---|
| `api/index.php` | Serverless entrypoint — boots the Laravel kernel from `$_SERVER` globals (`Request::createFromGlobals()`), which is the pattern that works inside the runtime. | ✅ committed |
| `vercel.json` | Function config (`vercel-php@0.7.4`), route table, `/tmp` cache paths, serverless-safe env defaults. | ✅ committed |
| `.vercelignore` | Excludes `vendor/` (rebuilt on Vercel), `.env*`, sqlite, logs, tests. | ✅ committed |
| `.env.production.example` | Template for every Vercel environment variable with comments. | ✅ committed |
| `composer.json` → `scripts.vercel` | Runs `npm install` + `npm run build` on every deploy (Vite assets). | ✅ committed |
| `bootstrap/app.php` | `trustProxies(at: '*')` — correct HTTPS URL generation behind Vercel. | ✅ committed |
| `league/flysystem-aws-s3-v3` | S3-compatible storage for documents & report artifacts. | ✅ committed |
| `.github/workflows/ci.yml` | PHP 8.2/8.3 lint + PHPUnit on push/PR. | ✅ committed |
| `tests/Feature/VercelEntrySmokeTest.php` | Proves `api/index.php` boots and renders a page — CI keeps it honest. | ✅ committed |

> The app deliberately **never caches balances or authorizations** (ledger is authoritative, read directly every request). The `CACHE_STORE=array` default is therefore safe: derived read-model snapshots are labelled, and financial numbers are always computed live.

---

## 3. Prerequisites

1. A **GitHub** account and a repo containing this project (see the push instructions below).
2. A **Vercel** account (free tier is enough to start) — vercel.com.
3. A **Neon** account (free Postgres) — neon.tech. (Any Postgres/MySQL host works; Neon is serverless-friendly.)
4. **Object storage** — Cloudflare R2 (free tier) or AWS S3. Needed for the Document Center + Report artifacts because the Vercel filesystem is ephemeral.
5. Optionally the **Vercel CLI** for local `vercel dev` / manual deploys: `npm i -g vercel`.

---

## 4. Quick start (dashboard)

### Step 1 — Push to GitHub

```bash
cd koriepay_rebuild
git init
git add .
git commit -m "KoriePay: aggregator console + banking engine (Stages A–I)"
git branch -M main
git remote add origin https://github.com/<YOU>/koriepay_app.git   # or a new repo
git push -u origin main
```

### Step 2 — Import into Vercel

1. Go to **vercel.com → Add New → Project**.
2. Click **Continue with GitHub** and authorize the repo you just pushed.
3. Vercel auto-detects the project. Set:
   - **Framework Preset:** `Other`
   - **Build Command:** leave empty — the `composer.json` `vercel` script (npm install + npm run build) runs automatically via the PHP runtime.
   - **Output Directory:** `public`
4. Add the environment variables from [§5](#5-environment-variables) (at minimum `APP_KEY`, `DB_*`, and the storage keys).
5. Click **Deploy**.

### Step 3 — Verify

- `https://<project>.vercel.app/up` should return `{"status":"ok"}` (Laravel health route).
- `https://<project>.vercel.app/login` should render the login page.
- Log in with a seeded account, open `/aggregator/dashboard` — every number must come back live from the database.

> **First deploy gotcha:** the build needs a moment to `composer install` + `npm run build`. A `504`/timeout on the very first deploy is normal; watch **Builds → Logs** in the project dashboard.

---

## 5. Environment variables

Set these in **Vercel → Project → Settings → Environment Variables** (add for `Production`, `Preview`, and `Development`).

### Required — app

| Variable | Value / how to get it |
|---|---|
| `APP_KEY` | `php artisan key:generate --show` → paste the full `base64:…` string. **Never reuse the dev `.env` key.** |
| `APP_URL` | `https://<your-project>.vercel.app` (or your custom domain) |
| `APP_DEBUG` | `false` |
| `APP_ENV` | `production` (already defaulted in vercel.json) |

### Required — database (Neon)

| Variable | Value |
|---|---|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | your `ep-….neon.tech` host |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `neondb` (Neon default DB name) |
| `DB_USERNAME` | your Neon user |
| `DB_PASSWORD` | your Neon password |
| `DB_SSLMODE` | `require` |

Run migrations **once**, from your machine or CI, against the Neon DB:

```bash
composer install
php artisan migrate --force \
  --env=production \
  --database=pgsql \
  # with DB_* env vars exported
```

> ⚠️ **Do not run `migrate:fresh` against production** — it drops data. The migration set is additive and re-runnable; use `php artisan migrate --force`.

### Required — object storage (Cloudflare R2 example)

| Variable | Value |
|---|---|
| `FILESYSTEM_DISK` | `s3` |
| `AWS_ACCESS_KEY_ID` | your R2 access key id |
| `AWS_SECRET_ACCESS_KEY` | your R2 secret key |
| `AWS_DEFAULT_REGION` | `auto` (R2 ignores region) |
| `AWS_BUCKET` | your bucket name, e.g. `koriepay-artifacts` |
| `AWS_ENDPOINT` | `https://<account-id>.r2.cloudflarestorage.com` |
| `AWS_URL` | your public R2 dev URL (optional, for direct file URLs) |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` |

The Document Center and Report generation read/write via `Storage::disk(config('filesystems.default'))` — the code is already disk-agnostic (no hardcoded `local`), so switching to S3/R2 is purely config.

### Already handled (do not override unless you know why)

| Variable | Value in vercel.json | Why |
|---|---|---|
| `APP_CONFIG_CACHE` / `APP_EVENTS_CACHE` / `APP_PACKAGES_CACHE` / `APP_ROUTES_CACHE` / `APP_SERVICES_CACHE` / `VIEW_COMPILED_PATH` | `/tmp/…` | Vercel functions get a writable, ephemeral `/tmp` — compiled artifacts go there instead of `storage/` |
| `SESSION_DRIVER` | `cookie` | Stateless; no session table/files needed. Cookie is signed with `APP_KEY` |
| `CACHE_STORE` | `array` | Per-request only. Balances/authorizations are never cached anyway |
| `QUEUE_CONNECTION` | `sync` | Jobs run inline (see §11 for the scaling caveat) |
| `LOG_CHANNEL` | `stderr` | Appears in Vercel function logs |

---

## 6. Database (Neon Postgres)

1. Sign up at **neon.tech** → **Create project** (region near your users, e.g. `Frankfurt (eu-central-1)`).
2. Copy the connection string: `postgresql://user:pass@ep-xxxx.eu-central-1.aws.neon.tech/neondb?sslmode=require`.
3. Fill the `DB_*` vars in Vercel (split the string into host/port/db/user/password, `sslmode=require`).
4. Run the migrations from a local shell or CI once:

```bash
export DB_CONNECTION=pgsql DB_HOST=... DB_PORT=5432 DB_DATABASE=neondb \
       DB_USERNAME=... DB_PASSWORD=... DB_SSLMODE=require
php artisan migrate --force
```

The schema is database-agnostic (Schema builder + `enum()` columns, which Laravel maps to `varchar` + check constraints on Postgres) — no raw SQL to patch.

**Backups:** Neon free tier auto-snapshots for 7 days. Enable **point-in-time restore** if you need more.

---

## 7. Object storage (documents & report artifacts)

Why: the Vercel filesystem is **read-only at runtime except `/tmp`**, and `/tmp` is wiped on every cold start. Storing user documents / generated reports on the function disk would lose files between requests.

The app is already wired to use `config('filesystems.default')` everywhere (`AggregatorDocumentsService`, `AggregatorReportsService`, download routes) — no code change needed.

**Cloudflare R2 (recommended, free):**
1. Cloudflare dashboard → **R2** → create bucket `koriepay-artifacts`.
2. Create an **API token** with `Object Read & Write` on that bucket → copy Access Key ID + Secret.
3. Fill the `AWS_*` vars from §5.
4. (Optional) enable a public `r2.dev` URL and set `AWS_URL` for direct links.

**AWS S3:** same variables without the endpoint; `AWS_DEFAULT_REGION` = your bucket region; consider a bucket policy restricting access to CloudFront/Vercel IPs and private ACLs — the app streams downloads through the API (authorization enforced), it does not rely on public bucket URLs.

---

## 8. Deploy from CLI (optional)

```bash
npm i -g vercel
vercel login

# first time: link the project
vercel link --yes --project koriepay-app

# preview deployment
vercel --env APP_KEY=base64:... --env DB_HOST=...   # or rely on dashboard env vars
vercel --prod
```

`vercel dev` also works locally with the PHP runtime if you have Docker: it boots `api/index.php` and applies the same route table — a great way to validate the serverless entrypoint before pushing.

---

## 9. GitHub Actions CI

The repo ships `.github/workflows/ci.yml`:

- **test** — PHP 8.2 + 8.3 matrix: composer install, `.env` bootstrap, `php artisan test` against SQLite (mirrors `phpunit.xml`), with package cache.
- **lint** — `php -l` across `app routes database bootstrap config tests api`.

It runs on push to `main`/`develop` and on PRs to `main`. This is your safety net: every PR must pass 286 tests (including `VercelEntrySmokeTest`) before deploy.

**Optional — deploy from CI:** add secrets `VERCEL_TOKEN`, `VERCEL_ORG_ID`, `VERCEL_PROJECT_ID` (from `.vercel/project.json`) and a `deploy` job:

```yaml
- name: Deploy to Vercel
  run: npx vercel deploy --prod --token=${{ secrets.VERCEL_TOKEN }}
  env:
    VERCEL_ORG_ID: ${{ secrets.VERCEL_ORG_ID }}
    VERCEL_PROJECT_ID: ${{ secrets.VERCEL_PROJECT_ID }}
```

> If you deploy from CI, disable Vercel's automatic git deployment in Project → Settings → Git to avoid double deploys.

---

## 10. Scheduled jobs (EOD backfill)

Vercel does not run cron on the server, but it does offer **Cron Jobs** (vercel.json `crons` key, requires paid plan) which call a function endpoint on a schedule. For the EOD read-model backfill you can:

1. Add an internal endpoint, e.g. `Route::get('/api/cron/eod', …)` in `routes/api.php`, protected by a secret `CRON_TOKEN` query check, that calls `AggregatorInsightsService::backfill(...)`.
2. Register the cron:
```json
"crons": [{ "path": "/api/cron/eod?token=SECRET", "schedule": "0 23 * * *" }]
```
3. Or — free alternative — a **GitHub Actions scheduled workflow** (`schedule: cron '0 23 * * *'`) that curls the same endpoint, or a third-party cron (cron-job.org) hitting the URL.

The insights/EOD UI also offers a manual **"Write read-model snapshot"** button (gated by the `network.analytics` permission), so nothing breaks if you skip cron initially — the dashboard falls back to live computation and labels it honestly.

---

## 11. What works vs. not (honest list)

| Feature | On Vercel serverless | Notes |
|---|---|---|
| Pages, Livewire 3 components, forms, validation | ✅ | Stateless via cookie sessions; polling works (each poll = one function call) |
| Ledger, commissions, settlements, support, documents, reports | ✅ | All DB-backed (Neon) + S3 for artifacts |
| File **upload** (documents) | ✅ | Uploaded bytes go straight to S3 (must stay under the 4.5 MB request body limit for hobby) |
| File **download** (stream) | ✅ | Streamed from S3 through the API with authorization + audit |
| Background/queue jobs | ⚠️ | `QUEUE_CONNECTION=sync` → jobs run **inside the request**. Fine for reports on small data; for heavy PDFs or many concurrent requests, move to an external queue (SQS/Redis) + a worker outside Vercel |
| WebSocket / Echo / broadcasting | ❌ | Needs a separate service (Pusher/Ably) — the app doesn't depend on it |
| Long-running processes, `queue:work`, `schedule:run` | ❌ | Not supported — use cron endpoints (§10) or external workers |
| Local `storage/app` persistence | ❌ | Ephemeral — that's why documents/reports use S3 |
| Writing the sqlite file | ❌ | Dev-only; production uses Neon |

---

## 12. Costs & limits (as of the free tier)

| Item | Limit |
|---|---|
| Function execution (hobby) | 60 s max per request, 100 GB‑hr/mo |
| Function memory | 1024 MB max |
| Request body (hobby) | 4.5 MB — document uploads above this need a direct-to-S3 signed upload flow |
| Deployment bundle | PHP runtime + vendor is several hundred MB uncompressed; Vercel handles it, but keep `node_modules` and `vendor` out of the deployment (already excluded) |
| Function cold starts | PHP boot ≈ 200–600 ms — cached config + `/tmp` artifacts keep it fast; warmers or Pro plan reduce it |
| Neon free | 0.5 GB storage, ~190 compute-hours/mo — fine for demo; scale to paid for production |
| R2 free | 10 GB storage, 1M Class A reads/mo |

> Paid plan (Pro, $20/mo) removes the 60 s cap (300 s) and unlocks Cron Jobs.

---

## 13. Security checklist

- [ ] `APP_DEBUG=false` and a **fresh** `APP_KEY` in production (never the dev key).
- [ ] `.env` is gitignored; only `.env.example` / `.env.production.example` are committed.
- [ ] `DB` credentials, `AWS_*` keys are Vercel env vars — never in the repo or `vercel.json`.
- [ ] HTTPS enforced by Vercel automatically; `trustProxies('*')` makes `secure()`/redirects correct.
- [ ] CSRF enabled (default) with webhook exceptions scoped to HMAC-signed routes only.
- [ ] RBAC/permissions enforced server-side (role + permission middleware) — unchanged on serverless.
- [ ] Ledger entries immutable; privileged ops audited — all DB-backed, so nothing lost on cold starts.
- [ ] Object storage bucket is **private**; downloads flow through the authorized API.
- [ ] `APP_URL` matches the production domain so signed URLs/redirects are right.

---

## 14. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| `404` on `/build/assets/…` | Vite assets not built/deployed | Run `npm run build` locally, verify `public/build` exists in the deploy; check the `vercel` composer script ran |
| `502`/`504` on first deploy | Build still installing PHP deps | Wait, check Build Logs; retry deploy |
| `Connection refused` to DB | Neon paused / wrong `DB_HOST` | Neon free pauses after inactivity — wake it in dashboard; double-check env vars |
| Login works but dashboard numbers are wrong/zero | DB empty | Run `php artisan migrate --seed --force` against Neon once, or load your seed data |
| Uploads/downloads of documents fail | Storage not configured | Verify `AWS_*` vars + bucket name; check function logs for S3 errors |
| `Session store not set on request` | `SESSION_DRIVER` overridden to `database`/`file` | Set `SESSION_DRIVER=cookie` in env |
| Slow first load | Cold start | Cached config via `/tmp` env vars; consider Pro + a warmer |
| `Too many requests` on Livewire polling | Aggressive polling on free tier | Reduce polling intervals on the dashboard charts |
| `SignatureV4Exception` (S3) | Wrong region/endpoint for R2 | R2 uses `AWS_DEFAULT_REGION=auto` + `AWS_ENDPOINT` |

Function logs: **Vercel → Project → Functions → (deployment) → Logs**, or `vercel logs`.

---

## 15. Rolling back

- **Instant:** Vercel keeps every successful deployment — **Deployments → ⋯ → Promote to Production** on any previous one.
- **Code:** revert the commit and push; CI runs tests; Vercel redeploys.
- **Database:** restore a Neon snapshot (free: 7-day). Never `migrate:rollback` against production unless you know the migration is the last one and reversible.

---

### Appendix — files changed for Vercel readiness

```
api/index.php                              new — serverless entrypoint
vercel.json                                new — functions, routes, env defaults
.vercelignore                              new — deployment excludes
.env.production.example                    new — env template
bootstrap/app.php                          edited — trustProxies(at: '*')
composer.json                              edited — scripts.vercel (asset build)
composer.lock                              edited — + league/flysystem-aws-s3-v3
.github/workflows/ci.yml                   new — lint + PHPUnit matrix
tests/Feature/VercelEntrySmokeTest.php     new — boots api/index.php in tests
app/Domain/Aggregator/*Service.php         edited — disk('local') → default disk
routes/web.php                             edited — storage via default disk
```
