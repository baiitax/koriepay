@props([
    'state' => 'operational', // operational | degraded | down | maintenance | unknown
    'label' => null,
    'sub' => null,
    'pulse' => true,
])

@php
    $map = [
        'operational' => ['tone' => 'ok',    'label' => 'Operational', 'dot' => 'bg-ok', 'text' => 'text-ok',  'ring' => 'bg-ok/20'],
        'degraded'    => ['tone' => 'warn',  'label' => 'Degraded',    'dot' => 'bg-brand-gold', 'text' => 'text-brand-gold', 'ring' => 'bg-brand-gold/20'],
        'down'        => ['tone' => 'crit',  'label' => 'Down',        'dot' => 'bg-crit', 'text' => 'text-crit', 'ring' => 'bg-crit/20'],
        'maintenance' => ['tone' => 'info',  'label' => 'Maintenance', 'dot' => 'bg-brand', 'text' => 'text-brand', 'ring' => 'bg-brand/20'],
        'unknown'     => ['tone' => 'neutral','label' => 'Unknown',    'dot' => 'bg-faint', 'text' => 'text-muted', 'ring' => 'bg-faint/20'],
    ];
    $r = $map[$state] ?? $map['unknown'];
    $label = $label ?? $r['label'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <span class="relative inline-flex w-2.5 h-2.5 shrink-0" role="img" aria-label="Status: {{ $label }}">
        @if($pulse && $state !== 'unknown')
            <span class="absolute inline-flex h-full w-full rounded-full {{ $r['ring'] }} {{ $state === 'operational' ? 'animate-ping opacity-60' : '' }}"></span>
        @endif
        <span class="relative inline-flex rounded-full w-2.5 h-2.5 {{ $r['dot'] }}"></span>
    </span>
    <span class="flex flex-col leading-none">
        <span class="text-xs font-bold {{ $r['text'] }}">{{ $label }}</span>
        @if($sub)
            <span class="text-[10px] font-medium text-muted mt-0.5">{{ $sub }}</span>
        @endif
    </span>
</span>
