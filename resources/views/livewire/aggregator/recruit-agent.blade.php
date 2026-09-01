<div class="mx-auto max-w-3xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned. Contact KoriePay admin to link your network." />
    @else
        <div>
            <a href="{{ route('aggregator.agents') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-muted transition hover:text-ink">
                <x-kp.icon name="chevron-right" class="h-3.5 w-3.5 -scale-x-100" stroke="2.4" /> Back to agents
            </a>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-ink">Recruit an agent</h1>
            <p class="mt-0.5 text-sm text-muted">Capture agent details into your network. Recruitment does not activate the agent.</p>
        </div>

        @if ($created)
            <section class="panel border-brand/30 p-5">
                <div class="flex items-start gap-3">
                    <span class="rounded-2xl bg-ok/15 p-2.5 text-ok">
                        <x-kp.icon name="check-circle" class="h-5 w-5" stroke="2.2" />
                    </span>
                    <div>
                        <h2 class="font-black text-ink">Agent captured — {{ $createdCode }}</h2>
                        <p class="mt-1 text-sm leading-relaxed text-muted">
                            The agent is registered as <span class="font-bold text-ink">pending</span> with <span class="font-bold text-ink">unverified KYC</span>.
                            Activation is withheld until the KoriePay verification team approves their identity documents.
                            They will appear in your directory immediately.
                        </p>
                        <a href="{{ route('aggregator.agents') }}" wire:navigate
                           class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-brand px-4 py-2 text-xs font-bold text-white transition hover:bg-brand/90">
                            View in directory <x-kp.icon name="arrow-up-right" class="h-3.5 w-3.5" stroke="2.4" />
                        </a>
                    </div>
                </div>
            </section>
        @endif

        <form wire:submit="submit" class="space-y-5">
            <section class="panel space-y-4 p-5 sm:p-6">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Agent details</p>

                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-muted">Full name</label>
                    <input wire:model="name" placeholder="e.g. Halima Kante"
                           class="w-full rounded-xl border border-line bg-white/60 px-3.5 py-2.5 text-sm font-semibold text-ink outline-none placeholder:text-muted/50 focus:border-brand">
                    @error('name') <span class="mt-1 block text-[11px] font-bold text-crit">{{ $message }}</span> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-muted">Email</label>
                        <input wire:model="email" type="email" placeholder="agent@example.com"
                               class="w-full rounded-xl border border-line bg-white/60 px-3.5 py-2.5 text-sm font-semibold text-ink outline-none placeholder:text-muted/50 focus:border-brand">
                        @error('email') <span class="mt-1 block text-[11px] font-bold text-crit">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-muted">Phone</label>
                        <input wire:model="phone" placeholder="+227 90 00 00 00"
                               class="w-full rounded-xl border border-line bg-white/60 px-3.5 py-2.5 text-sm font-semibold text-ink outline-none placeholder:text-muted/50 focus:border-brand">
                        @error('phone') <span class="mt-1 block text-[11px] font-bold text-crit">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-muted">Country</label>
                        <select wire:model="country" class="w-full rounded-xl border border-line bg-white/60 px-3 py-2.5 text-sm font-semibold text-ink outline-none focus:border-brand">
                            <option value="NE">Niger (XOF)</option>
                            <option value="NG">Nigeria (NGN)</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-muted">Region</label>
                        <input wire:model="region" placeholder="e.g. Maradi"
                               class="w-full rounded-xl border border-line bg-white/60 px-3 py-2.5 text-sm font-semibold text-ink outline-none placeholder:text-muted/50 focus:border-brand">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-muted">City</label>
                        <input wire:model="city" placeholder="e.g. Maradi"
                               class="w-full rounded-xl border border-line bg-white/60 px-3 py-2.5 text-sm font-semibold text-ink outline-none placeholder:text-muted/50 focus:border-brand">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-muted">Tier</label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold'] as $value => $label)
                            <label class="cursor-pointer rounded-xl border px-3 py-2.5 text-center text-sm font-bold transition {{ $tier === $value ? 'border-brand bg-brand/10 text-brand' : 'border-line text-muted hover:bg-panel-2' }}">
                                <input type="radio" wire:model="tier" value="{{ $value }}" class="sr-only">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-muted">Notes (optional)</label>
                    <textarea wire:model="notes" rows="2" placeholder="Anything the verification team should know…"
                              class="w-full rounded-xl border border-line bg-white/60 px-3.5 py-2.5 text-sm font-semibold text-ink outline-none placeholder:text-muted/50 focus:border-brand"></textarea>
                </div>
            </section>

            <section class="panel border-line p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <span class="rounded-2xl bg-brand/10 p-2.5 text-brand">
                        <x-kp.icon name="shield-check" class="h-5 w-5" stroke="2.2" />
                    </span>
                    <p class="text-xs leading-relaxed text-muted">
                        <span class="font-black text-ink">What happens next.</span>
                        This form only captures the agent. They are created <span class="font-bold text-ink">pending</span> with
                        <span class="font-bold text-ink">unverified KYC</span>, a float ledger is provisioned in the selected
                        country's currency, and the whole action is written to the audit log. Activation happens exclusively
                        on the backend after their KYC is approved — the agent cannot transact until then.
                    </p>
                </div>
            </section>

            <div class="flex items-center gap-3">
                <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand px-6 py-3 text-sm font-black text-white shadow-lg shadow-brand/20 transition hover:bg-brand/90 disabled:opacity-60">
                    <x-kp.icon name="user-group" class="h-4 w-4" stroke="2.2" />
                    Capture agent
                </button>
                <a href="{{ route('aggregator.agents') }}" wire:navigate class="text-sm font-bold text-muted transition hover:text-ink">Cancel</a>
            </div>
        </form>
    @endif
</div>
