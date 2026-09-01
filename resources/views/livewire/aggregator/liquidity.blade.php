<div class="mx-auto max-w-7xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned. Contact KoriePay admin to link your network." />
    @else
        @php $p = $payload; @endphp

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Liquidity</h1>
                <p class="mt-0.5 text-sm text-muted">Network cash position, demand and the agent liquidity workflow — every figure ledger-sourced; projections are labelled estimates.</p>
            </div>
            {{-- Currency filter — never mixes XOF and NGN. --}}
            <div class="flex items-center gap-1.5 rounded-2xl border border-line bg-panel/60 p-1.5 backdrop-blur">
                @foreach ($p['currencies'] as $code)
                    <button type="button" wire:click="setCurrency('{{ $code }}')"
                            class="rounded-xl px-3 py-1.5 text-xs font-black transition {{ $currency === $code ? 'bg-brand text-white' : 'text-muted hover:bg-panel-2 hover:text-ink' }}">
                        {{ $code }}
                    </button>
                @endforeach
                @if ($currency !== '')
                    <button type="button" wire:click="setCurrency('')" class="rounded-xl px-2 py-1.5 text-xs font-bold text-muted transition hover:text-ink">All</button>
                @endif
            </div>
        </div>

        @php
            $active = $currency !== '' ? [$currency => $p['position'][$currency]] : $p['position'];
        @endphp

        {{-- Position per ledger concept (§23) — agent wallet / aggregator wallet / operational cash / pending / settlement. --}}
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($active as $code => $pos)
                <div class="panel p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Position · {{ $code }}</p>
                        <span class="rounded-full bg-brand/10 px-2.5 py-1 text-[10px] font-black text-brand">{{ $code }}</span>
                    </div>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-muted">Agent wallets</span>
                            <span class="font-mono font-black text-ink">{{ number_format((float) $pos['agent_wallets'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-muted">Aggregator wallet</span>
                            <span class="font-mono font-black text-ink">{{ number_format((float) $pos['aggregator_wallet'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-muted">Pending (earmarked)</span>
                            <span class="font-mono font-black text-brand-gold">{{ number_format((float) $pos['pending'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-line pt-2">
                            <span class="font-bold text-ink">Operational cash (available)</span>
                            <span class="font-mono font-black text-ink">{{ number_format((float) $pos['operational_cash'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-semibold text-muted">Platform cash pool (gross)</span>
                            <span class="font-mono font-bold text-muted">{{ number_format((float) $pos['platform_gross'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-semibold text-muted">Settlement exposure (platform)</span>
                            <span class="font-mono font-bold text-muted">{{ number_format((float) $pos['settlement_exposure'], 2) }}</span>
                        </div>
                    </div>
                </div>

                @php $f = $p['forecast'][$code]; $d = $p['demand'][$code]; @endphp
                <div class="panel p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Forecast · {{ $code }}</p>
                        <span class="rounded-full bg-brand-gold/15 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-brand-gold">Estimate</span>
                    </div>
                    <p class="mt-1 text-[11px] text-muted">{{ $f['basis'] }}</p>
                    <div class="mt-3 space-y-2">
                        @foreach ([['6h', '6h'], ['24h', '24h'], ['7d', '7d']] as [$key, $label])
                            <div class="flex items-center justify-between rounded-xl border border-line bg-white/40 px-3 py-2 text-sm">
                                <span class="font-semibold text-muted">{{ $f[$key.'_label'] }}</span>
                                <span class="font-mono font-black text-ink">{{ number_format((float) $f[$key], 0) }} {{ $code }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-[11px] font-bold text-muted">
                        Demand: {{ number_format((float) $d['cash_in_7d'], 0) }} in / {{ number_format((float) $d['cash_out_7d'], 0) }} out (7d, posted)
                    </p>
                </div>

                <div class="panel p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Alerts · {{ $code }}</p>
                        <span class="rounded-full bg-line/60 px-2.5 py-1 text-[10px] font-black text-muted">{{ count($p['alerts'][$code] ?? []) }}</span>
                    </div>
                    @forelse (($p['alerts'][$code] ?? []) as $alert)
                        <div class="mt-2 flex items-start gap-2 rounded-xl border border-line bg-white/40 px-3 py-2">
                            <span class="mt-0.5 {{ $alert['severity'] === 'critical' ? 'text-crit' : 'text-brand-orange' }}">
                                <x-kp.icon name="exclamation-triangle" class="h-3.5 w-3.5" stroke="2.4" />
                            </span>
                            <div>
                                <p class="text-xs font-bold leading-snug text-ink">{{ $alert['message'] }}</p>
                                @if (($alert['estimate'] ?? false))
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-brand-gold">Estimate</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="mt-2 text-sm text-muted">No liquidity alerts for {{ $code }} — every agent has healthy coverage.</p>
                    @endforelse
                </div>
            @endforeach
        </section>

        {{-- Per-agent statuses (§23–24): Healthy / Watch / Low / Critical. --}}
        <section class="panel overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-black text-ink">Agent liquidity status</h2>
                <p class="text-xs text-muted">Buffer = float ÷ average daily cash-out demand (7-day posted history) — labelled estimate.</p>
            </div>
            @if (count($p['agents']) === 0)
                <x-kp.empty-state icon="users" title="No agents in the network" description="Recruit agents to build the network before liquidity can be measured." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-line text-[10px] font-black uppercase tracking-[0.14em] text-muted">
                                <th class="px-4 py-3">Agent</th>
                                <th class="px-4 py-3 text-right">Float</th>
                                <th class="px-4 py-3 text-right">Demand (7d)</th>
                                <th class="px-4 py-3 text-right">Buffer</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Cash-out risk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($p['agents'] as $row)
                                @php
                                    $tone = match ($row['bucket']) {
                                        'healthy' => 'ok',
                                        'watch' => 'warn',
                                        'low' => 'alert',
                                        'critical' => 'crit',
                                        'no_demand' => 'info',
                                        default => 'neutral',
                                    };
                                @endphp
                                <tr class="hover:bg-panel-2/60">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('aggregator.agents.show', $row['agent_code']) }}" wire:navigate class="font-bold text-ink hover:text-brand">
                                            {{ $row['name'] }}
                                        </a>
                                        <span class="block font-mono text-[11px] text-muted">{{ $row['agent_code'] }} · {{ $row['currency'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-ink">{{ number_format((float) $row['float'], 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-muted">{{ number_format((float) $row['demand_7d'], 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-muted">{{ $row['buffer_ratio'] ?? '—' }}</td>
                                    <td class="px-4 py-3"><x-kp.status-badge :status="$row['bucket']" :label="$row['status_label']" :tone="$tone" /></td>
                                    <td class="px-4 py-3 text-[12px] text-muted">
                                        @if (($row['cash_out_risk']['estimate'] ?? false)) <span class="mr-1 text-[9px] font-black uppercase tracking-wide text-brand-gold">Est</span> @endif
                                        {{ $row['cash_out_risk']['label'] ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Request workflow (§25–26) --}}
        <section class="grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2 panel overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <div>
                        <h2 class="font-black text-ink">Liquidity requests</h2>
                        <p class="text-xs text-muted">
                            {{ $p['requests_summary']['open'] }} open · {{ $p['requests_summary']['funded'] }} funded · {{ $p['requests_summary']['rejected'] }} rejected
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (['open' => 'Open', 'all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'funded' => 'Funded', 'rejected' => 'Rejected'] as $key => $label)
                            <button type="button" wire:click="setStatus('{{ $key }}')"
                                    class="rounded-lg px-2.5 py-1.5 text-[11px] font-black transition {{ $status === $key ? 'bg-brand text-white' : 'border border-line text-muted hover:bg-panel-2 hover:text-ink' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @if ($p['requests']['total'] === 0)
                    <x-kp.empty-state icon="wallet" title="No liquidity requests" description="No requests match the current view. Agents request liquidity through their portal, or raise one on an agent's behalf." />
                @else
                    <div class="divide-y divide-line">
                        @foreach ($p['requests']['rows'] as $request)
                            @php $reqAgent = $request->agent; @endphp
                            <div class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-mono text-xs font-bold text-ink">{{ $request->reference }}</p>
                                            <x-kp.status-badge :status="$request->status" />
                                            @if ($request->risk_level !== 'low')
                                                <x-kp.risk-badge :level="$request->risk_level" />
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm font-bold text-ink">
                                            {{ number_format((float) $request->amount, 2) }} {{ $request->currency_code }}
                                            <span class="font-medium text-muted">· {{ $reqAgent?->user?->name ?? 'Agent #'.$request->agent_id }} ({{ $reqAgent?->agent_code }})</span>
                                        </p>
                                        <p class="text-[11px] text-muted">
                                            {{ ucfirst(str_replace('_', ' ', $request->reason)) }} · {{ $request->created_at->diffForHumans() }}
                                            @if ($request->review_note) · {{ $request->review_note }} @endif
                                        </p>
                                        @if (is_array($request->risk_notes) && count($request->risk_notes) > 0)
                                            <ul class="mt-1 space-y-0.5 text-[11px] text-muted">
                                                @foreach ($request->risk_notes as $note)
                                                    <li>• {{ $note }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        @if ($request->status === 'pending' || $request->status === 'in_review')
                                            <input type="text" wire:model="notes.{{ $request->id }}" placeholder="Note…"
                                                   class="w-32 rounded-lg border border-line bg-white/60 px-2.5 py-1.5 text-[11px] font-semibold text-ink outline-none focus:border-brand">
                                            <button type="button" wire:click="approve({{ $request->id }})"
                                                    class="rounded-lg bg-ok px-3 py-1.5 text-[11px] font-black text-white transition hover:bg-ok/90">Approve</button>
                                            <button type="button" wire:click="reject({{ $request->id }})"
                                                    class="rounded-lg border border-brand-orange/40 px-3 py-1.5 text-[11px] font-black text-brand-orange transition hover:bg-brand-orange/10">Reject</button>
                                        @elseif ($request->status === 'approved')
                                            <button type="button" wire:click="fund({{ $request->id }})"
                                                    class="rounded-lg bg-brand px-3 py-1.5 text-[11px] font-black text-white transition hover:bg-brand/90">Fund</button>
                                            <button type="button" wire:click="cancel({{ $request->id }})"
                                                    class="rounded-lg border border-line px-3 py-1.5 text-[11px] font-black text-muted transition hover:bg-panel-2 hover:text-ink">Cancel</button>
                                        @elseif ($request->status === 'funded' || $request->status === 'rejected' || $request->status === 'cancelled')
                                            <span class="text-[11px] font-bold text-muted">{{ ucfirst($request->status) }} {{ $request->reviewed_at?->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($p['requests']['total'] > 10)
                        <div class="flex items-center justify-between border-t border-line px-4 py-3">
                            <p class="text-[11px] font-semibold text-muted">Showing page {{ $p['requests']['page'] }}</p>
                            <div class="flex gap-1.5">
                                <button type="button" wire:click="gotoPage({{ $p['requests']['page'] - 1 }})" @disabled($p['requests']['page'] <= 1)
                                        class="rounded-lg border border-line px-2.5 py-1.5 text-xs font-bold text-muted transition enabled:hover:bg-panel-2 disabled:opacity-40">Prev</button>
                                <button type="button" wire:click="gotoPage({{ $p['requests']['page'] + 1 }})" @disabled($p['requests']['page'] * 10 >= $p['requests']['total'])
                                        class="rounded-lg border border-line px-2.5 py-1.5 text-xs font-bold text-muted transition enabled:hover:bg-panel-2 disabled:opacity-40">Next</button>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Raise on behalf of an agent (§26 demo entry point). --}}
            <div class="panel h-fit p-5">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Raise a request</p>
                <h3 class="mt-1 font-black text-ink">On behalf of an agent</h3>
                <p class="mt-1 text-xs text-muted">Capture, review, approve and fund — each step audited; approval earmarks operational cash on the ledger.</p>
                <form wire:submit="createRequest" class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-muted">Agent</label>
                        <select wire:model="agentId" class="w-full rounded-xl border border-line bg-white/60 px-3 py-2.5 text-sm font-semibold text-ink outline-none focus:border-brand">
                            <option value="">Select agent…</option>
                            @foreach ($p['agents'] as $row)
                                @if ($row['status'] === 'active')
                                    <option value="{{ $row['agent_id'] }}">{{ $row['name'] }} ({{ $row['agent_code'] }})</option>
                                @endif
                            @endforeach
                        </select>
                        @error('agentId') <span class="mt-1 block text-[11px] font-bold text-crit">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-muted">Amount</label>
                        <input wire:model="amount" inputmode="decimal" placeholder="e.g. 250000"
                               class="w-full rounded-xl border border-line bg-white/60 px-3 py-2.5 text-sm font-semibold text-ink outline-none placeholder:text-muted/50 focus:border-brand">
                        @error('amount') <span class="mt-1 block text-[11px] font-bold text-crit">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-muted">Reason</label>
                        <select wire:model="reason" class="w-full rounded-xl border border-line bg-white/60 px-3 py-2.5 text-sm font-semibold text-ink outline-none focus:border-brand">
                            <option value="cash_out_demand">Cash-out demand</option>
                            <option value="restock">Restock</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-black text-white transition hover:bg-brand/90">
                        Raise request
                    </button>
                </form>
                <p class="mt-3 text-[10px] leading-relaxed text-muted">
                    Currency is fixed to the agent's country (XOF/NGN). Amounts above 6× an agent's average daily cash-out demand are flagged high-risk and blocked.
                </p>
            </div>
        </section>
    @endif
</div>
