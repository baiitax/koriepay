<div class="mx-auto max-w-7xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned. Contact KoriePay admin to link your network." />
    @else
        @php $p = $payload; @endphp

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Risk & alerts</h1>
                <p class="mt-0.5 text-sm text-muted">Velocity monitoring, pattern signals and the alert workflow — signals are never fraud conclusions (§52–57).</p>
            </div>
            @if ($p['notifications']['total'] > 0)
                <span class="rounded-full bg-crit/10 px-3.5 py-1.5 text-xs font-black text-crit">{{ $p['notifications']['total'] }} open alert{{ $p['notifications']['total'] === 1 ? '' : 's' }}</span>
            @endif
        </div>

        {{-- Notifications — grouped + deduplicated --}}
        <section class="panel p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-black text-ink">Notifications</h2>
                <span class="text-[11px] text-muted">{{ $p['notifications']['basis'] }}</span>
            </div>
            @if (count($p['notifications']['groups']) === 0)
                <p class="mt-3 text-sm text-muted">No open alerts — nothing to notify.</p>
            @else
                <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($p['notifications']['groups'] as $group)
                        <div class="rounded-xl border border-line bg-white/40 px-3.5 py-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-wide text-muted">{{ $group['category'] }}</span>
                                <x-kp.risk-badge :level="$group['severity']" />
                            </div>
                            <p class="mt-1 text-sm font-black text-ink">{{ $group['count'] }} alert{{ $group['count'] === 1 ? '' : 's' }}</p>
                            <p class="text-[11px] text-muted">latest {{ $group['latest'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Alerts --}}
        <section class="panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                <h2 class="font-black text-ink">Alert center</h2>
                <div class="flex flex-wrap gap-1.5">
                    @foreach (['all' => 'All', 'open' => 'Open', 'acknowledged' => 'Assigned', 'investigating' => 'Investigating', 'resolved' => 'Resolved', 'false_positive' => 'False positive'] as $key => $label)
                        <button type="button" wire:click="setAlertStatus('{{ $key }}')"
                                class="rounded-lg px-2.5 py-1.5 text-[11px] font-black transition {{ $alertStatus === $key ? 'bg-brand text-white' : 'border border-line text-muted hover:bg-panel-2 hover:text-ink' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            @if (count($p['alerts']) === 0)
                <x-kp.empty-state icon="shield" title="No alerts" description="No alerts match the current filter." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-line text-[10px] font-black uppercase tracking-[0.14em] text-muted">
                                <th class="px-4 py-3">Alert</th>
                                <th class="px-4 py-3">Severity</th>
                                <th class="px-4 py-3">Affected</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">When</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($p['alerts'] as $alert)
                                <tr class="hover:bg-panel-2/60">
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-xs font-bold text-ink">{{ $alert['reference'] }}</span>
                                        <p class="max-w-xs text-[12px] text-muted">{{ $alert['message'] }}</p>
                                        @if ($alert['resolution_note'])
                                            <p class="text-[10px] font-semibold text-muted/70">note: {{ $alert['resolution_note'] }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3"><x-kp.risk-badge :level="$alert['severity']" /></td>
                                    <td class="px-4 py-3">
                                        @if ($alert['affected']['name'])
                                            <span class="font-bold text-ink">{{ $alert['affected']['name'] }}</span>
                                            <span class="block font-mono text-[11px] text-muted">{{ $alert['affected']['code'] }}</span>
                                        @else
                                            <span class="text-muted">{{ ucfirst($alert['affected']['type']) }} #{{ $alert['affected']['code'] ?? '' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3"><x-kp.status-badge :status="$alert['status']" /></td>
                                    <td class="px-4 py-3 text-[11px] text-muted">{{ $alert['created_at']->diffForHumans() }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-1.5">
                                            @if (in_array($alert['status'], ['open', 'acknowledged', 'investigating'], true))
                                                @if ($alert['status'] === 'open')
                                                    <button type="button" wire:click="assign({{ $alert['id'] }})" class="rounded-lg border border-line px-2.5 py-1.5 text-[11px] font-black text-muted transition hover:bg-panel-2 hover:text-ink">Assign</button>
                                                @endif
                                                @if ($alert['status'] !== 'investigating')
                                                    <button type="button" wire:click="investigate({{ $alert['id'] }})" class="rounded-lg border border-line px-2.5 py-1.5 text-[11px] font-black text-muted transition hover:bg-panel-2 hover:text-ink">Investigate</button>
                                                @endif
                                                <input type="text" wire:model="notes.{{ $alert['id'] }}" placeholder="Note…" class="w-24 rounded-lg border border-line bg-white/60 px-2 py-1.5 text-[11px] font-semibold text-ink outline-none focus:border-brand">
                                                <button type="button" wire:click="resolve({{ $alert['id'] }})" class="rounded-lg bg-ok px-2.5 py-1.5 text-[11px] font-black text-white transition hover:bg-ok/90">Resolve</button>
                                                <button type="button" wire:click="falsePositive({{ $alert['id'] }})" class="rounded-lg border border-line px-2.5 py-1.5 text-[11px] font-black text-muted transition hover:bg-panel-2 hover:text-ink">False +</button>
                                            @else
                                                <span class="text-[11px] font-bold text-muted">{{ $alert['status'] === 'resolved' ? 'Resolved' : 'False positive' }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Velocity --}}
            <section class="panel p-5">
                <h2 class="font-black text-ink">Velocity monitoring</h2>
                <p class="text-xs text-muted">{{ $p['velocity']['basis'] }}</p>
                @if (count($p['velocity']['rows']) === 0)
                    <p class="mt-3 text-sm text-muted">No agent activity in the last 24 hours.</p>
                @else
                    <div class="mt-3 space-y-2">
                        @foreach ($p['velocity']['rows'] as $row)
                            <div class="flex items-center gap-3 rounded-xl border border-line bg-white/40 px-3.5 py-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $row['flag'] === 'elevated' ? 'bg-brand-orange/10 text-brand-orange' : 'bg-ok/10 text-ok' }}">
                                    <x-kp.icon :name="$row['flag'] === 'elevated' ? 'exclamation-triangle' : 'check-circle'" class="h-4 w-4" stroke="2.2" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-ink">{{ $row['name'] }} <span class="font-mono text-[10px] text-muted">{{ $row['agent_code'] }}</span></p>
                                    <p class="text-[11px] text-muted">{{ $row['ops_24h'] }} ops · {{ $row['ops_per_hour'] }}/h · {{ number_format((float) $row['volume_24h'], 0) }} volume</p>
                                    @foreach ($row['reasons'] as $reason)
                                        <p class="text-[10px] font-bold text-brand-orange">• {{ $reason }}</p>
                                    @endforeach
                                </div>
                                <span class="ml-auto rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $row['flag'] === 'elevated' ? 'bg-brand-orange/10 text-brand-orange' : 'bg-ok/10 text-ok' }}">{{ $row['flag'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Collusion signals --}}
            <section class="panel p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-black text-ink">Collusion signals</h2>
                    <span class="rounded-full bg-brand-gold/15 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-brand-gold">Risk signal</span>
                </div>
                <p class="text-xs font-bold text-brand-gold">{{ $p['signals']['warning'] }}</p>
                <p class="mt-0.5 text-xs text-muted">{{ $p['signals']['basis'] }}</p>
                @if (count($p['signals']['signals']) === 0)
                    <p class="mt-3 text-sm text-muted">No pattern signals detected in the window.</p>
                @else
                    <div class="mt-3 space-y-2">
                        @foreach ($p['signals']['signals'] as $signal)
                            <div class="rounded-xl border border-brand-gold/30 bg-brand-gold/5 px-3.5 py-2.5">
                                <p class="text-sm font-bold text-ink">{{ $signal['label'] }}</p>
                                <p class="text-[11px] text-muted">Customer #{{ $signal['customer_user_id'] }} · agents {{ implode(', ', $signal['agents']) }} · observed {{ $signal['observed_delta_minutes'] }} min gap · {{ $signal['ops_count'] }} ops</p>
                                <p class="mt-1 text-[10px] font-bold text-brand-gold">{{ $signal['disclaimer'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- KYC inconsistencies --}}
        <section class="panel p-5">
            <h2 class="font-black text-ink">KYC inconsistencies</h2>
            <p class="text-xs text-muted">{{ $p['kyc']['basis'] }}</p>
            @if (count($p['kyc']['rows']) === 0)
                <p class="mt-3 text-sm text-muted">No inconsistencies between agent and user KYC records.</p>
            @else
                <div class="mt-3 space-y-2">
                    @foreach ($p['kyc']['rows'] as $row)
                        <div class="flex items-center gap-3 rounded-xl border border-brand-orange/30 bg-brand-orange/5 px-3.5 py-2.5">
                            <x-kp.icon name="exclamation-triangle" class="h-4 w-4 text-brand-orange" stroke="2.2" />
                            <p class="text-sm font-bold text-ink">{{ $row['name'] }} <span class="font-mono text-[10px] text-muted">{{ $row['agent_code'] }}</span> — {{ $row['message'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>
