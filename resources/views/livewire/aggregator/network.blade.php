<div class="mx-auto max-w-7xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned. Contact KoriePay admin to link your network." />
    @else
        @php $p = $payload; $a = $p['analytics']; @endphp

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Network intelligence</h1>
                <p class="mt-0.5 text-sm text-muted">Volume, value, activity and coverage — all derived from real records (§44–51).</p>
            </div>
            <div class="flex items-center gap-1.5 rounded-2xl border border-line bg-panel/60 p-1.5 backdrop-blur">
                @foreach (['hourly' => 'Hourly', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $key => $label)
                    <button type="button" wire:click="setRange('{{ $key }}')"
                            class="rounded-xl px-3 py-1.5 text-xs font-black transition {{ $range === $key ? 'bg-brand text-white' : 'text-muted hover:bg-panel-2 hover:text-ink' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Health score --}}
        @if ($p['health']['score'] === null)
            <div class="panel p-5">
                <p class="text-sm text-muted">No network activity on record — a health score would be fabricated, so none is shown.</p>
            </div>
        @else
            <section class="panel p-5">
                <div class="flex flex-wrap items-center gap-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full border-4 {{ $p['health']['score'] >= 80 ? 'border-ok' : ($p['health']['score'] >= 60 ? 'border-brand-gold' : ($p['health']['score'] >= 40 ? 'border-brand-orange' : 'border-crit')) }}">
                            <span class="font-mono text-2xl font-black text-ink">{{ $p['health']['score'] }}</span>
                        </div>
                        <div>
                            <p class="text-lg font-black text-ink">Network health · {{ $p['health']['label'] }}</p>
                            <p class="max-w-md text-xs text-muted">{{ $p['health']['explanation'] }}</p>
                        </div>
                    </div>
                    <div class="grid flex-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($p['health']['components'] as $c)
                            <div class="rounded-xl border border-line bg-white/40 p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase tracking-wide text-muted">{{ ucfirst(str_replace('_', ' ', $c['key'])) }}</span>
                                    <span class="font-mono text-sm font-black text-ink">{{ $c['score'] }}</span>
                                </div>
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-panel-2">
                                    <div class="h-full rounded-full bg-brand" style="width: {{ $c['score'] }}%"></div>
                                </div>
                                <p class="mt-1.5 text-[10px] leading-snug text-muted">{{ $c['formula'] }} · weight {{ $c['weight'] }}%</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Summary KPIs --}}
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['Volume', $a['summary']['volume']],
                ['Operations', (string) $a['summary']['count']],
                ['Active agents', (string) $a['summary']['active_agents']],
                ['Average per agent', $a['summary']['average_per_agent']],
                ['Reversals', (string) $a['summary']['reversals']],
            ] as [$label, $value])
                <div class="panel p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-muted">{{ $label }}</p>
                    <p class="mt-1 truncate font-mono text-lg font-black text-ink">{{ $value }}</p>
                </div>
            @endforeach
        </section>

        {{-- Trend --}}
        <section class="panel p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-black text-ink">Trend — {{ ucfirst($range) }}</h2>
                <span class="text-[11px] text-muted">success {{ $a['summary']['success_rate'] ?? '—' }}% · failure {{ $a['summary']['failure_rate'] ?? '—' }}%</span>
            </div>
            @if (count($a['buckets']) === 0)
                <p class="mt-3 text-sm text-muted">No operations in this window.</p>
            @else
                <div class="mt-4 flex items-end gap-1.5 overflow-x-auto pb-1">
                    @php $max = max(array_column($a['buckets'], 'volume')) ?: 1; @endphp
                    @foreach ($a['buckets'] as $b)
                        <div class="flex min-w-[44px] flex-col items-center gap-1">
                            <div class="flex h-28 w-full items-end rounded-lg bg-panel-2">
                                <div class="w-full rounded-lg bg-gradient-to-t from-brand to-brand-2" style="height: {{ max(4, $b['volume'] / $max * 100) }}%"></div>
                            </div>
                            <span class="text-[9px] font-bold text-muted">{{ $b['bucket'] }}</span>
                            <span class="text-[9px] font-black text-ink">{{ number_format((float) $b['volume'], 0) }}</span>
                            <span class="text-[9px] text-muted">{{ $b['count'] }} ops · {{ $b['active_agents'] }} agents</span>
                        </div>
                    @endforeach
                </div>
            @endif
            <p class="mt-2 text-[10px] text-muted">{{ $a['summary']['basis'] }}</p>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Failure intelligence --}}
            <section class="panel p-5">
                <h2 class="font-black text-ink">Failure intelligence</h2>
                <p class="text-xs text-muted">{{ $p['failures']['basis'] }}</p>
                @if (count($p['failures']['causes']) === 0)
                    <p class="mt-3 text-sm text-muted">No failed operations on record.</p>
                @else
                    <div class="mt-3 space-y-2">
                        @foreach ($p['failures']['causes'] as $cause)
                            <div class="flex items-center gap-3 rounded-xl border border-line bg-white/40 px-3.5 py-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $cause['recorded'] ? 'bg-crit/10 text-crit' : 'bg-panel-2 text-muted' }}">
                                    <x-kp.icon name="x-circle" class="h-4 w-4" stroke="2.2" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-ink">{{ ucfirst(str_replace('_', ' ', $cause['cause'])) }}</p>
                                    <p class="text-[11px] text-muted">{{ $cause['count'] }} ops · {{ $cause['affected_agents'] }} agents · latest {{ $cause['latest_reference'] }}</p>
                                </div>
                                <div class="ml-auto text-right">
                                    <p class="font-mono font-black text-ink">{{ $cause['share'] }}%</p>
                                    <p class="text-[10px] font-bold text-muted">{{ number_format((float) $cause['amount'], 0) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Coverage + recommendations --}}
            <section class="panel p-5">
                <h2 class="font-black text-ink">Coverage</h2>
                <p class="text-xs text-muted">{{ $p['coverage']['basis'] }}</p>
                @if (count($p['coverage']['cities']) === 0)
                    <p class="mt-3 text-sm text-muted">No cities on record.</p>
                @else
                    <div class="mt-3 space-y-2">
                        @foreach ($p['coverage']['cities'] as $city)
                            <div class="flex items-center gap-3 rounded-xl border border-line bg-white/40 px-3.5 py-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                                    <x-kp.icon name="map-pin" class="h-4 w-4" stroke="2.2" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-ink">{{ $city['city'] }} <span class="text-[10px] font-semibold text-muted">{{ $city['region'] }}</span></p>
                                    <p class="text-[11px] text-muted">{{ $city['agents'] }} agents · out {{ number_format((float) $city['cash_out_7d'], 0) }} / in {{ number_format((float) $city['cash_in_7d'], 0) }} (7d)</p>
                                </div>
                                <p class="ml-auto text-right text-[11px] font-bold text-muted">demand/agent<br><span class="font-mono text-ink">{{ $city['demand_per_agent'] ?? '—' }}</span></p>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-4">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.16em] text-muted">Recruitment recommendations</h3>
                    @forelse ($p['coverage']['recommendations'] as $rec)
                        <div class="mt-2 rounded-xl border {{ $rec['type'] === 'recruit' ? 'border-brand/25 bg-brand/5' : 'border-brand-gold/30 bg-brand-gold/10' }} px-3.5 py-2.5 text-xs">
                            <p class="font-bold text-ink">{{ $rec['message'] }}</p>
                            @if (($rec['estimate'] ?? false))
                                <p class="mt-0.5 text-[10px] font-black uppercase tracking-wide text-brand-gold">Estimate · {{ $rec['basis'] }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="mt-2 text-xs text-muted">No recruitment signals — demand is covered in every active city.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif
</div>
