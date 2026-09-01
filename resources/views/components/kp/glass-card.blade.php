@props([
    'title' => null,
    'subtitle' => null,
    'tone' => null,      // null | ok | warn | alert | crit | info (left accent)
    'priority' => null,  // P0..P3 badge
    'padding' => 'p-5 sm:p-6',
    'bodyClass' => '',
])

@php
    $accent = match ($tone) {
        'ok'    => 'border-l-2 border-l-ok',
        'warn'  => 'border-l-2 border-l-brand-gold',
        'alert' => 'border-l-2 border-l-brand-orange',
        'crit'  => 'border-l-2 border-l-crit',
        'info'  => 'border-l-2 border-l-brand',
        default => '',
    };
    $priorityTone = match ($priority) {
        'P0' => 'crit', 'P1' => 'alert', 'P2' => 'warn', 'P3' => 'info', default => null,
    };
@endphp

<div {{ $attributes->merge(['class' => 'panel relative overflow-hidden '.$accent]) }}>
    @if($title || $subtitle || $priority || ($slot ?? false) === null)
        @if($title || $subtitle || $priority)
            <div class="flex items-start justify-between gap-3 px-5 sm:px-6 pt-5 sm:pt-6">
                <div class="min-w-0">
                    @if($title)
                        <h3 class="text-sm font-extrabold text-ink tracking-tight">{{ $title }}</h3>
                    @endif
                    @if($subtitle)
                        <p class="text-xs font-medium text-muted mt-1">{{ $subtitle }}</p>
                    @endif
                </div>
                @if($priority)
                    <x-kp.badge :tone="$priorityTone ?? 'neutral'" class="shrink-0">{{ $priority }}</x-kp.badge>
                @endif
            </div>
        @endif
    @endif

    <div class="{{ $title || $subtitle || $priority ? $padding : $padding }} {{ $bodyClass }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="px-5 sm:px-6 py-3 border-t border-line bg-panel-2/50">
            {{ $footer }}
        </div>
    @endisset
</div>
