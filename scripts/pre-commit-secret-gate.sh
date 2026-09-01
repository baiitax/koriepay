#!/usr/bin/env bash
# KoriePay pre-commit secret gate (Phase 0).
# Blocks commits containing high-signal secret patterns before they reach history.
# Works standalone (no install needed) — hook runs via .git/hooks/pre-commit.
set -uo pipefail

FAIL=0

# High-signal patterns (first line = readable name)
patterns=(
  "GitHub recovery codes|github[-_ ]?recovery[-_ ]?codes"
  "SQL dump|phpMyAdmin SQL Dump"
  "Live secret key|sk_live_[A-Za-z0-9]{16,}"
  "Hardcoded APP_KEY|APP_KEY[= ]+base64:[A-Za-z0-9+/=]{40,}"
  "Hardcoded webhook secret|(webhook|gateway)_secret[= ]+['\"][^'\"]{6,}['\"]"
  "Paystack secret|pk_live_|sk_live_"
  "DB password literal|DB_PASSWORD[= ]+['\"][^'\"]{3,}['\"]"
)

for entry in "${patterns[@]}"; do
  name="${entry%%|*}"
  regex="${entry#*|}"
  while IFS= read -r file; do
    if grep -qE "$regex" "$file"; then
      echo "⛔ [secret-gate] $name detected in: $file"
      FAIL=1
    fi
  done < <(git diff --cached --name-only --diff-filter=ACM | grep -Ev '\.(md|lock)$' || true)
done

# Block known bad filenames
while IFS= read -r file; do
  if echo "$file" | grep -qiE '(recovery.?codes|\.sql$|\.env\.(prod|production)$|\.pem$|\.key$|id_rsa|\.p12$|\.pfx$|\.jks$)'; then
    echo "⛔ [secret-gate] forbidden artifact staged: $file"
    FAIL=1
  fi
done < <(git diff --cached --name-only --diff-filter=ACM || true)

if [ "$FAIL" -ne 0 ]; then
  echo "Commit blocked. Remove the flagged files/secrets and retry."
  exit 1
fi
echo "✅ secret-gate: clean"
exit 0
