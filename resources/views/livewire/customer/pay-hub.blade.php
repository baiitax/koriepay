{{-- CUSTOMER BANKING — Pay hub (§128 send/receive journeys).
     Mobile-first, glass, honest states: preview before money moves, idempotent
     confirm, explicit success / failed / processing outcomes. --}}

<div class="mx-auto w-full max-w-md px-4 pt-safe sm:px-6">

    <header class="flex items-center justify-between py-5">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">KoriePay</p>
            <h1 class="text-xl font-black text-ink">Pay</h1>
        </div>
        @if ($step !== 'hub')
            <button type="button" wire:click="backToHub" class="inline-flex items-center gap-1 rounded-xl border border-line bg-panel/80 px-3 py-2 text-xs font-bold text-muted hover:text-ink">
                <x-kp.icon name="chevron-right" class="h-3.5 w-3.5 rotate-180" /> Back
            </button>
        @endif
    </header>

    {{-- ═══ HUB ═════════════════════════════════════════════════════════ --}}
    @if ($step === 'hub')
        <div class="grid grid-cols-2 gap-3">
            <button type="button" wire:click="openSend"
                    class="panel group p-5 text-left transition-all hover:border-brand/40 active:scale-[0.98]">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand text-white shadow-glass transition-transform group-active:scale-90">
                    <x-kp.icon name="arrow-up-right" class="h-5 w-5" />
                </span>
                <span class="mt-3 block text-sm font-black text-ink">Send</span>
                <span class="mt-0.5 block text-xs text-muted">To a KoriePay ID or phone</span>
            </button>

            <button type="button" wire:click="openReceive"
                    class="panel group p-5 text-left transition-all hover:border-brand/40 active:scale-[0.98]">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl border border-line bg-panel/80 text-ink transition-transform group-active:scale-90">
                    <x-kp.icon name="arrow-down-right" class="h-5 w-5" />
                </span>
                <span class="mt-3 block text-sm font-black text-ink">Receive</span>
                <span class="mt-0.5 block text-xs text-muted">Share your QR or KoriePay ID</span>
            </button>
        </div>

        @if ($receive)
            <section aria-label="Your receive identity" class="glass-inset mt-6 rounded-2xl p-4">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Your KoriePay ID</p>
                <p class="mt-1 font-mono text-lg font-bold tracking-tight text-ink">{{ $receive['koriepay_id'] }}</p>
                <button type="button" onclick="navigator.clipboard?.writeText('{{ $receive['koriepay_id'] }}'); this.textContent='Copied!'"
                        class="mt-2 rounded-lg border border-line bg-panel px-3 py-1.5 text-[11px] font-bold text-muted hover:text-ink">Copy ID</button>
            </section>
        @endif
    @endif

    {{-- ═══ SEND ════════════════════════════════════════════════════════ --}}
    @if ($step === 'send')
        @if ($preview === null)
            {{-- Step 1 — intent --}}
            <form wire:submit.prevent="requestPreview" class="space-y-4">
                @if ($error)
                    <x-kp.alert-card severity="high" icon="exclamation-triangle" title="Cannot continue" subtitle="{{ $error }}" />
                @endif

                @if ($selected && $selectedDetails)
                    <section aria-label="From" class="panel p-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">From</p>
                        <div class="mt-2 flex gap-2">
                            @foreach ($walletList as $w)
                                <button type="button" wire:click="selectWallet('{{ $w->wallet_id }}')"
                                        class="flex-1 rounded-xl border px-3 py-2 text-left transition-colors {{ $selected->wallet_id === $w->wallet_id ? 'border-brand/50 bg-brand/10' : 'border-line bg-panel-2/60' }}">
                                    <span class="block text-[9px] font-black uppercase tracking-widest text-muted">{{ $w->currency_code }}@if ($w->is_primary) · Main @endif</span>
                                    <span class="block text-xs font-bold text-ink">{{ $showBalance ? number_format((float) $w->available_balance, $selectedDetails['minor_units']) : '••••' }}</span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section aria-label="To" class="panel p-4 space-y-3">
                    <div>
                        <label for="recipient" class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Recipient</label>
                        <input id="recipient" type="text" wire:model="recipient" required autocomplete="off" spellcheck="false"
                               placeholder="KoriePay ID (KP-…) or phone number"
                               class="mt-1.5 w-full rounded-xl border border-line bg-panel-2/60 px-4 py-3 text-sm font-semibold text-ink placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30">
                    </div>
                    <div>
                        <label for="amount" class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Amount ({{ $selected?->currency_code ?? '' }})</label>
                        <input id="amount" type="text" inputmode="decimal" wire:model="amount" required
                               placeholder="0"
                               class="mt-1.5 w-full rounded-xl border border-line bg-panel-2/60 px-4 py-3 text-lg font-black text-ink placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30">
                    </div>
                    <div>
                        <label for="note" class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Note <span class="normal-case text-faint">(optional)</span></label>
                        <input id="note" type="text" wire:model="note" maxlength="140" placeholder="What is this for?"
                               class="mt-1.5 w-full rounded-xl border border-line bg-panel-2/60 px-4 py-3 text-sm font-semibold text-ink placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30">
                    </div>
                </section>

                <button type="submit" wire:loading.attr="disabled"
                        class="w-full rounded-xl bg-brand py-3.5 text-sm font-black text-white shadow-glass transition-transform active:scale-[0.98] disabled:opacity-60">
                    Continue
                </button>
            </form>
        @else
            {{-- Step 2 — server preview, nothing moved yet --}}
            <section aria-label="Confirm transfer" class="space-y-4">
                @if ($error)
                    <x-kp.alert-card severity="high" icon="exclamation-triangle" title="Transfer failed" subtitle="{{ $error }}" />
                @endif

                <div class="panel p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">You are sending</p>
                    <p class="mt-1 text-3xl font-black tracking-tight text-ink">{{ number_format((float) $preview['amount'], ($selectedDetails['minor_units'] ?? 0)) }} {{ $preview['currency'] }}</p>

                    <dl class="mt-4 space-y-2 border-t border-line pt-4 text-sm">
                        <div class="flex justify-between"><dt class="text-muted">To</dt><dd class="font-bold text-ink">{{ $preview['recipient']['name'] }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">KoriePay ID</dt><dd class="font-mono text-xs font-bold text-ink">{{ $preview['recipient']['koriepay_id'] }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Phone</dt><dd class="font-semibold text-ink">{{ $preview['recipient']['phone_masked'] }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Fee</dt><dd class="font-bold text-ink">{{ $preview['fee'] }} {{ $preview['currency'] }}</dd></div>
                        <div class="flex justify-between border-t border-line/70 pt-2"><dt class="font-bold text-ink">Total debit</dt><dd class="font-black text-ink">{{ $preview['total_debit'] }} {{ $preview['currency'] }}</dd></div>
                    </dl>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button type="button" wire:click="requestPreview" class="rounded-xl border border-line bg-panel/80 py-3.5 text-sm font-bold text-muted hover:text-ink">Re-check</button>
                    <button type="button" wire:click="confirmSend" wire:loading.attr="disabled"
                            class="rounded-xl bg-brand py-3.5 text-sm font-black text-white shadow-glass transition-transform active:scale-[0.98] disabled:opacity-60">
                        Confirm & send
                    </button>
                </div>
                <p class="text-center text-[11px] text-muted">You will see the result below. If it takes a moment, that is the network — retrying never sends twice.</p>
            </section>
        @endif
    @endif

    {{-- ═══ RECEIVE ═════════════════════════════════════════════════════ --}}
    @if ($step === 'receive')
        @if ($receive)
            <section aria-label="Receive" class="panel p-6 text-center">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Scan to pay me</p>

                {{-- Server-rendered QR — inline SVG, no external scripts. --}}
                <div class="mx-auto mt-4 w-56 rounded-2xl bg-white p-3 shadow-glass">
                    <img src="data:image/svg+xml;base64,{{ base64_encode($receive['qr_svg']) }}" alt="KoriePay receive QR code" width="224" height="224" class="mx-auto">
                </div>

                <p class="mt-4 font-mono text-lg font-black tracking-tight text-ink">{{ $receive['koriepay_id'] }}</p>
                <p class="text-sm font-semibold text-ink">{{ $receive['name'] }}</p>
                <p class="text-xs text-muted">{{ $receive['phone_masked'] }}</p>

                <button type="button" onclick="navigator.clipboard?.writeText('{{ $receive['koriepay_id'] }}'); this.textContent='Copied!'"
                        class="mt-4 rounded-xl bg-brand px-5 py-2.5 text-sm font-black text-white shadow-glass">Copy my ID</button>

                <p class="mt-4 text-[11px] text-muted">Can also be reached at <span class="font-mono font-bold">{{ $receive['qr_payload'] }}</span></p>
            </section>
        @else
            <x-kp.empty-state icon="identification" title="Receive not available" description="Set up your KoriePay ID to receive money." />
        @endif
    @endif

    {{-- ═══ RESULT ══════════════════════════════════════════════════════ --}}
    @if ($step === 'result' && $result)
        @php
            $outcome = $result['outcome']; // success | failed | processing | unknown
        @endphp
        <section aria-label="Transfer result" class="panel p-6 text-center">
            @if ($outcome === 'success')
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-ok/15 text-ok">
                    <x-kp.icon name="check" class="h-8 w-8" stroke="2.5" />
                </span>
                <h2 class="mt-4 text-xl font-black text-ink">Sent</h2>
                <p class="mt-1 text-sm text-muted">{{ number_format((float) $result['amount'], 0) }} {{ $result['currency'] }} to {{ $result['recipient']['name'] ?? 'recipient' }}</p>
            @elseif ($outcome === 'failed')
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-crit/15 text-crit">
                    <x-kp.icon name="x-mark" class="h-8 w-8" stroke="2.5" />
                </span>
                <h2 class="mt-4 text-xl font-black text-ink">Not sent</h2>
                <p class="mt-1 text-sm text-muted">{{ $result['error_reason'] ?? 'The transfer could not be completed.' }}</p>
                <p class="mt-1 text-[11px] text-faint">No money left your wallet.</p>
            @else
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-gold/20 text-brand-gold">
                    <x-kp.icon name="clock" class="h-8 w-8" stroke="2" />
                </span>
                <h2 class="mt-4 text-xl font-black text-ink">Processing</h2>
                <p class="mt-1 text-sm text-muted">We are confirming this transfer. It will appear in your activity shortly.</p>
            @endif

            <dl class="mt-5 space-y-1.5 border-t border-line pt-4 text-left text-xs">
                <div class="flex justify-between"><dt class="text-muted">Reference</dt><dd class="font-mono font-bold text-ink">{{ $result['reference'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Fee</dt><dd class="font-bold text-ink">{{ $result['fee'] }} {{ $result['currency'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Total debited</dt><dd class="font-black text-ink">{{ $result['total_debit'] }} {{ $result['currency'] }}</dd></div>
                @if ($result['status'] !== 'settled')
                    <div class="flex justify-between"><dt class="text-muted">Status</dt><dd class="font-bold uppercase text-ink">{{ $result['status'] }}</dd></div>
                @endif
            </dl>

            @if ($outcome === 'failed')
                <button type="button" wire:click="confirmSend" wire:loading.attr="disabled"
                        class="mt-5 w-full rounded-xl bg-brand py-3.5 text-sm font-black text-white shadow-glass disabled:opacity-60">
                    Try again
                </button>
            @endif

            <button type="button" wire:click="backToHub" class="mt-3 w-full rounded-xl border border-line bg-panel/80 py-3 text-sm font-bold text-muted hover:text-ink">
                Done
            </button>
        </section>
    @endif

    {{-- Loading shimmer while a network call is in flight --}}
    <div wire:loading.flex class="fixed inset-0 z-40 hidden items-center justify-center bg-canvas/70 backdrop-blur-sm">
        <div class="panel flex items-center gap-3 px-5 py-4">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-brand border-t-transparent" aria-hidden="true"></span>
            <span class="text-sm font-bold text-ink">Working…</span>
        </div>
    </div>
</div>
