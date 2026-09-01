<div class="mx-auto max-w-7xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned." />
    @else
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Document center</h1>
                <p class="mt-0.5 text-sm text-muted">Authorized documents only — your uploads plus KoriePay-published notices</p>
            </div>
            @if ($canManage)
                <button type="button" wire:click="openUpload"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white shadow-glow-brand hover:bg-brand-2 active:scale-[0.98]">
                    <x-kp.icon name="upload" class="h-4 w-4" /> Upload document
                </button>
            @endif
        </div>

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="panel p-4">
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Your documents</p>
                <p class="mt-1 text-2xl font-black text-ink">{{ $payload['summary']['own'] }}</p>
            </div>
            <div class="panel p-4">
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">KoriePay notices</p>
                <p class="mt-1 text-2xl font-black text-ink">{{ $payload['summary']['system'] }}</p>
            </div>
        </section>

        <section class="panel p-4">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <x-kp.icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                    <input type="search" wire:model.live.debounce.250ms="search" placeholder="Search documents…"
                           class="w-full rounded-xl border border-line bg-panel-2/50 py-2.5 pl-9 pr-3 text-sm font-semibold outline-none focus:border-brand">
                </div>
                <select wire:model.live="category" class="rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-xs font-bold outline-none focus:border-brand">
                    <option value="">All categories</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c }}">{{ ucwords(str_replace('_', ' ', $c)) }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="resetFilters" class="rounded-xl border border-line px-3 py-2.5 text-xs font-bold text-muted hover:bg-panel-2">Reset</button>
            </div>
        </section>

        @if ($showUpload)
            <section class="panel p-5">
                <h2 class="text-sm font-extrabold text-ink">Upload a document</h2>
                <form wire:submit.prevent="upload" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2" enctype="multipart/form-data">
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">File</label>
                        <input type="file" wire:model="docFile" class="w-full rounded-xl border border-dashed border-line bg-panel-2/40 px-3 py-3 text-sm font-semibold">
                        @error('docFile')<p class="text-[11px] font-bold text-crit">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Title</label>
                        <input type="text" wire:model="docTitle" maxlength="200" class="w-full rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-sm font-semibold outline-none focus:border-brand">
                        @error('docTitle')<p class="text-[11px] font-bold text-crit">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Category</label>
                            <select wire:model="docCategory" class="w-full rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-sm font-bold outline-none focus:border-brand">
                                @foreach ($categories as $c)
                                    <option value="{{ $c }}">{{ ucwords(str_replace('_', ' ', $c)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Visibility</label>
                            <select wire:model="docVisibility" class="w-full rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 text-sm font-bold outline-none focus:border-brand">
                                <option value="network">Network</option>
                                <option value="internal">Internal</option>
                            </select>
                        </div>
                    </div>
                    <div class="sm:col-span-2 flex items-center gap-2">
                        <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-brand px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white hover:bg-brand-2">Upload</button>
                        <button type="button" wire:click="cancelUpload" class="rounded-xl border border-line px-4 py-2.5 text-xs font-bold text-muted hover:bg-panel-2">Cancel</button>
                    </div>
                </form>
            </section>
        @endif

        @if (count($payload['documents']) === 0)
            <x-kp.empty-state icon="archive" title="No documents yet"
                description="Uploaded documents and KoriePay-published notices will appear here." />
        @else
            <section class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($payload['documents'] as $doc)
                    <article class="panel p-4">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-panel-2 border border-line">
                                <x-kp.icon name="{{ $doc['is_system'] ? 'shield-check' : 'document' }}" class="h-5 w-5 text-brand" />
                            </span>
                            <span class="rounded-full bg-panel-2 px-2.5 py-1 text-[9px] font-black uppercase tracking-wide text-muted">{{ $doc['category'] }}</span>
                        </div>
                        <h3 class="mt-3 text-sm font-extrabold text-ink">{{ $doc['title'] }}</h3>
                        <p class="mt-1 text-[11px] text-muted">{{ $doc['file_name'] }} · {{ number_format($doc['size_bytes'] / 1024, 1) }} KB</p>
                        <div class="mt-3 flex items-center justify-between gap-2">
                            <x-kp.freshness level="{{ $doc['is_system'] ? 'authoritative' : 'authoritative' }}" :sub="$doc['source']" />
                            @if ($doc['downloadable'])
                                <a href="{{ route('aggregator.documents.download', $doc['id']) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-line px-3 py-1.5 text-[11px] font-bold text-brand hover:bg-panel-2">
                                    <x-kp.icon name="download" class="h-3.5 w-3.5" /> Download
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>

            @if ($payload['pages'] > 1)
                <nav class="flex flex-wrap items-center justify-between gap-2 text-xs font-bold text-muted">
                    <span>{{ $payload['total'] }} document(s) · page {{ $payload['page'] }} of {{ $payload['pages'] }}</span>
                    <div class="flex gap-1.5">
                        @foreach (range(1, $payload['pages']) as $p)
                            <button wire:click="gotoPage({{ $p }})" class="rounded-lg border border-line px-3 py-1.5 {{ $p === $payload['page'] ? 'bg-brand text-white' : 'hover:bg-panel-2' }}">{{ $p }}</button>
                        @endforeach
                    </div>
                </nav>
            @endif
        @endif

        <p class="text-[10px] font-medium text-faint">{{ $payload['basis'] }}</p>
    @endif
</div>
