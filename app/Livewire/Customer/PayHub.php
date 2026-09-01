<?php

namespace App\Livewire\Customer;

use App\Domain\Customer\CustomerTransferService;
use App\Domain\Customer\CustomerWalletService;
use App\Domain\Customer\Exceptions\CustomerBankingException;
use App\Domain\Customer\QrService;
use App\Models\CustomerWallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * CUSTOMER BANKING — Pay hub (§128 send + receive journeys).
 *
 * Send journey: recipient (KoriePay ID or phone) → server preview (fee +
 * total, no money moves) → confirm (idempotent, through the Phase 5 state
 * machine) → honest result (success | failed | processing).
 *
 * Receive journey: the customer's public identity + a real server-rendered
 * QR (koriepay:// payload). Scanning resolves the KoriePay ID.
 *
 * All validation and money decisions are server-side; the UI never computes
 * a fee or a rate.
 */
#[Layout('layouts.customer')]
class PayHub extends Component
{
    public string $step = 'hub';            // hub | send | receive | result
    public bool $showBalance = true;
    public string $recipient = '';
    public string $amount = '';
    public string $walletId = '';
    public string $note = '';
    public ?array $preview = null;
    public ?array $result = null;
    public ?string $error = null;
    public ?string $idempotencyKey = null;

    /** Deep-link: /customer/pay?view=send|receive (§128 short journeys). */
    #[Url]
    public ?string $view = null;

    public function mount(): void
    {
        $user = Auth::user();
        $selected = app(CustomerWalletService::class)->selectedWallet($user);
        $this->walletId = $selected?->wallet_id ?? '';

        if (in_array($this->view, ['send', 'receive'], true)) {
            $this->step = $this->view;
            $this->view = null;
        }
    }

    public function openSend(): void
    {
        $this->step = 'send';
        $this->error = null;
    }

    public function openReceive(): void
    {
        $this->step = 'receive';
        $this->error = null;
    }

    public function backToHub(): void
    {
        $this->step = 'hub';
        $this->error = null;
        $this->preview = null;
        $this->result = null;
        $this->idempotencyKey = null;
    }

    public function selectWallet(string $walletId): void
    {
        $this->walletId = $walletId;
        $this->preview = null;
        $this->error = null;
    }

    /** Server-side preview — no money moves, honest fee + total. */
    public function requestPreview(CustomerTransferService $transfers): void
    {
        $this->error = null;
        $this->preview = null;

        try {
            $wallet = $this->ownedWallet();
            $this->preview = $transfers->preview(
                Auth::user(),
                $wallet,
                $this->recipient,
                $this->amount,
            );
            $this->recipient = $this->preview['recipient_koriepay_id'] ?? $this->recipient;
        } catch (CustomerBankingException $e) {
            $this->error = $e->getMessage();
        }
    }

    /** Idempotent execution. Retry reuses the same key — never double-pays. */
    public function confirmSend(CustomerTransferService $transfers): void
    {
        $this->error = null;

        try {
            $wallet = $this->ownedWallet();
            $this->idempotencyKey ??= 'kp-web-'.Str::uuid()->toString();

            $transaction = $transfers->send(
                Auth::user(),
                $wallet,
                $this->recipient,
                $this->amount,
                $this->idempotencyKey,
                $this->note !== '' ? $this->note : null,
            );

            $this->result = $transfers->transactionPayload($transaction);
            $this->step = 'result';
            $this->preview = null;
        } catch (CustomerBankingException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render(CustomerTransferService $transfers, CustomerWalletService $wallets, QrService $qr)
    {
        $user = Auth::user();
        $walletList = collect($wallets->walletsFor($user));
        $selected = $walletList->firstWhere('wallet_id', $this->walletId) ?? $walletList->first();

        $receive = null;
        if ($this->step === 'receive' || $this->step === 'hub') {
            try {
                $receive = $transfers->receiveIdentity($user);
                $receive['qr_svg'] = $qr->svg($receive['qr_payload'], 240);
            } catch (CustomerBankingException) {
                $receive = null;
            }
        }

        return view('livewire.customer.pay-hub', [
            'walletList' => $walletList->all(),
            'selected' => $selected,
            'selectedDetails' => $selected !== null
                ? $wallets->balanceDetails($user, $selected)
                : null,
            'receive' => $receive,
        ]);
    }

    private function ownedWallet(): CustomerWallet
    {
        $wallet = CustomerWallet::where('wallet_id', $this->walletId)->first();

        if ($wallet === null || (int) $wallet->user_id !== (int) Auth::id()) {
            throw new CustomerBankingException('Wallet not found.');
        }

        return $wallet;
    }
}
