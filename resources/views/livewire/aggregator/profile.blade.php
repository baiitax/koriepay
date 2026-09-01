<div class="mx-auto max-w-5xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned." />
    @else
        @php $p = $payload; @endphp

        <div>
            <h1 class="text-2xl font-black tracking-tight text-ink">Profile & limits</h1>
            <p class="mt-0.5 text-sm text-muted">Identity from your aggregator record · limits derived from the ledger — never invented</p>
        </div>

        {{-- Identity card --}}
        <section class="panel p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand to-brand-2 text-xl font-black italic text-white">K</span>
                    <div>
                        @if ($editing)
                            <input type="text" wire:model="displayName" maxlength="160" class="rounded-lg border border-line bg-panel-2/50 px-2 py-1 text-lg font-black outline-none focus:border-brand">
                            @error('displayName')<p class="text-[11px] font-bold text-crit">{{ $message }}</p>@enderror
                        @else
                            <h2 class="text-lg font-black text-ink">{{ $p['identity']['name'] }}</h2>
                        @endif
                        <p class="text-xs font-bold text-brand">{{ $p['identity']['code'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-kp.status-badge :status="$p['identity']['status']" />
                    <x-kp.status-badge :status="$p['identity']['kyc_status']" />
                    @if (! $editing)
                        <button wire:click="toggleEdit" class="rounded-xl border border-line px-3 py-1.5 text-[11px] font-bold text-muted hover:bg-panel-2">Edit name</button>
                    @else
                        <button wire:click="saveName" class="rounded-xl bg-brand px-3 py-1.5 text-[11px] font-black text-white hover:bg-brand-2">Save</button>
                        <button wire:click="toggleEdit" class="rounded-xl border border-line px-3 py-1.5 text-[11px] font-bold text-muted hover:bg-panel-2">Cancel</button>
                    @endif
                </div>
            </div>

            <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                @foreach ([
                    'Country' => $p['identity']['country'],
                    'Region' => $p['identity']['region'],
                    'City' => $p['identity']['city'],
                    'Member since' => $p['identity']['member_since'],
                    'Account email' => $p['identity']['user_email'],
                    'Account phone' => $p['identity']['user_phone'],
                    'Last login' => $p['identity']['last_login_at'] ?? '—',
                ] as $label => $value)
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">{{ $label }}</dt>
                        <dd class="mt-0.5 font-bold text-ink">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Backend-sourced limits (§64–65) --}}
        <section class="panel p-6">
            <x-kp.section-header eyebrow="Backend-sourced limits" title="What your network can actually do"
                description="Derived from ledger positions and real records. No fabricated caps." />

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($p['limits']['position'] as $currency => $pos)
                    <div class="rounded-2xl border border-line bg-panel-2/40 p-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-brand">{{ $currency }} position</p>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-muted">Agent floats</dt><dd class="font-black font-mono">{{ $pos['agent_wallets'] }}</dd></div>
                            <div class="flex justify-between"><dt class="text-muted">Your float</dt><dd class="font-black font-mono">{{ $pos['aggregator_wallet'] }}</dd></div>
                            <div class="flex justify-between"><dt class="text-muted">Earmarked (pending liquidity)</dt><dd class="font-black font-mono">{{ $pos['pending'] }}</dd></div>
                            <div class="flex justify-between"><dt class="text-muted">Operational cash</dt><dd class="font-black font-mono">{{ $pos['operational_cash'] }}</dd></div>
                            <div class="flex justify-between"><dt class="text-muted">Settlement exposure</dt><dd class="font-black font-mono">{{ $pos['settlement_exposure'] }}</dd></div>
                        </dl>
                        <p class="mt-2 text-[10px] font-medium text-faint">Source: ledger accounts</p>
                    </div>
                @endforeach
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 text-center">
                <div class="rounded-2xl border border-line p-4">
                    <dd class="text-2xl font-black text-ink">{{ $p['limits']['agents']['total'] }}</dd>
                    <dt class="mt-1 text-[10px] font-black uppercase tracking-wide text-muted">Agents (total)</dt>
                </div>
                <div class="rounded-2xl border border-line p-4">
                    <dd class="text-2xl font-black text-ok">{{ $p['limits']['agents']['active'] }}</dd>
                    <dt class="mt-1 text-[10px] font-black uppercase tracking-wide text-muted">Active</dt>
                </div>
                <div class="rounded-2xl border border-line p-4">
                    <dd class="text-2xl font-black text-brand-gold">{{ $p['limits']['outstanding_liquidity'] }}</dd>
                    <dt class="mt-1 text-[10px] font-black uppercase tracking-wide text-muted">Outstanding liquidity</dt>
                </div>
                <div class="rounded-2xl border border-line p-4">
                    <dd class="text-2xl font-black text-brand">{{ $p['limits']['pending_commission'] }}</dd>
                    <dt class="mt-1 text-[10px] font-black uppercase tracking-wide text-muted">Pending commission</dt>
                </div>
            </dl>

            <p class="mt-4 text-xs font-medium text-muted">{{ $p['limits']['capacity_note'] }}</p>
        </section>

        {{-- Authorization + cache policy --}}
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="panel p-6">
                <x-kp.section-header eyebrow="Authorization" title="Your permission set"
                    description="Loaded from the role_permissions matrix — the same matrix the server enforces." />
                <div class="mt-4 flex flex-wrap gap-1.5">
                    @foreach ($p['permissions'] as $perm)
                        <span class="rounded-full bg-panel-2 border border-line px-2.5 py-1 font-mono text-[10px] font-bold text-muted">{{ $perm }}</span>
                    @endforeach
                </div>
            </div>
            <div class="panel p-6">
                <x-kp.section-header eyebrow="Data policy" title="Cache & freshness"
                    description="The console never caches what it cannot afford to be stale." />
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3 rounded-xl border border-ok/20 bg-ok/5 p-3">
                        <x-kp.icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-ok" />
                        <p class="text-xs leading-relaxed text-ink/90">{{ $p['cache_policy']['statement'] }}</p>
                    </div>
                    <div class="flex items-start gap-3 rounded-xl border border-line bg-panel-2/40 p-3">
                        <x-kp.icon name="database" class="mt-0.5 h-4 w-4 shrink-0 text-brand" />
                        <p class="text-xs leading-relaxed text-ink/90">{{ $p['cache_policy']['cached_ok'] }}</p>
                    </div>
                </div>
            </div>
        </section>

        <p class="text-[10px] font-medium text-faint">{{ $p['basis'] }}</p>
    @endif
</div>
