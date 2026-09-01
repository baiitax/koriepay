<div class="mx-auto max-w-7xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned." />
    @else
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Support center</h1>
                <p class="mt-0.5 text-sm text-muted">Cases for your network · priority-driven SLA countdown</p>
            </div>
            @if ($canManage)
                <button type="button" wire:click="openRaise"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white shadow-glow-brand hover:bg-brand-2 active:scale-[0.98]">
                    <x-kp.icon name="chat" class="h-4 w-4" /> Raise a case
                </button>
            @endif
        </div>

        {{-- SLA summary strip --}}
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="panel p-4">
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Open cases</p>
                <p class="mt-1 text-2xl font-black text-ink">{{ $payload['summary']['open'] }}</p>
            </div>
            <div class="panel p-4 border-l-2 {{ $payload['summary']['overdue'] > 0 ? 'border-l-crit' : 'border-l-ok' }}">
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">SLA overdue</p>
                <p class="mt-1 text-2xl font-black {{ $payload['summary']['overdue'] > 0 ? 'text-crit' : 'text-ink' }}">{{ $payload['summary']['overdue'] }}</p>
            </div>
            <div class="panel p-4">
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Resolved</p>
                <p class="mt-1 text-2xl font-black text-ink">{{ $payload['summary']['resolved'] }}</p>
            </div>
        </section>

        {{-- Filters --}}
        <section class="panel p-4">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <x-kp.icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                    <input type="search" wire:model.live.debounce.250ms="search" placeholder="Search ticket id or subject…"
                           class="w-full rounded-xl border border-line bg-panel-2/50 py-2.5 pl-9 pr-3 text-sm font-semibold outline-none focus:border-brand">
                </div>
                <select wire:model.live="status" class="rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-xs font-bold outline-none focus:border-brand">
                    <option value="">All statuses</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
                <select wire:model.live="priority" class="rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-xs font-bold outline-none focus:border-brand">
                    <option value="">All priorities</option>
                    @foreach ($priorities as $p)
                        <option value="{{ $p['key'] }}">{{ $p['label'] }} · SLA {{ $p['sla_hours'] }}h</option>
                    @endforeach
                </select>
                <button type="button" wire:click="resetFilters" class="rounded-xl border border-line px-3 py-2.5 text-xs font-bold text-muted hover:bg-panel-2">Reset</button>
            </div>
        </section>

        {{-- Raise form --}}
        @if ($showRaise)
            <section class="panel p-5">
                <h2 class="text-sm font-extrabold text-ink">Raise a support case</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Category</label>
                        <select wire:model="category" class="w-full rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-sm font-bold outline-none focus:border-brand">
                            @foreach ($categories as $c)
                                <option value="{{ $c }}">{{ ucwords(str_replace('_', ' ', $c)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Priority (SLA)</label>
                        <select wire:model="raisePriority" class="w-full rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-sm font-bold outline-none focus:border-brand">
                            @foreach ($priorities as $p)
                                <option value="{{ $p['key'] }}">{{ $p['label'] }} — respond within {{ $p['sla_hours'] }}h</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Subject</label>
                        <input type="text" wire:model="subject" maxlength="200" class="w-full rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-sm font-semibold outline-none focus:border-brand">
                        @error('subject')<p class="text-[11px] font-bold text-crit">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Message</label>
                        <textarea wire:model="message" rows="3" maxlength="4000" class="w-full rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-sm font-semibold outline-none focus:border-brand"></textarea>
                        @error('message')<p class="text-[11px] font-bold text-crit">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <button type="button" wire:click="raise" wire:loading.attr="disabled" class="rounded-xl bg-brand px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white hover:bg-brand-2">Submit case</button>
                    <button type="button" wire:click="cancelRaise" class="rounded-xl border border-line px-4 py-2.5 text-xs font-bold text-muted hover:bg-panel-2">Cancel</button>
                </div>
            </section>
        @endif

        {{-- Ticket list --}}
        @if (count($payload['tickets']) === 0)
            <x-kp.empty-state icon="inbox" title="No support cases"
                description="Tickets raised in this console or by your network's users will appear here." />
        @else
            <section class="space-y-3">
                @foreach ($payload['tickets'] as $ticket)
                    <article id="ticket-{{ $ticket['id'] }}" class="panel overflow-hidden">
                        <button type="button" wire:click="openTicket({{ $ticket['id'] }})" class="flex w-full flex-wrap items-center justify-between gap-3 p-4 text-left hover:bg-panel-2/40">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-panel-2 border border-line">
                                    <x-kp.icon name="chat" class="h-4 w-4 text-brand" />
                                </span>
                                <div class="min-w-0">
                                    <p class="font-mono text-xs font-bold text-brand">{{ $ticket['ticket_id'] }}</p>
                                    <p class="truncate text-sm font-extrabold text-ink">{{ $ticket['subject'] }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold">
                                <x-kp.status-badge :status="$ticket['status']" />
                                <x-kp.risk-badge :level="$ticket['priority'] === 'critical' ? 'critical' : ($ticket['priority'] === 'high' ? 'high' : ($ticket['priority'] === 'medium' ? 'medium' : 'low'))" />
                                @if ($ticket['sla']['status'] === 'overdue')
                                    <span class="rounded-full bg-crit/10 px-2.5 py-1 font-black uppercase tracking-wide text-crit">Overdue</span>
                                @elseif ($ticket['sla']['status'] === 'within')
                                    <span class="rounded-full bg-ok/10 px-2.5 py-1 text-ok">{{ round($ticket['sla']['remaining_hours'], 1) }}h left</span>
                                @endif
                                <x-kp.icon :name="$activeTicket === $ticket['id'] ? 'chevron-up' : 'chevron-down'" class="h-4 w-4 text-faint" />
                            </div>
                        </button>

                        @if ($activeTicket === $ticket['id'])
                            <div class="border-t border-line p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-[10px] font-black uppercase tracking-wide text-muted">{{ ucwords(str_replace('_', ' ', $ticket['category'])) }}</span>
                                    <span class="text-[10px] text-faint">raised by {{ $ticket['raised_by'] }} · {{ $ticket['created_at'] }}</span>
                                    @if ($canManage)
                                        <div class="ml-auto flex flex-wrap gap-1.5">
                                            @foreach (['open', 'in_progress', 'resolved', 'closed'] as $st)
                                                <button wire:click="setStatus({{ $ticket['id'] }}, '{{ $st }}')" class="rounded-lg border border-line px-2.5 py-1 text-[10px] font-bold {{ $ticket['status'] === $st ? 'bg-brand text-white' : 'text-muted hover:bg-panel-2' }}">{{ ucwords(str_replace('_', ' ', $st)) }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <p class="mt-3 text-sm leading-relaxed text-ink/90">{{ $ticket['message'] }}</p>

                                <div class="mt-4 space-y-2">
                                    @foreach ($ticket['replies'] as $reply)
                                        <div class="rounded-xl border border-line bg-panel-2/40 p-3 {{ $reply['is_internal'] ? 'border-dashed' : '' }}">
                                            <p class="text-[10px] font-black uppercase tracking-wide {{ $reply['is_internal'] ? 'text-brand-gold' : 'text-brand' }}">
                                                {{ $reply['is_internal'] ? 'Internal note' : $reply['author'] }} · {{ $reply['created_at'] }}
                                            </p>
                                            <p class="mt-1 text-sm text-ink/90">{{ $reply['message'] }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($canManage)
                                    <form wire:submit.prevent="addReply({{ $ticket['id'] }})" class="mt-4 space-y-2">
                                        <textarea wire:model="reply" rows="2" placeholder="Add a reply…" class="w-full rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-sm font-semibold outline-none focus:border-brand"></textarea>
                                        @error('reply')<p class="text-[11px] font-bold text-crit">{{ $message }}</p>@enderror
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-xs font-black uppercase tracking-wide text-white hover:bg-brand-2">Reply</button>
                                            <label class="flex cursor-pointer items-center gap-1.5 text-[11px] font-bold text-muted">
                                                <input type="checkbox" wire:model="replyInternal" class="rounded border-line text-brand"> Internal note
                                            </label>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </article>
                @endforeach

                {{-- Pagination --}}
                @if ($payload['pages'] > 1)
                    <nav class="flex flex-wrap items-center justify-between gap-2 text-xs font-bold text-muted">
                        <span>{{ $payload['total'] }} case(s) · page {{ $payload['page'] }} of {{ $payload['pages'] }}</span>
                        <div class="flex gap-1.5">
                            @foreach (range(1, $payload['pages']) as $p)
                                <button wire:click="gotoPage({{ $p }})" class="rounded-lg border border-line px-3 py-1.5 {{ $p === $payload['page'] ? 'bg-brand text-white' : 'hover:bg-panel-2' }}">{{ $p }}</button>
                            @endforeach
                        </div>
                    </nav>
                @endif
            </section>
        @endif

        <p class="text-[10px] font-medium text-faint">{{ $payload['basis'] }} SLA is measured from priority at creation.</p>
    @endif
</div>
