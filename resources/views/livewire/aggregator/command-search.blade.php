{{-- Global command search (Ctrl/Cmd+K). Rendered inside the shared layout;
     opened via the header button or keyboard. All results are live and
     tenant-scoped — no fabricated hits. --}}
<div wire:keydown.escape="close" wire:keydown.arrow-down.prevent="goNext" wire:keydown.arrow-up.prevent="goPrev"
     x-data x-cloak x-show="$wire.open" x-transition.opacity
     class="fixed inset-0 z-[70] flex items-start justify-center px-4 pt-[12vh]" wire:key="command-search">
    <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm" wire:click="close" aria-hidden="true"></div>

    <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-line bg-panel shadow-2xl">
        <div class="flex items-center gap-3 border-b border-line px-4 py-3.5">
            <x-kp.icon name="magnifying-glass" class="h-5 w-5 shrink-0 text-brand" />
            <input type="search" wire:model.live="term" placeholder="Search agents, cases, documents, reports… or press Esc"
                   class="w-full bg-transparent text-sm font-semibold text-ink outline-none placeholder:text-faint"
                   autofocus>
            <x-kp.kbd :keys="['esc']" />
        </div>

        <div class="max-h-[50vh] overflow-y-auto p-2">
            @if ($term === '')
                <p class="px-3 py-6 text-center text-xs font-semibold text-muted">
                    Type to search your network — agents, support cases, documents and reports.<br>
                    <span class="text-faint">Use ↑ ↓ to move and Enter to open.</span>
                </p>
            @elseif ($total === 0)
                <p class="px-3 py-6 text-center text-xs font-semibold text-muted">No matches for “{{ $term }}” in your network.</p>
            @else
                @php $i = 0; @endphp
                @foreach ($groups as $group)
                    <p class="px-3 pb-1 pt-3 text-[10px] font-black uppercase tracking-[0.16em] text-faint">{{ $group['label'] }}</p>
                    @foreach ($group['items'] as $item)
                        @php $active = $i === $highlight; $i++; @endphp
                        <a href="{{ $item['route'] }}" wire:navigate
                           class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm {{ $active ? 'bg-brand/10 text-brand' : 'text-ink hover:bg-panel-2' }}">
                            <span class="min-w-0">
                                <span class="block truncate font-bold">{{ $item['label'] }}</span>
                                <span class="block truncate text-[11px] font-medium text-muted">{{ $item['sub'] }}</span>
                            </span>
                            <x-kp.icon name="chevron-right" class="h-4 w-4 shrink-0 text-faint" />
                        </a>
                    @endforeach
                @endforeach
            @endif
        </div>

        <div class="flex items-center justify-between border-t border-line px-4 py-2 text-[10px] font-bold uppercase tracking-wide text-faint">
            <span>Live results · {{ $total }} hit(s)</span>
            <span>↑ ↓ navigate · Enter open · Esc close</span>
        </div>
    </div>
</div>
