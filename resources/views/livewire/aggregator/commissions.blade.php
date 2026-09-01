<div class="mx-auto max-w-7xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned. Contact KoriePay admin to link your network." />
    @else
        @php $p = $payload; @endphp

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Commissions</h1>
                <p class="mt-0.5 text-sm text-muted">Accrued and paid commission intelligence — every figure from real commission entries.</p>
            </div>
            @if ($p['currency'] !== '')
                <button type="button" wire:click="setCurrency('')" class="rounded-xl border border-line px-3 py-1.5 text-xs font-black text-muted transition hover:bg-panel-2 hover:text-ink">Show all currencies</button>
            @endif
        </div>

        {{-- Overview: today / week / month --}}
        <section class="grid gap-3 sm:grid-cols-3">
            @foreach (['today' => 'Today', 'week' => 'This week', 'month' => 'This month'] as $key => $label)
                <div class="panel p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">{{ $label }}</p>
                    <p class="mt-2 font-mono text-2xl font-black text-ink">{{ number_format((float) $p['overview'][$key]['gross'], 0) }}</p>
                    <div class="mt-2 flex items-center gap-3 text-[11px] font-bold">
                        <span class="text-ok">{{ number_format((float) $p['overview'][$key]['paid'], 0) }} paid</span>
                        <span class="text-brand-gold">{{ number_format((float) $p['overview'][$key]['pending'], 0) }} pending</span>
                        <span class="text-muted">{{ $p['overview'][$key]['count'] }} entries</span>
                    </div>
                </div>
            @endforeach
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Earnings — every component shown; gross is never labelled net. --}}
            <section class="panel h-fit p-5 lg:col-span-1">
                <div class="flex items-center justify-between">
                    <h2 class="font-black text-ink">Earnings (30d)</h2>
                    <span class="rounded-full bg-brand-gold/15 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-brand-gold">Breakdown</span>
                </div>
                <div class="mt-3 space-y-2 text-sm">
                    @foreach ([
                        ['Gross commission', $p['earnings']['gross'], 'text-ink'],
                        ['Adjustments', $p['earnings']['adjustments'], $p['earnings']['adjustments'][0] === '-' ? 'text-crit' : 'text-muted'],
                        ['Reversals', $p['earnings']['reversals'], $p['earnings']['reversals'][0] === '-' ? 'text-crit' : 'text-muted'],
                    ] as [$label, $value, $color])
                        <div class="flex items-center justify-between border-b border-line pb-2">
                            <span class="font-semibold text-muted">{{ $label }}</span>
                            <span class="font-mono font-black {{ $color }}">{{ number_format((float) $value, 2) }}</span>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between pt-1">
                        <span class="font-black text-ink">Net earnings</span>
                        <span class="font-mono text-lg font-black text-brand">{{ number_format((float) $p['earnings']['net'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[12px]">
                        <span class="font-semibold text-muted">Paid</span>
                        <span class="font-mono font-bold text-ok">{{ number_format((float) $p['earnings']['paid'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[12px]">
                        <span class="font-semibold text-muted">Pending</span>
                        <span class="font-mono font-bold text-brand-gold">{{ number_format((float) $p['earnings']['pending'], 2) }}</span>
                    </div>
                </div>
                <p class="mt-3 text-[10px] leading-relaxed text-muted">{{ $p['earnings']['formula'] }}</p>
            </section>

            {{-- Breakdown by product/rule + audit trail --}}
            <section class="panel overflow-hidden lg:col-span-2">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="font-black text-ink">Breakdown by rule</h2>
                    <p class="text-xs text-muted">Gross accruals in the last 30 days per commission rule, with the rule definition where on record (§41).</p>
                </div>
                @if (count($p['breakdown']) === 0)
                    <x-kp.empty-state icon="banknotes" title="No commission rules" description="No commission entries on record in this window." />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-line text-[10px] font-black uppercase tracking-[0.14em] text-muted">
                                    <th class="px-4 py-3">Rule</th>
                                    <th class="px-4 py-3 text-right">Entries</th>
                                    <th class="px-4 py-3 text-right">Gross</th>
                                    <th class="px-4 py-3">Version</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($p['breakdown'] as $row)
                                    <tr class="hover:bg-panel-2/60">
                                        <td class="px-4 py-3 font-mono text-xs font-bold text-ink">{{ $row['rule_id'] }}</td>
                                        <td class="px-4 py-3 text-right text-muted">{{ $row['count'] }}</td>
                                        <td class="px-4 py-3 text-right font-mono font-black text-ink">{{ number_format((float) $row['amount'], 2) }}</td>
                                        <td class="px-4 py-3">
                                            @if ($row['definition'])
                                                <span class="text-[11px] font-semibold text-muted">rate {{ $row['definition']['rate'] }} · flat {{ $row['definition']['flat_amount'] }} · P{{ $row['definition']['priority'] }} · {{ $row['definition']['is_active'] ? 'active' : 'inactive' }}</span>
                                            @else
                                                <span class="text-[11px] font-semibold text-muted/70">definition not on record</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="border-t border-line px-5 py-4">
                    <h3 class="font-black text-ink">Audit trail</h3>
                    <p class="text-xs text-muted">Latest accrual entries with their rule version at the time.</p>
                </div>
                <div class="max-h-72 overflow-y-auto border-t border-line">
                    @forelse ($p['audit'] as $entry)
                        <div class="flex items-center gap-3 border-b border-line px-5 py-2.5 text-xs">
                            <span class="font-mono font-bold text-ink">{{ $entry['rule_id'] }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase {{ $entry['kind'] === 'reversal' ? 'bg-crit/10 text-crit' : ($entry['kind'] === 'adjustment' ? 'bg-brand-orange/10 text-brand-orange' : 'bg-ok/10 text-ok') }}">{{ $entry['kind'] }}</span>
                            <span class="font-mono font-bold text-ink">{{ number_format((float) $entry['amount'], 2) }} {{ $entry['currency'] }}</span>
                            <x-kp.status-badge :status="$entry['status']" />
                            <span class="ml-auto text-muted">{{ $entry['created_at']->diffForHumans() }}</span>
                            @if ($entry['version_known'])
                                <span class="text-muted/70">v{{ $entry['version']['priority'] }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="px-5 py-4 text-sm text-muted">No commission entries on record.</p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Per-agent commission table --}}
        <section class="panel overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-black text-ink">Per-agent commissions</h2>
                <p class="text-xs text-muted">All-time accrued/paid totals per agent in your network.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-line text-[10px] font-black uppercase tracking-[0.14em] text-muted">
                            <th class="px-4 py-3">Agent</th>
                            <th class="px-4 py-3 text-right">Entries</th>
                            <th class="px-4 py-3 text-right">Accrued</th>
                            <th class="px-4 py-3 text-right">Paid</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($p['agents'] as $row)
                            <tr class="hover:bg-panel-2/60">
                                <td class="px-4 py-3">
                                    <a href="{{ route('aggregator.agents.show', $row['agent_code']) }}" wire:navigate class="font-bold text-ink hover:text-brand">{{ $row['name'] }}</a>
                                    <span class="block font-mono text-[11px] text-muted">{{ $row['agent_code'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-right text-muted">{{ $row['count'] }}</td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-brand-gold">{{ number_format((float) $row['accrued'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-ok">{{ number_format((float) $row['paid'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono font-black text-ink">{{ number_format((float) $row['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
