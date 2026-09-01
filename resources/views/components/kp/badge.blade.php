@props([
    'tone' => 'neutral', // neutral | ok | warn | alert | crit | info | brand
    'icon' => null,
    'class' => '',
])

@php
    $tones = [
        'neutral' => 'tone-neutral',
        'ok'      => 'tone-ok',
        'warn'    => 'tone-warn',
        'alert'   => 'tone-alert',
        'crit'    => 'tone-crit',
        'info'    => 'tone-info',
        'brand'   => 'text-brand bg-brand/10 border-brand/25',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[11px] font-bold leading-none '.($tones[$tone] ?? $tones['neutral']).' '.$class]) }}>
    @if($icon)
        <x-kp.icon :name="$icon" class="w-3 h-3" stroke="2.2" />
    @endif
    <span>{{ $slot }}</span>
</span>
