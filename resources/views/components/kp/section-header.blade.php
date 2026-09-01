@props([
    'eyebrow' => null,
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-end justify-between gap-3']) }}>
    <div class="min-w-0">
        @if($eyebrow)
            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-brand">{{ $eyebrow }}</p>
        @endif
        @if($title)
            <h2 class="mt-1 text-xl sm:text-2xl font-black text-ink tracking-tight">{{ $title }}</h2>
        @endif
        @if($description)
            <p class="mt-1 text-sm font-medium text-muted max-w-2xl">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2 shrink-0">
            {{ $actions }}
        </div>
    @endisset
</div>
