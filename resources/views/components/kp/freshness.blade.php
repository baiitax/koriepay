@props([
    'level' => 'authoritative', // authoritative | snapshot | estimate | unknown
    'label' => null,
    'sub' => null,
])

{{-- Refresh/quality indicator: authoritative (live records), snapshot
     (derived read model), estimate (forecast/extrapolation) or unknown.
     Every figure in the console carries one of these labels. --}}
@php
    $map = [
        'authoritative' => ['tone' => 'ok',   'icon' => 'check-circle',     'label' => 'Live records'],
        'snapshot'      => ['tone' => 'info', 'icon' => 'database',         'label' => 'Snapshot'],
        'estimate'      => ['tone' => 'warn', 'icon' => 'exclamation-triangle', 'label' => 'Estimate'],
        'unknown'       => ['tone' => 'neutral', 'icon' => 'question-mark-circle', 'label' => 'Unknown'],
    ];
    $r = $map[$level] ?? $map['unknown'];
    $label = $label ?? $r['label'];
    $toneText = match ($r['tone']) {
        'ok' => 'text-ok border-ok/20 bg-ok/5',
        'info' => 'text-brand border-brand/20 bg-brand/5',
        'warn' => 'text-brand-gold border-brand-gold/20 bg-brand-gold/5',
        default => 'text-muted border-line bg-panel-2/50',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wide '.$toneText]) }}
      title="Data quality: {{ $label }}{{ $sub ? ' — '.$sub : '' }}">
    <x-kp.icon :name="$r['icon']" class="w-3 h-3" stroke="2.4" />
    {{ $label }}
</span>
