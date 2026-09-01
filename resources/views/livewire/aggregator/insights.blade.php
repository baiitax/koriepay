<div class="mx-auto max-w-7xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned." />
    @else
        @php $p = $payload; @endphp

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Insights & end-of-day</h1>
                <p class="mt-0.5 text-sm text-muted">Read-model analytics, network health signals, EOD summary</p>
            </div>
            <x-kp.freshness level="authoritative" sub="recomputed at render" />
        </div>

        {{-- EOD summary (§113–116) --}}
        <section class="panel p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-kp.section-header eyebrow="End-of-day summary" :title="'EOD · '.$p['eod']['date']"
                    description="Everything below is computed from the day's real records." />
                <div class="flex items-center gap-2">
                    <input type="date" wire:model="eodDate" class="rounded-xl border border-line bg-panel-2/50 px-3 py-2 text-xs font-bold outline-none focus:border-brand">
                    @if ($p['canSnapshot'])
                        <button wire:click="runSnapshot" class="rounded-xl bg-brand px-3 py-2 text-xs font-black uppercase tracking-wide text-white hover:bg-brand-2">Write read-model snapshot</button>
                    @endif
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
                <x-kp.metric-card label="Volume" :value="number_format((float) $p['eod']['volume'], 0)" currency="XOF" freshness="authoritative" icon="banknotes" />
                <x-kp.metric-card label="Transactions" :value="$p['eod']['transactions']" freshness="authoritative" icon="arrow-right-left" />
                <x-kp.metric-card label="Success rate" :value="$p['eod']['success_rate'].'%'" freshness="authoritative" icon="check-circle" tone="ok" />
                <x-kp.metric-card label="Commission accrued" :value="number_format((float) $p['eod']['commission_accrued'], 0)" freshness="authoritative" icon="trending-up" />
                <x-kp.metric-card label="New agents" :value="$p['eod']['new_agents']" freshness="authoritative" icon="user-check" />
                <x-kp.metric-card label="Open alerts" :value="$p['eod']['open_alerts']" freshness="authoritative" icon="shield-exclamation"
                    :tone="$p['eod']['open_alerts'] > 0 ? 'warn' : 'neutral'" />
            </div>

            <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-xs font-semibold text-muted">
                <span>Settlements created: <span class="font-black text-ink">{{ $p['eod']['settlements_created'] }}</span></span>
                <span>Settlement value: <span class="font-black text-ink">{{ $p['eod']['settlement_value'] }}</span></span>
                <span>Failed ops: <span class="font-black {{ $p['eod']['failed_ops'] > 0 ? 'text-crit' : 'text-ink' }}">{{ $p['eod']['failed_ops'] }}</span></span>
                <span>Pending liquidity: <span class="font-black text-ink">{{ $p['eod']['pending_liquidity'] }}</span></span>
                @if ($p['eod']['snapshot'])
                    <span class="text-faint">read-model snapshot @ {{ $p['eod']['snapshot']['computed_at'] }}</span>
                @else
                    <x-kp.freshness level="estimate" label="No snapshot yet" sub="write one to persist the day" />
                @endif
            </div>
        </section>

        {{-- Observability indicators (§96–97) --}}
        <section class="panel p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-kp.section-header eyebrow="Observability" title="Network health signals"
                    description="Recomputed from live records at render time — nothing is cached." />
                <x-kp.health-indicator :status="$p['observability']['status']" />
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($p['observability']['indicators'] as $indicator)
                    <div class="rounded-2xl border border-line bg-panel-2/40 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-extrabold text-ink">{{ $indicator['label'] }}</p>
                            <x-kp.status-badge :status="$indicator['status'] === 'attention' ? 'attention' : ($indicator['status'] === 'critical' ? 'critical' : ($indicator['status'] === 'degraded' ? 'degraded' : ($indicator['status'] === 'unknown' ? 'unknown' : 'operational')))" />
                        </div>
                        <p class="mt-2 text-xl font-black text-ink">{{ $indicator['value'] }}</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-muted">{{ $indicator['explanation'] }}</p>
                        <p class="mt-2 text-[10px] font-medium text-faint">source: {{ $indicator['source'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Daily series + growth --}}
        <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="panel p-5 sm:p-6">
                <x-kp.section-header eyebrow="14-day series" title="Daily activity"
                    description="Read-model rows where snapshots exist, live computation otherwise — each row labelled." />
                <div class="mt-4 max-h-80 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-panel">
                            <tr class="text-[10px] font-black uppercase tracking-wide text-muted">
                                <th class="py-2 pr-3">Date</th>
                                <th class="py-2 pr-3">Ops</th>
                                <th class="py-2 pr-3">Volume</th>
                                <th class="py-2 pr-3">Active</th>
                                <th class="py-2 pr-3">New</th>
                                <th class="py-2">Success</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($p['series'] as $day)
                                <tr class="border-t border-line">
                                    <td class="py-2 pr-3 font-bold text-ink">{{ $day['date'] }}</td>
                                    <td class="py-2 pr-3">{{ $day['total_ops'] }}</td>
                                    <td class="py-2 pr-3 font-mono">{{ number_format((float) $day['volume'], 0) }}</td>
                                    <td class="py-2 pr-3">{{ $day['active_agents'] }}</td>
                                    <td class="py-2 pr-3">{{ $day['new_agents'] }}</td>
                                    <td class="py-2">{{ $day['success_rate'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-4">
                <div class="panel p-5">
                    <x-kp.section-header eyebrow="Growth (§100–110)" title="Network growth" :description="$p['growth']['basis']" />
                    <div class="mt-4 space-y-2">
                        @foreach ($p['growth']['monthly'] as $month)
                            <div class="flex items-center gap-3">
                                <span class="w-14 font-mono text-[11px] font-bold text-muted">{{ $month['month'] }}</span>
                                <div class="h-3 flex-1 rounded-full bg-panel-2 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-brand to-brand-2" style="width: {{ $p['growth']['current_total'] > 0 ? max(4, round($month['total'] / $p['growth']['current_total'] * 100)) : 0 }}%"></div>
                                </div>
                                <span class="w-16 text-right text-[11px] font-black text-ink">+{{ $month['new'] }} · {{ $month['total'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="panel p-5">
                    <x-kp.section-header eyebrow="Retention" title="Activity & dormancy" :description="$p['retention']['basis']" />
                    <div class="mt-4 grid grid-cols-2 gap-3 text-center sm:grid-cols-3">
                        <div class="rounded-2xl border border-line p-3">
                            <p class="text-xl font-black text-ok">{{ $p['retention']['active_30d'] }}<span class="text-xs text-muted">/{{ $p['retention']['total'] }}</span></p>
                            <p class="mt-0.5 text-[10px] font-black uppercase tracking-wide text-muted">Active 30d</p>
                        </div>
                        <div class="rounded-2xl border border-line p-3">
                            <p class="text-xl font-black text-brand-gold">{{ $p['retention']['dormant_30d'] }}</p>
                            <p class="mt-0.5 text-[10px] font-black uppercase tracking-wide text-muted">Dormant 30d</p>
                        </div>
                        <div class="rounded-2xl border border-line p-3 col-span-2 sm:col-span-1">
                            <p class="text-xl font-black text-ink">{{ $p['retention']['active_rate_30d'] ?? '—' }}%</p>
                            <p class="mt-0.5 text-[10px] font-black uppercase tracking-wide text-muted">Active rate</p>
                        </div>
                    </div>
                </div>

                <div class="panel p-5">
                    <x-kp.section-header eyebrow="Productivity" title="Per active agent" :description="$p['productivity']['basis']" />
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-center">
                        <div class="rounded-2xl border border-line p-3">
                            <dd class="text-xl font-black text-ink">{{ $p['productivity']['ops_per_active_agent'] ?? '—' }}</dd>
                            <dt class="mt-0.5 text-[10px] font-black uppercase tracking-wide text-muted">Ops / agent (30d)</dt>
                        </div>
                        <div class="rounded-2xl border border-line p-3">
                            <dd class="text-xl font-black text-ink">{{ $p['productivity']['volume_per_active_agent'] ?? '—' }}</dd>
                            <dt class="mt-0.5 text-[10px] font-black uppercase tracking-wide text-muted">Volume / agent</dt>
                        </div>
                    </dl>
                </div>
            </div>
        </section>
    @endif
</div>
