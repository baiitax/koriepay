{{-- CUSTOMER BANKING — Dashboard (§10, §12, §82, §90).
     Mobile-first (390px design, scales 320→768+). Glass surfaces, no
     Tailwind CDN, no `wallets.balance` anywhere — balances are ledger +
     in-flight derived by CustomerWalletService. --}}

<div class="mx-auto w-full max-w-md px-4 pt-safe sm:px-6 lg:max-w-none lg:px-0" wire:poll.30s>

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <header class="flex items-center justify-between py-5">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand to-brand-2 text-sm font-black text-white shadow-glass">K</span>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">KoriePay</p>
                <h1 class="truncate text-base font-bold text-ink">{{ $profile['name'] }}</h1>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if ($profile['kyc_status'] === 'verified')
                <x-kp.badge tone="ok" icon="check-badge" class="!text-[10px]">Verified</x-kp.badge>
            @else
                <a href="{{ route('customer.kyc') }}" wire:navigate>
                    <x-kp.badge tone="alert" icon="identification">Verify ID</x-kp.badge>
                </a>
            @endif
            <button type="button" wire:click="toggleBalance" aria-label="{{ $showBalance ? 'Hide balances' : 'Show balances' }}" class="rounded-xl border border-line bg-panel/80 p-2.5 text-muted hover:text-ink">
                <x-kp.icon :name="$showBalance ? 'eye' : 'eye'" class="h-4.5 w-4.5" />
            </button>
        </div>
    </header>

    {{-- ── No eligible wallets yet (honest eligibility state, §75) ─────── --}}
    @if (! $selected)
        <section aria-label="Wallet eligibility" class="panel p-6 text-center">
            <x-kp.empty-state
                icon="identification"
                title="Complete verification to open a wallet"
                description="Wallet eligibility depends on your country and verification level. Complete KYC and your wallets will appear here." />
            <a href="{{ route('customer.kyc') }}" wire:navigate class="mt-4 inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-bold text-white">
                Verify identity <x-kp.icon name="chevron-right" class="h-4 w-4" />
            </a>
        </section>
    @endif

    {{-- ── Primary balance hero ────────────────────────────────────────── --}}
    @if ($selected && $selectedDetails)
        <section aria-label="Balance" class="panel relative overflow-hidden p-5">
            <div class="pointer-events-none absolute -right-10 -top-16 h-40 w-40 rounded-full bg-brand/15 blur-2xl" aria-hidden="true"></div>

            <div class="flex items-center justify-between">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">{{ $selected->display_name }}</p>
                <span class="text-[10px] font-bold uppercase tracking-widest text-muted">{{ $selected->currency_code }}</span>
            </div>

            <p class="mt-2 text-[32px] font-black leading-none tracking-tight" x-data x-cloak>
                @if ($showBalance)
                    {{ number_format((float) $selectedDetails['available'], $selectedDetails['minor_units']) }}
                @else
                    ••••••
                @endif
            </p>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] font-semibold text-muted">
                <span>Pending: {{ $showBalance ? number_format((float) $selectedDetails['pending'], $selectedDetails['minor_units']) : '••••' }}</span>
                <span class="inline-flex items-center gap-1"><x-kp.icon name="shield-check" class="h-3.5 w-3.5 text-ok" /> Ledger-backed</span>
            </div>

            {{-- Wallet switcher --}}
            @if (count($wallets) > 1)
                <div class="mt-4 flex gap-2" role="tablist" aria-label="Wallets">
                    @foreach ($wallets as $w)
                        <button type="button"
                                wire:click="selectWallet('{{ $w->wallet_id }}')"
                                role="tab"
                                aria-selected="{{ $selected->wallet_id === $w->wallet_id ? 'true' : 'false' }}"
                                class="flex-1 rounded-xl border px-3 py-2 text-left transition-colors {{ $selected->wallet_id === $w->wallet_id ? 'border-brand/50 bg-brand/10' : 'border-line bg-panel-2/60 hover:border-brand/30' }}">
                            <span class="block text-[9px] font-black uppercase tracking-widest text-muted">{{ $w->currency_code }}@if ($w->is_primary) · Main @endif</span>
                            <span class="block text-xs font-bold text-ink">{{ $showBalance ? number_format((float) $w->available_balance, $selectedDetails['minor_units']) : '••••' }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Portfolio estimate — clearly labelled, never authoritative (§12) --}}
        <section aria-label="Portfolio estimate" class="glass-inset mt-3 flex items-center justify-between rounded-xl px-4 py-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-muted">Total portfolio (est.)</p>
                @if ($portfolio['rate_available'])
                    <p class="text-sm font-bold text-ink">{{ $showBalance ? number_format((float) $portfolio['total'], 0) : '••••' }} {{ $portfolio['currency'] }}</p>
                @else
                    <p class="text-sm font-bold text-ink">Estimate unavailable</p>
                @endif
            </div>
            <span class="inline-flex items-center gap-1 rounded-full border border-line bg-panel px-2 py-1 text-[9px] font-black uppercase tracking-widest text-muted" title="Converted at the current KoriePay rate — not withdrawable value.">
                <x-kp.icon name="info" class="h-3 w-3" /> Estimate
            </span>
        </section>
    @endif

    {{-- ── Quick services ──────────────────────────────────────────────── --}}
    <section aria-label="Quick services" class="mt-6">
        <h2 class="mb-3 text-xs font-black uppercase tracking-[0.18em] text-muted">Quick actions</h2>
        <div class="grid grid-cols-4 gap-3">
            <a href="{{ route('customer.pay', ['view' => 'send']) }}" wire:navigate class="group flex flex-col items-center gap-1.5">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand text-white shadow-glass transition-transform group-active:scale-90"><x-kp.icon name="arrow-up-right" class="h-5 w-5" /></span>
                <span class="text-[10px] font-bold text-muted group-hover:text-ink">Send</span>
            </a>
            <a href="{{ route('customer.pay', ['view' => 'receive']) }}" wire:navigate class="group flex flex-col items-center gap-1.5">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl border border-line bg-panel/80 text-ink transition-transform group-active:scale-90"><x-kp.icon name="arrow-down-right" class="h-5 w-5" /></span>
                <span class="text-[10px] font-bold text-muted group-hover:text-ink">Receive</span>
            </a>
            <a href="{{ route('customer.pay') }}" wire:navigate class="group flex flex-col items-center gap-1.5 {{ $exchangeAvailable ? '' : 'pointer-events-none opacity-50' }}">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl border border-line bg-panel/80 text-ink transition-transform group-active:scale-90"><x-kp.icon name="arrow-right-left" class="h-5 w-5" /></span>
                <span class="text-[10px] font-bold text-muted group-hover:text-ink">Exchange</span>
            </a>
            <a href="{{ route('customer.bills') }}" wire:navigate class="group flex flex-col items-center gap-1.5">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl border border-line bg-panel/80 text-ink transition-transform group-active:scale-90"><x-kp.icon name="bolt" class="h-5 w-5" /></span>
                <span class="text-[10px] font-bold text-muted group-hover:text-ink">Bills</span>
            </a>
        </div>
    </section>

    {{-- ── Recent transactions ─────────────────────────────────────────── --}}
    <section aria-label="Recent transactions" class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-[0.18em] text-muted">Recent activity</h2>
            <a href="{{ route('customer.history') }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-bold text-brand">View all <x-kp.icon name="chevron-right" class="h-3.5 w-3.5" /></a>
        </div>

        @if (count($recentTransactions) === 0)
            <x-kp.empty-state icon="clock" title="No activity yet" description="Your transactions will appear here as you send, receive or exchange money." />
        @else
            <ul class="panel divide-y divide-line/70 px-1">
                @foreach ($recentTransactions as $tx)
                    @php $out = (int) $tx->sender_id === (int) auth()->id(); @endphp
                    <li class="flex items-center gap-3 px-3 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $out ? 'bg-crit/10 text-crit' : 'bg-ok/10 text-ok' }}">
                            <x-kp.icon :name="$out ? 'arrow-up' : 'arrow-down'" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink">{{ $out ? ($tx->receiver_name ?? 'KoriePay') : (auth()->user()->name) }}</p>
                            <p class="text-[11px] text-muted">{{ strtoupper((string) $tx->type) }} · {{ $tx->created_at?->diffForHumans() }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold {{ $out ? 'text-ink' : 'text-ok' }}">{{ $out ? '−' : '+' }}{{ number_format((float) $tx->source_amount, 0) }} {{ $tx->source_currency }}</p>
                            <x-kp.status-badge :status="$tx->status" />
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- ── KYC nudge ───────────────────────────────────────────────────── --}}
    @if ($profile['kyc_status'] !== 'verified')
        <a href="{{ route('customer.kyc') }}" wire:navigate class="mt-6 block">
            <x-kp.alert-card severity="high" icon="identification" title="Finish verification" subtitle="Verify your identity to unlock higher limits and currency exchange." />
        </a>
    @endif
</div>
