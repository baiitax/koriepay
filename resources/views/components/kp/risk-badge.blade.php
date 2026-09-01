@props([
    'level' => 'low', // low | medium | high | critical
    'label' => null,
])

@php
    $map = [
        'low'      => ['tone' => 'ok',    'icon' => 'shield-check'],
        'medium'   => ['tone' => 'warn',  'icon' => 'shield'],
        'high'     => ['tone' => 'alert', 'icon' => 'shield-exclamation'],
        'critical' => ['tone' => 'crit',  'icon' => 'shield-exclamation'],
    ];
    $r = $map[$level] ?? $map['low'];
    $label = $label ?? strtoupper($level);
    $toneCls = match ($r['tone']) {
        'ok'    => 'text-ok border-ok/25 bg-ok/10',
        'warn'  => 'text-brand-gold border-brand-gold/30 bg-brand-gold/10',
        'alert' => 'text-brand-orange border-brand-orange/30 bg-brand-orange/10',
        'crit'  => 'text-crit border-crit/25 bg-crit/10',
        default => 'text-muted border-line bg-panel-2',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[11px] font-black tracking-wide leading-none '.$toneCls]) }} title="Risk level: {{ $label }}">
    <x-kp.icon :name="$r['icon']" class="w-3.5 h-3.5" stroke="2.2" />
    <span>{{ $label }}</span>
</span>
