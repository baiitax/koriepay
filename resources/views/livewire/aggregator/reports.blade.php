<div class="mx-auto max-w-7xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned." />
    @else
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Report center</h1>
                <p class="mt-0.5 text-sm text-muted">Async generation · CSV / Excel / PDF · every request audited</p>
            </div>
            @if ($canGenerate)
                <button type="button" wire:click="openRequest"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white shadow-glow-brand hover:bg-brand-2 active:scale-[0.98]">
                    <x-kp.icon name="document" class="h-4 w-4" /> Request report
                </button>
            @endif
        </div>

        {{-- Request form --}}
        @if ($showRequest)
            <section class="panel p-5">
                <h2 class="text-sm font-extrabold text-ink">Request a report</h2>
                <form wire:submit.prevent="request" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Report type</label>
                        <select wire:model="type" class="w-full rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-sm font-bold outline-none focus:border-brand">
                            @foreach ($catalog as $c)
                                <option value="{{ $c['type'] }}">{{ $c['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Format</label>
                        <div class="grid grid-cols-3 gap-1.5">
                            @foreach (['csv' => 'CSV', 'xlsx' => 'Excel', 'pdf' => 'PDF'] as $key => $label)
                                <button type="button" wire:click="$set('format', '{{ $key }}')"
                                        class="rounded-xl border px-3 py-2.5 text-xs font-black uppercase {{ $format === $key ? 'border-brand bg-brand text-white' : 'border-line text-muted hover:bg-panel-2' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">From</label>
                        <input type="date" wire:model="dateFrom" class="w-full rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-sm font-semibold outline-none focus:border-brand">
                        @error('dateFrom')<p class="text-[11px] font-bold text-crit">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">To</label>
                        <input type="date" wire:model="dateTo" class="w-full rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-sm font-semibold outline-none focus:border-brand">
                        @error('dateTo')<p class="text-[11px] font-bold text-crit">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2 flex items-center gap-2">
                        <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-brand px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white hover:bg-brand-2">Generate</button>
                        <button type="button" wire:click="cancelRequest" class="rounded-xl border border-line px-4 py-2.5 text-xs font-bold text-muted hover:bg-panel-2">Cancel</button>
                        <p class="text-[11px] text-muted">Generation runs in the background — you can leave this page.</p>
                    </div>
                </form>
            </section>
        @endif

        {{-- Catalog --}}
        <section class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($catalog as $c)
                <div class="panel p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.14em] text-brand">{{ $c['type'] }}</p>
                    <h3 class="mt-1 text-sm font-extrabold text-ink">{{ $c['label'] }}</h3>
                    <p class="mt-1 text-[11px] leading-relaxed text-muted">{{ $c['description'] }}</p>
                    <p class="mt-2 text-[10px] font-bold uppercase tracking-wide text-faint">Formats: {{ implode(' · ', array_map('strtoupper', $c['formats'])) }}</p>
                </div>
            @endforeach
        </section>

        {{-- Job list --}}
        <section>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-extrabold tracking-tight text-ink">Generation history</h2>
                <button wire:click="refresh" class="rounded-xl border border-line px-3 py-1.5 text-xs font-bold text-muted hover:bg-panel-2">Refresh status</button>
            </div>

            @if (count($jobs['jobs']) === 0)
                <x-kp.empty-state class="mt-3" icon="clipboard" title="No reports yet"
                    description="Requested reports and their generation status will appear here." />
            @else
                <div class="mt-3 space-y-2">
                    @foreach ($jobs['jobs'] as $job)
                        <div class="panel flex flex-wrap items-center justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <p class="font-mono text-xs font-bold text-brand">{{ $job['reference'] }}</p>
                                <p class="truncate text-sm font-extrabold text-ink">{{ $job['type'] }} · {{ strtoupper($job['format']) }}</p>
                                <p class="text-[11px] text-muted">{{ $job['date_from'] }} → {{ $job['date_to'] }} · requested by {{ $job['requested_by'] }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-kp.status-badge :status="$job['status']" />
                                @if ($job['status'] === 'ready' && $job['downloadable'])
                                    <a href="{{ route('aggregator.reports.download', $job['id']) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-1.5 text-[11px] font-black uppercase tracking-wide text-white hover:bg-brand-2">
                                        <x-kp.icon name="download" class="h-3.5 w-3.5" /> Download
                                    </a>
                                @elseif ($job['status'] === 'failed' && $job['error'])
                                    <span class="max-w-[220px] truncate rounded-lg bg-crit/10 px-2.5 py-1 text-[10px] font-bold text-crit" title="{{ $job['error'] }}">{{ $job['error'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($jobs['pages'] > 1)
                    <nav class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs font-bold text-muted">
                        <span>{{ $jobs['total'] }} job(s) · page {{ $jobs['page'] }} of {{ $jobs['pages'] }}</span>
                        <div class="flex gap-1.5">
                            @foreach (range(1, $jobs['pages']) as $p)
                                <button wire:click="gotoPage({{ $p }})" class="rounded-lg border border-line px-3 py-1.5 {{ $p === $jobs['page'] ? 'bg-brand text-white' : 'hover:bg-panel-2' }}">{{ $p }}</button>
                            @endforeach
                        </div>
                    </nav>
                @endif
            @endif
        </section>

        <p class="text-[10px] font-medium text-faint">Every request, completion, failure and download is written to the audit log.</p>
    @endif
</div>
