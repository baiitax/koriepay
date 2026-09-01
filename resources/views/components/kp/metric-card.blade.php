@props([
    'label' => null,
    'value' => null,
    'currency' => null,
    'delta' => null,        // e.g. '+14.7%' — raw string
    'deltaDirection' => null, // 'up' | 'down' | 'flat' (auto-derived from delta if omitted)
    'deltaGood' => null,    // whether up is good (default true)
    'deltaNote' => null,    // e.g. 'vs previous 30 days'
    'target' => null,       // target string e.g. 'Target: 99.00%'
    'benchmark' => null,    // benchmark string
    'risk' => null,         // risk status key for badge: low|medium|high|critical
    'sparkline' => null,    // array of numbers → inline SVG sparkline
    'interpretation' => null,
    'action' => null,       // ['label' => '...', 'url' => '...']
    'freshness' => null,    // 'Updated 12s ago'
    'icon' => null,
    'tone' => 'neutral',    // neutral | ok | warn | alert | crit | info
    'href' => null,         // whole card clickable (drill-down)
])

@php
    $up = $deltaDirection ?? (str_starts_with((string) $delta, '+') ? 'up' : (str_starts_with((string) $delta, '-') ? 'down' : 'flat'));
    $goodIsUp = $deltaGood ?? true;
    $deltaTone = match ($up) {
        'up'   => $goodIsUp ? 'ok' : 'crit',
        'down' => $goodIsUp ? 'crit' : 'ok',
        default => 'neutral',
    };
    $deltaText = match ($deltaTone) {
        'ok' => 'text-ok', 'crit' => 'text-crit', default => 'text-muted',
    };
    $deltaIcon = $up === 'up' ? 'arrow-up' : ($up === 'down' ? 'arrow-down' : null);
    $accent = match ($tone) {
        'ok' => 'from-ok/15', 'warn' => 'from-brand-gold/15', 'alert' => 'from-brand-orange/15',
        'crit' => 'from-crit/15', 'info' => 'from-brand/15', default => 'from-brand/10',
    };
@endphp

<div {{ $attributes->merge(['class' => 'panel relative overflow-hidden group']) }}>
    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r {{ $accent }} to-transparent"></div>

    @if($href)
        <a href="{{ $href }}" class="absolute inset-0 z-10" aria-label="{{ $label }} — drill down"><span class="sr-only">{{ $label }} drill down</span></a>
    @endif

    <div class="p-5 sm:p-6">
        <div class="flex items-center justify-between gap-3">
            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted flex items-center gap-1.5">
                @if($icon)<x-kp.icon :name="$icon" class="w-3.5 h-3.5" stroke="2.2" />@endif
                {{ $label }}
            </p>
            @if($risk)
                <x-kp.risk-badge :level="$risk" class="relative z-20" />
            @endif
        </div>

        <div class="mt-3 flex items-end justify-between gap-3">
            <div class="min-w-0">
                <p class="text-2xl sm:text-[1.7rem] font-black text-ink tracking-tight font-mono truncate">
                    @if($currency)<span class="text-sm font-bold text-muted mr-1">{{ $currency }}</span>@endif
                    {{ $value }}
                </p>
                @if($delta)
                    <p class="mt-1.5 flex items-center gap-1 text-xs font-bold {{ $deltaText }}">
                        @if($deltaIcon)<x-kp.icon :name="$deltaIcon" class="w-3 h-3" stroke="2.4" />@endif
                        <span>{{ $delta }}</span>
                        @if($deltaNote)<span class="font-medium text-muted">{{ $deltaNote }}</span>@endif
                    </p>
                @endif
            </div>
            @if($sparkline)
                <div class="shrink-0 w-20 h-10 relative z-20" aria-hidden="true">
                    @php
                        $min = min($sparkline); $max = max($sparkline);
                        $range = ($max - $min) ?: 1;
                        $pts = array_map(fn($v, $i) => [($i / max(count($sparkline)-1, 1)) * 80, 34 - (($v - $min) / $range) * 30], $sparkline, array_keys($sparkline));
                        $line = implode(' ', array_map(fn($p) => $p[0].','.$p[1], $pts));
                        $area = $line.' 80,34 0,34';
                        $last = end($pts);
                        $stroke = match ($deltaTone) { 'ok' => '#22C55E', 'crit' => '#EF4444', default => '#158987' };
                    @endphp
                    <svg viewBox="0 0 80 36" class="w-full h-full overflow-visible" preserveAspectRatio="none">
                        <polygon points="{{ $area }}" fill="{{ $stroke }}" opacity="0.12"></polygon>
                        <polyline points="{{ $line }}" fill="none" stroke="{{ $stroke }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <circle cx="{{ $last[0] }}" cy="{{ $last[1] }}" r="2.4" fill="{{ $stroke }}"></circle>
                    </svg>
                </div>
            @endif
        </div>

        @if($benchmark || $target)
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-medium text-muted">
                @if($benchmark)<span>Benchmark: <span class="text-ink font-bold">{{ $benchmark }}</span></span>@endif
                @if($target)<span>Target: <span class="text-ink font-bold">{{ $target }}</span></span>@endif
            </div>
        @endif

        @if($interpretation)
            <p class="mt-3 text-xs leading-relaxed text-muted border-t border-line pt-3">{{ $interpretation }}</p>
        @endif
    </div>

    @if($action || $freshness)
        <div class="px-5 sm:px-6 py-3 border-t border-line bg-panel-2/50 flex items-center justify-between gap-2 relative z-20">
            @if($action)
                <a href="{{ $action['url'] ?? '#' }}" class="text-[11px] font-bold text-brand hover:text-brand-2 inline-flex items-center gap-1">
                    {{ $action['label'] ?? 'View details' }}
                    <x-kp.icon name="chevron-right" class="w-3 h-3" stroke="2.4" />
                </a>
            @else
                <span></span>
            @endif
            @if($freshness)
                <span class="text-[10px] font-medium text-faint inline-flex items-center gap-1" title="Data freshness">
                    <x-kp.icon name="clock" class="w-3 h-3" stroke="2.2" />{{ $freshness }}
                </span>
            @endif
        </div>
    @endif
</div>
