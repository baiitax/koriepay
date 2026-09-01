<div class="mx-auto max-w-7xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned. Contact KoriePay admin to link your network." />
    @else
        @php $p = $payload; $pag = $p['paginator']; @endphp

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Agents</h1>
                <p class="mt-0.5 text-sm text-muted">{{ $p['total'] }} agent{{ $p['total'] === 1 ? '' : 's' }} in your network · live stats from operations & ledger</p>
            </div>
            <a href="{{ route('aggregator.agents.recruit') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-brand/20 transition hover:bg-brand/90">
                <x-kp.icon name="user-group" class="h-4 w-4" stroke="2.2" />
                Recruit agent
            </a>
        </div>

        {{-- Onboarding pipeline (§21–22) — real counts, honest conversion. --}}
        <section class="panel p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Onboarding pipeline</p>
                <p class="text-[11px] font-bold text-muted">
                    Conversion
                    <span class="font-black text-ink">{{ $p['pipeline']['conversion_rate'] !== null ? $p['pipeline']['conversion_rate'].'%' : '—' }}</span>
                    @if ($p['pipeline']['total'] === 0)
                        <span class="text-muted/70">(empty network)</span>
                    @endif
                </p>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($p['pipeline']['stages'] as $stage)
                    <div class="rounded-2xl border border-line bg-white/40 p-3">
                        <p class="text-2xl font-black tracking-tight text-ink">{{ $stage['count'] }}</p>
                        <p class="mt-0.5 text-[11px] font-bold leading-tight text-muted">{{ $stage['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Filter bar (§14) — every option derived from real data. --}}
        <section class="panel p-3 sm:p-4">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-faint">
                        <x-kp.icon name="magnifying-glass" class="h-4 w-4" stroke="2" />
                    </span>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search code, name, phone or email…"
                           class="w-full rounded-xl border border-line bg-white/60 py-2.5 pl-9 pr-3 text-sm font-semibold text-ink outline-none placeholder:text-muted/60 focus:border-brand">
                </div>
                <select wire:model.live="status" class="rounded-xl border border-line bg-white/60 px-3 py-2.5 text-sm font-semibold text-ink outline-none focus:border-brand">
                    <option value="">All statuses</option>
                    @foreach ($p['options']['statuses'] as $value)
                        <option value="{{ $value }}">{{ ucfirst($value) }}</option>
                    @endforeach
                </select>
                <select wire:model.live="kyc" class="rounded-xl border border-line bg-white/60 px-3 py-2.5 text-sm font-semibold text-ink outline-none focus:border-brand">
                    <option value="">All KYC</option>
                    @foreach ($p['options']['kyc'] as $value)
                        <option value="{{ $value }}">{{ ucfirst($value) }}</option>
                    @endforeach
                </select>
                <select wire:model.live="region" class="rounded-xl border border-line bg-white/60 px-3 py-2.5 text-sm font-semibold text-ink outline-none focus:border-brand">
                    <option value="">All regions</option>
                    @foreach ($p['options']['regions'] as $value)
                        <option value="{{ $value }}">{{ $value }}</option>
                    @endforeach
                </select>
                <select wire:model.live="city" class="rounded-xl border border-line bg-white/60 px-3 py-2.5 text-sm font-semibold text-ink outline-none focus:border-brand">
                    <option value="">All cities</option>
                    @foreach ($p['options']['cities'] as $value)
                        <option value="{{ $value }}">{{ $value }}</option>
                    @endforeach
                </select>
                <select wire:model.live="sort" class="rounded-xl border border-line bg-white/60 px-3 py-2.5 text-sm font-semibold text-ink outline-none focus:border-brand">
                    <option value="newest">Newest first</option>
                    <option value="name">Code A–Z</option>
                </select>
                <button type="button" wire:click="resetFilters"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-line px-3 py-2.5 text-sm font-bold text-muted transition hover:bg-panel-2 hover:text-ink">
                    <x-kp.icon name="x-mark" class="h-3.5 w-3.5" stroke="2.4" />
                    Clear
                </button>
            </div>
        </section>

        {{-- Directory table (§14) — server-paginated, stats live. --}}
        <section class="panel overflow-hidden">
            @if ($p['total'] === 0)
                <x-kp.empty-state icon="users" title="No agents match"
                    description="No agents match the current filters{{ $p['total'] === 0 && !array_filter([$p['filters']['search'] ?? '', $p['filters']['status'] ?? '', $p['filters']['kyc_status'] ?? '', $p['filters']['region'] ?? '', $p['filters']['city'] ?? '']) ? ' — recruit your first agent to start building the network' : '' }}."
                    actionUrl="{{ route('aggregator.agents.recruit') }}" actionLabel="Recruit an agent" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[880px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-line text-[10px] font-black uppercase tracking-[0.14em] text-muted">
                                <th class="px-4 py-3">Agent</th>
                                <th class="px-4 py-3">Location</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">KYC</th>
                                <th class="px-4 py-3">Float ({{ $p['currency'] }})</th>
                                <th class="px-4 py-3 text-right">30-day ops</th>
                                <th class="px-4 py-3 text-right">Volume ({{ $p['currency'] }})</th>
                                <th class="px-4 py-3">Last activity</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($pag->items() as $row)
                                @php $agent = $row['agent']; @endphp
                                <tr class="transition-colors hover:bg-panel-2/60">
                                    <td class="px-4 py-3.5">
                                        <a href="{{ route('aggregator.agents.show', $agent) }}" wire:navigate class="group flex items-center gap-3">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-sm font-black text-brand">
                                                {{ collect(explode(' ', $row['name']))->take(2)->map(fn ($w) => strtoupper(substr($w, 0, 1)))->join('') }}
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate font-bold text-ink group-hover:text-brand">{{ $row['name'] }}</span>
                                                <span class="block font-mono text-[11px] text-muted">{{ $agent->agent_code }}@if ($row['phone']) · {{ $row['phone'] }} @endif</span>
                                            </span>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3.5 text-[13px] text-muted">{{ $row['location'] ?: '—' }}</td>
                                    <td class="px-4 py-3.5"><x-kp.status-badge :status="$row['status']" /></td>
                                    <td class="px-4 py-3.5">
                                        @if ($row['kyc_status'] === 'verified')
                                            <x-kp.status-badge status="verified" />
                                        @else
                                            <x-kp.status-badge status="pending" label="{{ ucfirst($row['kyc_status']) }}" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 font-mono text-[13px] font-bold text-ink">
                                        {{ $row['float'] !== null ? number_format((float) $row['float'], 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <span class="font-bold text-ink">{{ $row['posted_30d'] }}</span>
                                        @if ($row['failed_30d'] > 0)
                                            <span class="ml-1 text-[11px] font-bold text-brand-orange">+{{ $row['failed_30d'] }} failed</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono text-[13px] font-bold text-ink">{{ number_format((float) $row['volume_30d'], 0) }}</td>
                                    <td class="px-4 py-3.5 text-[12px] text-muted">
                                        {{ $row['last_activity'] ? $row['last_activity']->diffForHumans() : '—' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <a href="{{ route('aggregator.agents.show', $agent) }}" wire:navigate
                                           class="inline-flex items-center gap-1 text-xs font-bold text-brand transition hover:text-brand-2">
                                            Open <x-kp.icon name="arrow-up-right" class="h-3.5 w-3.5" stroke="2.4" />
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pager + honest footer. --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
                    <p class="text-[11px] font-semibold text-muted">
                        Showing {{ $pag->firstItem() ?? 0 }}–{{ $pag->lastItem() ?? 0 }} of {{ $pag->total() }} agents · stats window: last 30 days
                    </p>
                    @if ($pag->hasPages())
                        <nav class="flex items-center gap-1.5">
                            <button type="button" wire:click="gotoPage({{ $pag->currentPage() - 1 }})" @disabled($pag->onFirstPage())
                                    class="rounded-lg border border-line px-2.5 py-1.5 text-xs font-bold text-muted transition enabled:hover:bg-panel-2 enabled:hover:text-ink disabled:opacity-40">
                                Prev
                            </button>
                            <span class="px-2 text-xs font-bold text-ink">Page {{ $pag->currentPage() }} / {{ $pag->lastPage() }}</span>
                            <button type="button" wire:click="gotoPage({{ $pag->currentPage() + 1 }})" @disabled(!$pag->hasMorePages())
                                    class="rounded-lg border border-line px-2.5 py-1.5 text-xs font-bold text-muted transition enabled:hover:bg-panel-2 enabled:hover:text-ink disabled:opacity-40">
                                Next
                            </button>
                        </nav>
                    @endif
                </div>
            @endif
        </section>
    @endif
</div>
