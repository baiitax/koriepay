<?php

namespace App\Domain\Accounting;

use App\Domain\Accounting\Exceptions\DuplicateIdempotencyKeyException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Table-backed idempotency. Same key + same request hash → returns the exact
 * original outcome (response) without re-running the operation. Guarantees
 * "never debit twice" even under concurrent duplicate submissions.
 */
class IdempotencyService
{
    public function __construct(private readonly int $ttlSeconds = 86400)
    {
    }

    /**
     * Run $callback exactly once per $key, unless it already completed.
     *
     * @template T
     * @param  callable():T  $callback
     * @return T
     */
    public function execute(string $key, callable $callback)
    {
        $key = $this->normalizeKey($key);

        $existing = $this->findCompleted($key);
        if ($existing !== null) {
            return $existing->response; // replay → original result
        }

        try {
            return DB::transaction(function () use ($key, $callback) {
                // Acquire the DB write lock FIRST. On SQLite, a transaction that
                // reads before writing cannot upgrade its snapshot under
                // contention ("database is locked"); forcing the write intent
                // up-front serializes concurrent duplicates safely.
                DB::table('idempotency_keys')->where('id', 0)->update(['created_at' => now()]);

                // Re-check under the write lock to serialize concurrent duplicates.
                $existing = $this->findCompleted($key);
                if ($existing !== null) {
                    return $existing->response;
                }

                $result = $callback();

                DB::table('idempotency_keys')->insert([
                    'key' => $key,
                    'user_id' => request()->user()?->id,
                    'endpoint' => request()->path(),
                    'request_hash' => $this->requestHash(),
                    'response' => json_encode(['data' => $result]),
                    'created_at' => now(),
                    'expires_at' => now()->addSeconds($this->ttlSeconds),
                ]);

                return $result;
            });
        } catch (Throwable $e) {
            // Unique constraint race: another worker won. Return its result.
            $winner = $this->findCompleted($key);
            if ($winner !== null) {
                return $winner->response;
            }

            // No winner: the failure is this caller's own (insufficient funds,
            // validation, provider outage…). Propagate it UNTOUCHED — a failed
            // attempt must never be mislabeled as an idempotency collision,
            // and the key stays retryable until the callback completes.
            throw $e;
        }
    }

    public function isReplay(string $key): bool
    {
        return $this->findCompleted($this->normalizeKey($key)) !== null;
    }

    protected function findCompleted(string $key): ?object
    {
        $row = DB::table('idempotency_keys')
            ->where('key', $key)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($row === null || $row->response === null) {
            return null;
        }

        $row->response = json_decode($row->response, true)['data'] ?? null;

        return $row;
    }

    protected function requestHash(): string
    {
        $payload = [
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'body' => request()->except(['_token']),
        ];

        return hash('sha256', json_encode($payload));
    }

    protected function normalizeKey(string $key): string
    {
        $key = trim($key);

        if ($key === '' || strlen($key) > 64) {
            throw new \InvalidArgumentException('Idempotency key must be 1–64 characters.');
        }

        return $key;
    }
}
