<?php

namespace App\Domain\Customer;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * CUSTOMER BANKING — Stage 4 (transaction history).
 *
 * Read-side service for a customer's own transaction history with explicit,
 * validated filters (type, currency, from/to date, status, free-text q).
 * `find()` enforces ownership — a customer can only ever see their own rows;
 * anything else is a 404 (no existence leak). Status receipts are served by
 * the controller through TransactionReceiptService.
 */
class CustomerTransactionService
{
    /** @return array{type: string[], currency: string[], status: string[], date_from: ?string, date_to: ?string, q: ?string} */
    public function historyFilters(): array
    {
        return [
            'type' => ['deposit', 'withdraw', 'transfer', 'exchange', 'bill', 'airtime', 'data', 'refund', 'fee', 'reversal'],
            'currency' => ['XOF', 'NGN'],
            'status' => ['initiated', 'processing', 'authorized', 'posted', 'settled', 'failed', 'reversed'],
            'date_from' => null,
            'date_to' => null,
            'q' => null,
        ];
    }

    /**
     * Paginated, filtered history for one customer (newest first).
     *
     * @param  array{type?: string, currency?: string, from?: string, to?: string, status?: string, q?: string, per_page?: int}  $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<Transaction>
     */
    public function history(User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Transaction::query()
            ->where('sender_id', $user->id)
            ->latest('created_at');

        $this->applyFilters($query, $filters);

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }

    /**
     * Ownership-guarded lookup — returns the transaction ONLY when it belongs
     * to this customer, otherwise null (caller turns that into a 404).
     */
    public function find(User $user, string $reference): ?Transaction
    {
        return Transaction::query()
            ->where('reference', $reference)
            ->where('sender_id', $user->id)
            ->first();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if ($type = Arr::get($filters, 'type')) {
            $query->where('type', $type);
        }

        if ($currency = Arr::get($filters, 'currency')) {
            $query->where(function (Builder $q) use ($currency) {
                $q->where('source_currency', $currency)
                    ->orWhere('destination_currency', $currency);
            });
        }

        if ($status = Arr::get($filters, 'status')) {
            $query->where('status', strtolower((string) $status));
        }

        if ($from = Arr::get($filters, 'from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = Arr::get($filters, 'to')) {
            $query->where('created_at', '<=', $to);
        }

        if ($q = Arr::get($filters, 'q')) {
            $needle = strtolower(trim((string) $q));
            $query->where(function (Builder $inner) use ($needle) {
                $inner->where('reference', 'like', "%{$needle}%")
                    ->orWhere('description', 'like', "%{$needle}%")
                    ->orWhere('type', 'like', "%{$needle}%");
            });
        }
    }
}
