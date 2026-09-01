<div class="mx-auto max-w-7xl space-y-6">
    @php
        $agent = $payload['agent'];
        $user = $payload['user'] ?? null;
        $tabs = [
            'overview' => 'Overview', 'kyc' => 'KYC', 'transactions' => 'Transactions',
            'liquidity' => 'Liquidity', 'commissions' => 'Commissions', 'performance' => 'Performance',
            'risk' => 'Risk', 'devices' => 'Devices', 'support' => 'Support', 'audit' => 'Audit',
        ];
    @endphp

    <div>
        <a href="{{ route('aggregator.agents') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-muted transition hover:text-ink">
            <x-kp.icon name="chevron-right" class="h-3.5 w-3.5 -scale-x-100" stroke="2.4" /> Back to agents
        </a>
    </div>

    {{-- Agent header (§15) — identity + backend-controlled status actions. --}}
    <section class="panel p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-lg font-black text-brand">
                    {{ collect(explode(' ', $user?->name ?? 'A'))->take(2)->map(fn ($w) => strtoupper(substr($w, 0, 1)))->join('') }}
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black tracking-tight text-ink">{{ $user?->name ?? 'Unnamed agent' }}</h1>
                        <x-kp.status-badge :status="$agent->status" />
                        @if ($agent->tier)
                            <span class="rounded-full border border-line bg-panel-2 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-muted">{{ $agent->tier }}</span>
                        @endif
                    </div>
                    <p class="mt-1 font-mono text-sm text-muted">
                        {{ $agent->agent_code }}
                        @if ($user?->phone_number) · {{ $user->phone_number }} @endif
                        @if ($user?->email) · {{ $user->email }} @endif
                    </p>
                    <p class="mt-0.5 text-xs text-muted">
                        {{ trim(implode(', ', array_filter([$agent->city, $agent->region, $agent->country_iso2]))) ?: '—' }}
                        · joined {{ $agent->created_at?->format('M j, Y') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if ($canSuspend && in_array($agent->status, ['active', 'pending'], true))
                    <button type="button" wire:click="suspend"
                            class="rounded-xl border border-brand-orange/40 px-3.5 py-2 text-xs font-black text-brand-orange transition hover:bg-brand-orange/10">
                        Suspend
                    </button>
                @endif
                @if ($canReactivate && $agent->status === 'suspended')
                    <button type="button" wire:click="reactivate"
                            class="rounded-xl bg-ok px-3.5 py-2 text-xs font-black text-white transition hover:bg-ok/90">
                        Reactivate
                    </button>
                @endif
            </div>
        </div>

        {{-- Tab bar (§15–22). --}}
        <nav class="mt-5 flex gap-1 overflow-x-auto border-t border-line pt-3" aria-label="Agent profile tabs">
            @foreach ($tabs as $key => $label)
                <button type="button" wire:click="switchTab('{{ $key }}')"
                        class="whitespace-nowrap rounded-xl px-3.5 py-2 text-xs font-black transition {{ $tab === $key ? 'bg-brand text-white' : 'text-muted hover:bg-panel-2 hover:text-ink' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </section>

    {{-- ── Overview ─────────────────────────────────────────────────────── --}}
    @if ($tab === 'overview')
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="panel p-5">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Floats · ledger authoritative</p>
                <div class="mt-3 space-y-3">
                    @forelse ($payload['floats'] as $float)
                        <div class="flex items-center justify-between rounded-2xl border border-line bg-white/40 px-4 py-3">
                            <div>
                                <p class="font-mono text-sm font-black text-ink">{{ number_format((float) $float['balance'], 2) }}</p>
                                <p class="text-[11px] font-bold text-muted">{{ $float['currency'] }} · {{ $float['name'] }}</p>
                            </div>
                            <x-kp.status-badge status="operational" label="Live" />
                        </div>
                    @empty
                        <p class="text-sm text-muted">No float ledger account on file.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel p-5">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">30-day activity</p>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-line bg-white/40 p-3">
                        <p class="text-xl font-black text-ink">{{ $payload['counts']['posted_30d'] }}</p>
                        <p class="text-[11px] font-bold text-muted">Posted ops</p>
                    </div>
                    <div class="rounded-2xl border border-line bg-white/40 p-3">
                        <p class="text-xl font-black text-ink">{{ number_format((float) $payload['counts']['volume_30d'], 0) }}</p>
                        <p class="text-[11px] font-bold text-muted">Volume {{ $payload['currency'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-line bg-white/40 p-3">
                        <p class="text-xl font-black text-ink">{{ number_format((float) $payload['counts']['commissions_30d'], 0) }}</p>
                        <p class="text-[11px] font-bold text-muted">Commissions accrued</p>
                    </div>
                    <div class="rounded-2xl border border-line bg-white/40 p-3">
                        <p class="text-xl font-black {{ $payload['counts']['open_alerts'] > 0 ? 'text-brand-orange' : 'text-ink' }}">{{ $payload['counts']['open_alerts'] }}</p>
                        <p class="text-[11px] font-bold text-muted">Open alerts</p>
                    </div>
                </div>
                @if ($payload['dormancy'] !== null)
                    <div class="mt-3 rounded-2xl border border-brand-gold/30 bg-brand-gold/10 p-3 text-xs font-bold text-ink">
                        {{ $payload['dormancy']['label'] }} — {{ $payload['dormancy']['note'] }}
                    </div>
                @endif
            </section>

            <section class="panel p-5">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Recent operations</p>
                <div class="mt-3 space-y-2">
                    @forelse ($payload['recent_ops'] as $op)
                        <div class="flex items-center justify-between rounded-xl border border-line bg-white/40 px-3 py-2">
                            <span class="text-xs font-bold {{ $op->status === 'posted' ? 'text-ok' : 'text-brand-orange' }}">{{ ucfirst($op->operation_type) }}</span>
                            <span class="font-mono text-xs font-bold text-ink">{{ number_format((float) $op->amount, 0) }} {{ $op->currency_code }}</span>
                            <span class="text-[11px] text-muted">{{ $op->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-muted">No operations on record.</p>
                    @endforelse
                </div>
            </section>
        </div>

    {{-- ── KYC ─────────────────────────────────────────────────────────── --}}
    @elseif ($tab === 'kyc')
        <section class="panel mx-auto max-w-2xl p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Verification state</p>
                @if ($payload['status'] === 'verified')
                    <x-kp.status-badge status="verified" />
                @elseif ($payload['status'] === 'pending')
                    <x-kp.status-badge status="pending" />
                @else
                    <x-kp.status-badge status="new" label="Unverified" />
                @endif
            </div>
            <h2 class="mt-2 text-2xl font-black text-ink">{{ $payload['label'] }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-muted">{{ $payload['note'] }}</p>

            <div class="mt-5 rounded-2xl border border-line bg-white/40 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wide text-muted">Last identity submission</p>
                @if ($payload['submission'])
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        <span class="font-bold text-ink">{{ ucfirst($payload['submission']['type']) }}</span>
                        <x-kp.status-badge :status="$payload['submission']['status']" />
                        <span class="text-xs text-muted">{{ $payload['submission']['submitted_at']?->format('M j, Y H:i') }}</span>
                    </div>
                @else
                    <p class="mt-2 text-sm text-muted">No identity submission on file.</p>
                @endif
            </div>

            <p class="mt-4 text-[11px] font-bold text-muted">Recorded tier: {{ $payload['tier'] }} · source: <span class="font-mono">kyc_submissions + agents.kyc_status</span></p>
        </section>

    {{-- ── Transactions ────────────────────────────────────────────────── --}}
    @elseif ($tab === 'transactions')
        <section class="panel overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-black text-ink">Transactions</h2>
                <p class="text-xs text-muted">{{ $payload['total'] }} operation{{ $payload['total'] === 1 ? '' : 's' }} for this agent</p>
            </div>
            @if ($payload['total'] === 0)
                <x-kp.empty-state icon="arrow-right-left" title="No transactions" description="No agency operations are recorded for this agent." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-line text-[10px] font-black uppercase tracking-[0.14em] text-muted">
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-right">Commission</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($payload['rows'] as $op)
                                <tr class="hover:bg-panel-2/60">
                                    <td class="px-4 py-3 font-mono text-xs text-muted">{{ $op->reference }}</td>
                                    <td class="px-4 py-3 text-[13px] font-bold text-ink">{{ ucfirst($op->operation_type) }}</td>
                                    <td class="px-4 py-3 text-[13px] text-muted">{{ $op->customer?->name ?? '#' . $op->customer_user_id }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-[13px] font-bold text-ink">{{ number_format((float) $op->amount, 0) }} {{ $op->currency_code }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-[13px] text-muted">{{ number_format((float) $op->commission_amount, 0) }}</td>
                                    <td class="px-4 py-3">
                                        <x-kp.status-badge :status="$op->status === 'posted' ? 'completed' : 'failed'" :label="$op->status" />
                                    </td>
                                    <td class="px-4 py-3 text-[12px] text-muted">{{ $op->created_at->format('M j, H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($payload['total'] > $payload['per_page'])
                    <div class="flex items-center justify-between border-t border-line px-4 py-3">
                        <p class="text-[11px] font-semibold text-muted">Showing page {{ $payload['page'] }}</p>
                        <div class="flex gap-1.5">
                            <button type="button" wire:click="gotoPage({{ $payload['page'] - 1 }})" @disabled($payload['page'] <= 1)
                                    class="rounded-lg border border-line px-2.5 py-1.5 text-xs font-bold text-muted transition enabled:hover:bg-panel-2 disabled:opacity-40">Prev</button>
                            <button type="button" wire:click="gotoPage({{ $payload['page'] + 1 }})" @disabled($payload['page'] * $payload['per_page'] >= $payload['total'])
                                    class="rounded-lg border border-line px-2.5 py-1.5 text-xs font-bold text-muted transition enabled:hover:bg-panel-2 disabled:opacity-40">Next</button>
                        </div>
                    </div>
                @endif
            @endif
        </section>

    {{-- ── Liquidity ───────────────────────────────────────────────────── --}}
    @elseif ($tab === 'liquidity')
        <section class="panel mx-auto max-w-2xl p-5 sm:p-6">
            <h2 class="font-black text-ink">Agent float</h2>
            <p class="mt-0.5 text-xs text-muted">{{ $payload['note'] }}</p>
            <div class="mt-4 space-y-3">
                @forelse ($payload['accounts'] as $float)
                    <div class="flex items-center justify-between rounded-2xl border border-line bg-white/40 px-4 py-4">
                        <div class="flex items-center gap-3">
                            <span class="rounded-xl bg-brand/10 p-2 text-brand"><x-kp.icon name="wallet" class="h-4 w-4" stroke="2" /></span>
                            <div>
                                <p class="font-mono text-lg font-black text-ink">{{ number_format((float) $float['balance'], 2) }} {{ $float['currency'] }}</p>
                                <p class="text-[11px] font-bold text-muted">{{ $float['name'] }}</p>
                            </div>
                        </div>
                        <x-kp.status-badge status="operational" label="Authoritative" />
                    </div>
                @empty
                    <x-kp.empty-state icon="wallet" title="No float accounts" description="No float ledger account is provisioned for this agent." />
                @endforelse
            </div>
        </section>

    {{-- ── Commissions ─────────────────────────────────────────────────── --}}
    @elseif ($tab === 'commissions')
        <section class="panel overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-black text-ink">Commissions</h2>
                <p class="mt-1 text-xs text-muted">
                    Accrued
                    @foreach ($payload['totals'] as $currency => $total)
                        <span class="ml-2 rounded-full bg-ok/15 px-2.5 py-1 font-mono font-black text-ok">{{ number_format((float) $total, 2) }} {{ $currency }}</span>
                    @endforeach
                </p>
            </div>
            @if ($payload['total'] === 0)
                <x-kp.empty-state icon="banknotes" title="No commissions accrued" description="This agent has no commission entries yet." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-line text-[10px] font-black uppercase tracking-[0.14em] text-muted">
                                <th class="px-4 py-3">Entry</th>
                                <th class="px-4 py-3">Currency</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($payload['rows'] as $entry)
                                <tr class="hover:bg-panel-2/60">
                                    <td class="px-4 py-3 font-mono text-xs text-muted">#{{ $entry->id }}</td>
                                    <td class="px-4 py-3 text-[13px] font-bold text-ink">{{ $entry->currency_code }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-ink">{{ number_format((float) $entry->amount, 2) }}</td>
                                    <td class="px-4 py-3"><x-kp.status-badge :status="$entry->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

    {{-- ── Performance (§17–19) ────────────────────────────────────────── --}}
    @elseif ($tab === 'performance')
        @php $score = $payload['score']; @endphp
        <section class="panel mx-auto max-w-2xl p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Performance score · {{ $payload['window'] }}</p>
                <span class="rounded-full border border-line bg-panel-2 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-muted">
                    {{ $score['basis'] === 'authoritative' ? 'Full basis' : 'Partial basis' }}
                </span>
            </div>

            @if ($score === null)
                <x-kp.empty-state icon="gauge" title="Insufficient data"
                    description="This agent has no operations, risk posture or KYC state in the window — there is nothing real to score yet." />
            @else
                <div class="mt-3 flex items-end gap-4">
                    <p class="text-6xl font-black leading-none tracking-tight text-ink">{{ $score['score'] }}</p>
                    <div class="pb-1">
                        <p class="text-sm font-black text-brand">{{ $score['label'] }}</p>
                        <p class="text-[11px] font-bold text-muted">/ 100 · explainable composite</p>
                    </div>
                </div>

                <div class="mt-5 space-y-2.5">
                    @foreach ($score['components'] as $component)
                        <div class="rounded-2xl border border-line bg-white/40 p-3.5">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-black text-ink">{{ $component['label'] }}</p>
                                <p class="font-mono text-xs font-bold text-muted">
                                    {{ $component['points'] }} / {{ $component['max'] }} · weight {{ $component['weight'] }}%
                                </p>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-line">
                                <div class="h-full rounded-full bg-brand transition-all" style="width: {{ $component['points'] }}%"></div>
                            </div>
                            <p class="mt-1.5 text-[11px] leading-relaxed text-muted">{{ $component['explanation'] }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Productivity (§18). --}}
                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
                    @php $prod = $payload['productivity']; @endphp
                    <div class="rounded-2xl border border-line bg-white/40 p-3 text-center">
                        <p class="text-lg font-black text-ink">{{ $prod['ops_30d'] }}</p>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Posted ops</p>
                    </div>
                    <div class="rounded-2xl border border-line bg-white/40 p-3 text-center">
                        <p class="text-lg font-black text-ink">{{ $prod['active_days_30d'] }}</p>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Active days</p>
                    </div>
                    <div class="rounded-2xl border border-line bg-white/40 p-3 text-center">
                        <p class="text-lg font-black text-ink">{{ $prod['ops_per_active_day'] ?? '—' }}</p>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Ops / day</p>
                    </div>
                    <div class="rounded-2xl border border-line bg-white/40 p-3 text-center">
                        <p class="text-lg font-black text-ink">{{ $prod['distinct_customers_30d'] }}</p>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Customers</p>
                    </div>
                    <div class="rounded-2xl border border-line bg-white/40 p-3 text-center">
                        <p class="text-lg font-black text-ink">{{ number_format((float) $prod['avg_value'], 0) }}</p>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Avg value</p>
                    </div>
                </div>

                {{-- Dormancy (§19) — labelled estimates only. --}}
                @if ($payload['dormancy'] !== null)
                    @php $d = $payload['dormancy']; @endphp
                    <div class="mt-5 rounded-2xl border border-brand-gold/30 bg-brand-gold/10 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-black text-ink">{{ $d['label'] }}</p>
                            <x-kp.status-badge status="attention" :label="ucfirst($d['status'])" />
                        </div>
                        <p class="mt-1 text-xs text-muted">{{ $d['note'] }}</p>
                        @if ($d['estimate'] !== null)
                            <p class="mt-2 text-xs font-bold text-ink">
                                <span class="mr-1 rounded bg-brand-gold/20 px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wide text-brand-gold">Estimate</span>
                                ~{{ number_format((float) $d['estimate']['weekly_volume'], 0) }} {{ $d['estimate']['currency'] }} / week
                                <span class="font-medium text-muted">— {{ $d['estimate']['explanation'] }}</span>
                            </p>
                        @endif
                    </div>
                @endif
            @endif
        </section>

    {{-- ── Risk ────────────────────────────────────────────────────────── --}}
    @elseif ($tab === 'risk')
        <section class="panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                <div>
                    <h2 class="font-black text-ink">Risk & alerts</h2>
                    <p class="text-xs text-muted">
                        {{ $payload['total'] }} alert{{ $payload['total'] === 1 ? '' : 's' }} ·
                        {{ $payload['open'] }} open ·
                        risk score {{ $payload['risk_score'] ?? 'not set' }}
                    </p>
                </div>
                @if ($payload['risk_score'] !== null)
                    <x-kp.risk-badge :level="$payload['risk_score'] >= 70 ? 'critical' : ($payload['risk_score'] >= 50 ? 'high' : ($payload['risk_score'] >= 30 ? 'medium' : 'low'))" />
                @endif
            </div>
            @if ($payload['total'] === 0)
                <x-kp.empty-state icon="shield-check" title="No alerts" description="No risk alerts are recorded for this agent." />
            @else
                <div class="divide-y divide-line">
                    @foreach ($payload['rows'] as $alert)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-ink">{{ $alert->message ?: ucfirst($alert->category) }}</p>
                                <p class="mt-0.5 text-[11px] text-muted">
                                    {{ $alert->reference }} · {{ $alert->category }} · score {{ $alert->risk_score }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-kp.risk-badge :level="$alert->severity" />
                                <x-kp.status-badge :status="$alert->status" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    {{-- ── Devices (honest) ────────────────────────────────────────────── --}}
    @elseif ($tab === 'devices')
        <section class="panel mx-auto max-w-2xl p-5 sm:p-6">
            <h2 class="font-black text-ink">Devices</h2>
            <div class="mt-4">
                <x-kp.empty-state icon="lock" title="No device telemetry"
                    description="{{ $payload['note'] }}" />
            </div>
        </section>

    {{-- ── Support ─────────────────────────────────────────────────────── --}}
    @elseif ($tab === 'support')
        <section class="panel overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-black text-ink">Support tickets</h2>
                <p class="text-xs text-muted">{{ count($payload['tickets']) }} ticket{{ count($payload['tickets']) === 1 ? '' : 's' }} linked to this agent's account</p>
            </div>
            @if (count($payload['tickets']) === 0)
                <x-kp.empty-state icon="chat" title="No support tickets" description="No support tickets are linked to this agent's user account." />
            @else
                <div class="divide-y divide-line">
                    @foreach ($payload['tickets'] as $ticket)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-ink">{{ $ticket->subject }}</p>
                                <p class="mt-0.5 text-[11px] text-muted">{{ $ticket->ticket_id }} · {{ $ticket->category }} · {{ $ticket->created_at->format('M j, Y') }}</p>
                            </div>
                            <x-kp.status-badge :status="$ticket->status" />
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    {{-- ── Audit ───────────────────────────────────────────────────────── --}}
    @elseif ($tab === 'audit')
        <section class="panel overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-black text-ink">Audit trail</h2>
                <p class="text-xs text-muted">{{ $payload['total'] }} event{{ $payload['total'] === 1 ? '' : 's' }} on this agent</p>
            </div>
            @if ($payload['total'] === 0)
                <x-kp.empty-state icon="clipboard" title="No audit events" description="No audited events reference this agent yet." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-line text-[10px] font-black uppercase tracking-[0.14em] text-muted">
                                <th class="px-4 py-3">When</th>
                                <th class="px-4 py-3">Action</th>
                                <th class="px-4 py-3">By</th>
                                <th class="px-4 py-3">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($payload['rows'] as $log)
                                <tr class="hover:bg-panel-2/60">
                                    <td class="px-4 py-3 text-[12px] text-muted">{{ $log->created_at->format('M j, H:i') }}</td>
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-ink">{{ $log->action }}</td>
                                    <td class="px-4 py-3 text-[12px] text-muted">{{ $log->user_name }}</td>
                                    <td class="px-4 py-3 text-[12px] text-muted">{{ $log->description }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($payload['total'] > 10)
                    <div class="flex items-center justify-between border-t border-line px-4 py-3">
                        <p class="text-[11px] font-semibold text-muted">Showing page {{ $payload['page'] }}</p>
                        <div class="flex gap-1.5">
                            <button type="button" wire:click="gotoPage({{ $payload['page'] - 1 }})" @disabled($payload['page'] <= 1)
                                    class="rounded-lg border border-line px-2.5 py-1.5 text-xs font-bold text-muted transition enabled:hover:bg-panel-2 disabled:opacity-40">Prev</button>
                            <button type="button" wire:click="gotoPage({{ $payload['page'] + 1 }})" @disabled($payload['page'] * 10 >= $payload['total'])
                                    class="rounded-lg border border-line px-2.5 py-1.5 text-xs font-bold text-muted transition enabled:hover:bg-panel-2 disabled:opacity-40">Next</button>
                        </div>
                    </div>
                @endif
            @endif
        </section>
    @endif
</div>
