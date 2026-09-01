<div class="mx-auto max-w-7xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned. Contact KoriePay admin to link your network." />
    @else
        {{-- ── Header / daily brief (§13) ─────────────────────────────────── --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Network Command</h1>
                <p class="mt-0.5 text-sm text-muted">{{ $aggregator->name }} · {{ $aggregator->code }}</p>
            </div>
            {{-- Global filter bar (§9) — quick ranges --}}
            <div class="flex flex-wrap items-center gap-1.5 rounded-2xl border border-line bg-panel/60 p-1.5 backdrop-blur">
                @foreach ($ranges as $key => $label)
                    <button type="button" wire:click="$set('range', '{{ $key }}')"
                            class="rounded-xl px-3 py-1.5 text-xs font-bold transition {{ $range === $key ? 'bg-brand text-white' : 'text-muted hover:bg-panel-2 hover:text-ink' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        @php $c = $center; $o = $c['overview']; $cur = $o['currency']; @endphp

        {{-- Daily brief card — every sentence derived from real data. --}}
        @if (count($c['brief']) > 0)
            <section class="panel relative overflow-hidden p-5 sm:p-6">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-brand/30 to-transparent"></div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-muted">Network brief</p>
                <div class="mt-2 space-y-1.5 text-sm leading-relaxed text-ink/90">
                    @foreach ($c['brief'] as $sentence)
                        <p>• {{ $sentence }}</p>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ── KPI command center (§10–11) ────────────────────────────────── --}}
        <section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 xl:grid-cols-6">
            <x-kp.metric-card label="Total Agents" value="{{ number_format($o['total_agents']) }}"
                delta="{{ $o['agents_added'] > 0 ? '+'.$o['agents_added'] : '0' }}" deltaNote="added in range"
                icon="users" freshness="authoritative" />

            <x-kp.metric-card label="Active Agents" value="{{ number_format($o['active_agents']) }}"
                delta="{{ $o['active_rate'] }}%" deltaNote="of network" deltaDirection="flat" icon="user-check" />

            <x-kp.metric-card label="Transactions" value="{{ number_format($o['transactions']) }}"
                delta="{{ $this->deltaLabel($o['transactions'], $o['transactions_prev']) }}" deltaNote="vs previous period"
                icon="arrow-right-left" freshness="authoritative" />

            <x-kp.metric-card label="Network Volume" value="{{ $this->money($o['volume'][$cur] ?? 0) }}"
                currency="{{ $cur }}"
                delta="{{ $this->deltaLabel($o['volume'][$cur] ?? 0, $o['volume_prev'][$cur] ?? 0) }}" deltaNote="vs previous period"
                icon="banknotes" freshness="authoritative" />

            <x-kp.metric-card label="Your Commission" value="{{ $this->money($o['commission'][$cur] ?? 0) }}"
                currency="{{ $cur }}"
                delta="{{ $this->deltaLabel($o['commission'][$cur] ?? 0, $o['commission_prev'][$cur] ?? 0) }}" deltaNote="accrued in range"
                icon="trending-up" freshness="authoritative" tone="ok" />

            <x-kp.metric-card label="Network Liquidity" value="{{ $this->money($c['liquidity']['totals'][$cur] ?? 0) }}"
                currency="{{ $cur }}" deltaNote="agent + aggregator float (ledger)" deltaDirection="flat"
                icon="wallet" tone="info" freshness="authoritative" />
        </section>

        {{-- ── EOD snapshot + health signals (Stage I §96–116) ───────────── --}}
        @if ($eod !== null && $observability !== null)
            <section class="panel border-l-2 border-l-brand p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <x-kp.icon name="chart-bar" class="h-5 w-5 text-brand" />
                        <div>
                            <h2 class="text-sm font-extrabold tracking-tight text-ink">End-of-day · {{ $eod['date'] }}</h2>
                            <p class="text-[11px] font-semibold text-muted">From today's real records — not a forecast</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-kp.status-badge :status="$observability['status']" />
                        <a href="{{ route('aggregator.insights') }}" class="rounded-xl border border-line px-3 py-1.5 text-[11px] font-bold text-muted hover:bg-panel-2">Full insights</a>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
                    <x-kp.metric-card label="Today volume" :value="number_format((float) $eod['volume'], 0)" currency="XOF" freshness="authoritative" icon="banknotes" />
                    <x-kp.metric-card label="Transactions" :value="$eod['transactions']" freshness="authoritative" icon="arrow-right-left" />
                    <x-kp.metric-card label="Success rate" :value="$eod['success_rate'].'%'" freshness="authoritative" icon="check-circle" tone="ok" />
                    <x-kp.metric-card label="Commission" :value="number_format((float) $eod['commission_accrued'], 0)" freshness="authoritative" icon="trending-up" />
                    <x-kp.metric-card label="New agents" :value="$eod['new_agents']" freshness="authoritative" icon="user-check" />
                    <x-kp.metric-card label="Open alerts" :value="$eod['open_alerts']" freshness="authoritative" icon="shield-exclamation"
                        :tone="$eod['open_alerts'] > 0 ? 'warn' : 'neutral'" />
                </div>

                @if (count($observability['indicators']) > 0)
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach (collect($observability['indicators'])->reject(fn ($s) => $s['status'] === 'ok')->take(4) as $signal)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-line px-2.5 py-1 text-[11px] font-bold text-ink">
                                <span class="w-1.5 h-1.5 rounded-full {{ $signal['status'] === 'critical' ? 'bg-crit' : ($signal['status'] === 'degraded' ? 'bg-brand-gold' : 'bg-brand-orange') }}"></span>
                                {{ $signal['label'] }} · {{ $signal['value'] }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        {{-- ── Attention center (§12) ─────────────────────────────────────── --}}
        <section class="panel border-l-2 border-l-brand-orange p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-extrabold tracking-tight text-ink">ACTION REQUIRED</h2>
                <span class="rounded-full bg-brand-orange/15 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-brand-orange">
                    {{ count($c['attention']) }} {{ Str::plural('item', count($c['attention'])) }}
                </span>
            </div>
            @if (count($c['attention']) === 0)
                <p class="mt-3 text-xs text-muted">No attention items in your network right now.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($c['attention'] as $alert)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-line bg-panel-2/50 px-3.5 py-2.5">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $alert['severity'] === 'high' ? 'bg-brand-orange' : ($alert['severity'] === 'medium' ? 'bg-brand-gold' : 'bg-ok') }}"></span>
                                <span class="text-xs font-semibold text-ink">{{ $alert['message'] }}</span>
                            </div>
                            <span class="shrink-0 rounded-full border border-line px-2.5 py-1 text-[10px] font-bold text-muted">{{ $alert['action'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- ── Network performance + liquidity + top agents ─────────────── --}}
        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Performance chart (pure CSS bars — no chart lib, low-bandwidth) --}}
            <section class="panel p-5 sm:col-span-2 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-extrabold tracking-tight text-ink">Network Performance</h2>
                        <p class="text-[11px] text-muted">Daily volume in {{ $cur }} · {{ count($c['series']) }} {{ Str::plural('day', count($c['series'])) }}</p>
                    </div>
                </div>
                @if (count($c['series']) === 0 || collect($c['series'])->sum('volume') == 0)
                    <x-kp.empty-state icon="chart-bar" title="No activity in range" description="There are no operations in this period — the chart stays honest." class="mt-4" />
                @else
                    @php
                        $max = max(array_column($c['series'], 'volume')) ?: 1;
                    @endphp
                    <div class="mt-5 flex h-40 items-end gap-1.5">
                        @foreach ($c['series'] as $day)
                            <div class="group flex flex-1 flex-col items-center gap-1">
                                <div class="w-full rounded-t-md bg-gradient-to-t from-brand/20 to-brand/70 transition-all group-hover:from-brand/30 group-hover:to-brand"
                                     style="height: {{ max(2, round(($day['volume'] / $max) * 120)) }}px" title="{{ $day['label'] }}: {{ $this->money($day['volume']) }}"></div>
                                <span class="text-[9px] font-semibold text-muted">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Liquidity snapshot (§23) --}}
            <section class="panel p-5 sm:p-6">
                <h2 class="text-sm font-extrabold tracking-tight text-ink">Liquidity</h2>
                <p class="text-[11px] text-muted">Agent + aggregator float (ledger) · estimates labelled</p>
                <div class="mt-4 space-y-2">
                    @foreach ($c['liquidity']['totals'] as $currency => $total)
                        <div class="flex items-center justify-between rounded-xl border border-line bg-panel-2/50 px-3.5 py-2.5">
                            <span class="text-xs font-bold text-ink">Available liquidity ({{ $currency }})</span>
                            <span class="text-sm font-black text-ink">{{ $this->money($total) }} <span class="text-[10px] font-bold text-muted">{{ $currency }}</span></span>
                        </div>
                    @endforeach
                </div>
                @if (count($c['liquidity']['items']) === 0)
                    <p class="mt-3 text-[11px] text-muted">No agents with float accounts yet.</p>
                @else
                    @php
                        $buckets = collect($c['liquidity']['items'])->groupBy('status')->map->count();
                    @endphp
                    <div class="mt-3 flex flex-wrap gap-1.5 text-[10px] font-bold">
                        <span class="rounded-full bg-ok/15 px-2 py-0.5 text-ok">Healthy {{ $buckets['healthy'] ?? 0 }}</span>
                        <span class="rounded-full bg-brand-gold/15 px-2 py-0.5 text-brand-gold">Watch {{ $buckets['watch'] ?? 0 }}</span>
                        <span class="rounded-full bg-brand-orange/15 px-2 py-0.5 text-brand-orange">Low {{ $buckets['low'] ?? 0 }}</span>
                        <span class="rounded-full bg-crit/15 px-2 py-0.5 text-crit">Critical {{ $buckets['critical'] ?? 0 }}</span>
                    </div>
                @endif
            </section>
        </div>

        {{-- ── Top agents + recent activity ──────────────────────────────── --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="panel p-5 sm:p-6">
                <h2 class="text-sm font-extrabold tracking-tight text-ink">Top Agents</h2>
                <p class="text-[11px] text-muted">By volume in range ({{ $cur }})</p>
                @if (count($c['top_agents']) === 0)
                    <x-kp.empty-state icon="users" title="No agents ranked yet" description="Top performers appear here once operations exist in the selected range." class="mt-4" />
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($c['top_agents'] as $i => $agent)
                            <li class="flex items-center justify-between gap-3 rounded-xl border border-line bg-panel-2/50 px-3.5 py-2.5">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-xs font-black text-brand">{{ $i + 1 }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-bold text-ink">{{ $agent['name'] }}</p>
                                        <p class="text-[10px] font-semibold text-muted">{{ $agent['agent_code'] }} · {{ $agent['transactions'] }} ops</p>
                                    </div>
                                </div>
                                <span class="shrink-0 text-sm font-black text-ink">{{ $this->money($agent['volume']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="panel p-5 sm:p-6">
                <h2 class="text-sm font-extrabold tracking-tight text-ink">Recent Activity</h2>
                <p class="text-[11px] text-muted">Latest network operations</p>
                @if (count($c['recent_activity']) === 0)
                    <x-kp.empty-state icon="clock" title="No recent activity" description="Cash-in / cash-out operations will appear here as your agents process them." class="mt-4" />
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($c['recent_activity'] as $op)
                            <li class="flex items-center justify-between gap-3 rounded-xl border border-line bg-panel-2/50 px-3.5 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-ink">
                                        {{ ucwords(str_replace('_', ' ', $op['type'])) }}
                                        <span class="font-semibold text-muted">· {{ $op['agent_name'] }}</span>
                                    </p>
                                    <p class="text-[10px] font-semibold text-muted">{{ $op['reference'] }} · {{ \Illuminate\Support\Carbon::parse($op['created_at'])->diffForHumans() }}</p>
                                </div>
                                <span class="shrink-0 text-sm font-black text-ink">{{ $this->money($op['amount']) }} <span class="text-[10px] font-bold text-muted">{{ $op['currency'] }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        {{-- Data freshness (§108) — authoritative, computed live. --}}
        <p class="pb-2 text-center text-[10px] font-semibold uppercase tracking-widest text-muted">
            Updated {{ now()->diffForHumans(['parts' => 1]) }} · authoritative (live from ledger & operations)
        </p>
    @endif
</div>
