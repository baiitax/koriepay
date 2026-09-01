@props([
    'severity' => 'info', // info | low | medium | high | critical (or P0..P3)
    'icon' => null,
    'title' => null,
    'timestamp' => null,
    'source' => null,
    'confidence' => null,
    'dismissable' => false,
])

@php
    $sev = match (strtoupper((string) $severity)) {
        'P0', 'CRITICAL' => ['tone' => 'crit',  'icon' => 'shield-exclamation', 'label' => 'CRITICAL'],
        'P1', 'HIGH'     => ['tone' => 'alert', 'icon' => 'exclamation-triangle', 'label' => 'HIGH'],
        'P2', 'MEDIUM'   => ['tone' => 'warn',  'icon' => 'exclamation-triangle', 'label' => 'MEDIUM'],
        'P3', 'LOW'      => ['tone' => 'info',  'icon' => 'info', 'label' => 'LOW'],
        default          => ['tone' => 'info',  'icon' => 'info', 'label' => 'INFO'],
    };
    $tone = $sev['tone'];
    $toneText = match ($tone) {
        'crit' => 'text-crit', 'alert' => 'text-brand-orange', 'warn' => 'text-brand-gold', default => 'text-brand',
    };
    $toneBg = match ($tone) {
        'crit' => 'bg-crit/10 border-crit/25', 'alert' => 'bg-brand-orange/10 border-brand-orange/30',
        'warn' => 'bg-brand-gold/10 border-brand-gold/30', default => 'bg-brand/10 border-brand/25',
    };
@endphp

<div x-data="{ visible: true }" x-show="visible" x-transition:leave.opacity.duration.150
     {{ $attributes->merge(['class' => 'panel border-l-4 p-4 sm:p-5 flex gap-3.5']) }}
     style="{{ match ($tone) { 'crit' => 'border-left-color: #EF4444', 'alert' => 'border-left-color: #F88D25', 'warn' => 'border-left-color: #FCCB1A', default => 'border-left-color: #158987' } }}">
    <div class="shrink-0 w-9 h-9 rounded-xl {{ $toneBg }} flex items-center justify-center {{ $toneText }}">
        <x-kp.icon :name="$icon ?? $sev['icon']" class="w-5 h-5" stroke="2" />
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-[10px] font-black tracking-[0.14em] {{ $toneText }}">{{ $sev['label'] }}</span>
            @if($source)<span class="text-[10px] font-medium text-faint">· {{ $source }}</span>@endif
            @if($confidence)
                <x-kp.badge tone="info" icon="sparkles" class="ml-auto">{{ $confidence }}</x-kp.badge>
            @endif
        </div>
        @if($title)
            <h4 class="mt-1 text-sm font-extrabold text-ink">{{ $title }}</h4>
        @endif
        <div class="mt-1 text-xs leading-relaxed text-muted">{{ $slot }}</div>
        @isset($actions)
            <div class="mt-3 flex items-center gap-2 flex-wrap">
                {{ $actions }}
            </div>
        @endisset
    </div>
    @if($dismissable)
        <button type="button" @click="visible = false" class="shrink-0 self-start text-faint hover:text-ink p-1 rounded-md hover:bg-panel-2" aria-label="Dismiss alert">
            <x-kp.icon name="x-mark" class="w-4 h-4" stroke="2.2" />
        </button>
    @endif
</div>
