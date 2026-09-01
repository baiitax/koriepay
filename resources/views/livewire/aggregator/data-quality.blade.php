<div class="mx-auto max-w-5xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned." />
    @else
        @php $p = $payload; @endphp

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Data quality center</h1>
                <p class="mt-0.5 text-sm text-muted">Live quality checks over your network's records — nothing cached, nothing fabricated</p>
            </div>
            <div class="flex items-center gap-2">
                <x-kp.freshness level="authoritative" sub="scanned at render" />
                @if ($canRecompute)
                    <button wire:click="recomputeReadModel"
                        class="rounded-xl bg-brand px-3 py-2 text-xs font-black uppercase tracking-wide text-white hover:bg-brand-2">
                        Recompute read model
                    </button>
                @endif
            </div>
        </div>

        {{-- Overall verdict --}}
        <section class="panel p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <x-kp.section-header eyebrow="Overall verdict"
                        title="Data quality {{ $p['overall'] === 'healthy' ? 'is healthy' : ($p['overall'] === 'attention' ? 'needs attention' : ($p['overall'] === 'issues' ? 'has issues' : 'is unverifiable')) }}" />
                    <p class="mt-1 text-sm font-medium text-muted">{{ $p['basis'] }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-kp.status-badge :status="$p['overall']" />
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl border border-line bg-panel-2/50 p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Passing</p>
                    <p class="mt-1 text-2xl font-black text-ok">{{ $p['summary']['ok'] }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-panel-2/50 p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Attention</p>
                    <p class="mt-1 text-2xl font-black text-brand-gold">{{ $p['summary']['attention'] }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-panel-2/50 p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Warning</p>
                    <p class="mt-1 text-2xl font-black text-brand-orange">{{ $p['summary']['warning'] }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-panel-2/50 p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-muted">Unknown</p>
                    <p class="mt-1 text-2xl font-black text-muted">{{ $p['summary']['unknown'] }}</p>
                </div>
            </div>
        </section>

        {{-- Checks --}}
        <section class="panel p-5 sm:p-6">
            <x-kp.section-header eyebrow="Checks" title="What we measure"
                description="Each check is recomputed live from tenant-scoped records at render time." />

            <ul class="mt-5 divide-y divide-line">
                @foreach ($p['checks'] as $check)
                    <li class="flex flex-wrap items-center gap-3 py-3.5">
                        <x-kp.status-badge :status="$check['status']" class="w-24 justify-center" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-black text-ink">{{ $check['label'] }}</p>
                            <p class="mt-0.5 text-xs font-medium text-muted">{{ $check['detail'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black text-ink">{{ $check['value'] }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-faint">{{ $check['source'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Why this page is honest --}}
        <section class="panel border-l-2 border-l-brand p-5 sm:p-6">
            <x-kp.section-header eyebrow="Honesty contract" title="What this center never does"
                description="It never invents a healthy signal, never hides a gap, and never reports a source it could not read as zero." />
            <ul class="mt-4 grid gap-2 text-sm font-medium text-ink/90 sm:grid-cols-3">
                <li class="flex items-center gap-2"><x-kp.icon name="check-circle" class="h-4 w-4 text-ok" /> Live reads only</li>
                <li class="flex items-center gap-2"><x-kp.icon name="check-circle" class="h-4 w-4 text-ok" /> Gaps surface as unknown</li>
                <li class="flex items-center gap-2"><x-kp.icon name="check-circle" class="h-4 w-4 text-ok" /> Read model is labelled as a snapshot</li>
            </ul>
        </section>
    @endif
</div>
