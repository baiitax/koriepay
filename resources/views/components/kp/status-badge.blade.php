@props([
    'status' => null,  // normalized status key (maps to tone) or a raw label
    'label' => null,   // overrides label text
    'tone' => null,    // overrides tone: ok|warn|alert|crit|info|neutral
    'dot' => true,
])

@php
    // Status badge — status is never conveyed by color alone: always a dot +
    // label, and the dot renders a distinct glyph for terminal/critical states.
    $map = [
        'operational' => ['tone' => 'ok',    'label' => 'Operational', 'icon' => 'check-circle'],
        'healthy'     => ['tone' => 'ok',    'label' => 'Healthy',     'icon' => 'check-circle'],
        'active'      => ['tone' => 'ok',    'label' => 'Active',      'icon' => 'check-circle'],
        'completed'   => ['tone' => 'ok',    'label' => 'Completed',   'icon' => 'check-circle'],
        'success'     => ['tone' => 'ok',    'label' => 'Success',     'icon' => 'check-circle'],
        'approved'    => ['tone' => 'ok',    'label' => 'Approved',    'icon' => 'check-circle'],
        'verified'    => ['tone' => 'ok',    'label' => 'Verified',    'icon' => 'check-circle'],
        'matched'     => ['tone' => 'ok',    'label' => 'Matched',     'icon' => 'check-circle'],
        'settled'     => ['tone' => 'ok',    'label' => 'Settled',     'icon' => 'check-circle'],

        'degraded'    => ['tone' => 'warn',  'label' => 'Degraded',    'icon' => 'exclamation-triangle'],
        'pending'     => ['tone' => 'warn',  'label' => 'Pending',     'icon' => 'clock'],
        'attention'   => ['tone' => 'warn',  'label' => 'Attention',   'icon' => 'exclamation-triangle'],
        'processing'  => ['tone' => 'warn',  'label' => 'Processing',  'icon' => 'clock'],
        'review'      => ['tone' => 'warn',  'label' => 'Review',      'icon' => 'eye'],
        'held'        => ['tone' => 'warn',  'label' => 'Held',        'icon' => 'lock'],
        'unmatched'   => ['tone' => 'warn',  'label' => 'Unmatched',   'icon' => 'clock'],
        'warning'     => ['tone' => 'warn',  'label' => 'Warning',     'icon' => 'exclamation-triangle'],

        'outage'      => ['tone' => 'alert', 'label' => 'Outage',      'icon' => 'x-circle'],
        'high_risk'   => ['tone' => 'alert', 'label' => 'High Risk',   'icon' => 'shield-exclamation'],
        'suspended'   => ['tone' => 'alert', 'label' => 'Suspended',   'icon' => 'lock'],
        'blocked'     => ['tone' => 'alert', 'label' => 'Blocked',     'icon' => 'lock'],
        'rejected'    => ['tone' => 'alert', 'label' => 'Rejected',    'icon' => 'x-circle'],
        'failed'      => ['tone' => 'alert', 'label' => 'Failed',      'icon' => 'x-circle'],

        'critical'    => ['tone' => 'crit',  'label' => 'Critical',    'icon' => 'shield-exclamation'],
        'down'        => ['tone' => 'crit',  'label' => 'Down',        'icon' => 'x-circle'],
        'fraud'       => ['tone' => 'crit',  'label' => 'Fraud',       'icon' => 'fingerprint'],
        'reversed'    => ['tone' => 'crit',  'label' => 'Reversed',    'icon' => 'arrow-right-left'],

        'info'        => ['tone' => 'info',  'label' => 'Info',        'icon' => 'info'],
        'new'         => ['tone' => 'info',  'label' => 'New',         'icon' => 'sparkles'],
        'scheduled'   => ['tone' => 'info',  'label' => 'Scheduled',   'icon' => 'clock'],

        'ok'          => ['tone' => 'ok',    'label' => 'OK',          'icon' => 'check-circle'],
        'issues'      => ['tone' => 'crit',  'label' => 'Issues',      'icon' => 'x-circle'],
        'unknown'     => ['tone' => 'neutral','label' => 'Unknown',    'icon' => 'question-mark-circle'],
        'stale'       => ['tone' => 'warn',  'label' => 'Stale',       'icon' => 'clock'],
    ];
    $resolved = $map[$status] ?? ['tone' => 'neutral', 'label' => $status ?? 'Unknown', 'icon' => null];
    $tone = $tone ?? $resolved['tone'];
    $label = $label ?? $resolved['label'];
    $icon = $resolved['icon'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[11px] font-bold leading-none']) }}>
    @if($icon)
        <span class="inline-flex {{ match($tone) { 'ok'=>'text-ok', 'warn'=>'text-brand-gold', 'alert'=>'text-brand-orange', 'crit'=>'text-crit', 'info'=>'text-brand', default=>'text-muted' } }}">
            <x-kp.icon :name="$icon" class="w-3 h-3" stroke="2.4" />
        </span>
    @elseif($dot)
        <span class="inline-flex w-1.5 h-1.5 rounded-full {{ match($tone) { 'ok'=>'bg-ok', 'warn'=>'bg-brand-gold', 'alert'=>'bg-brand-orange', 'crit'=>'bg-crit', 'info'=>'bg-brand', default=>'bg-faint' } }}"></span>
    @endif
    <span class="{{ match($tone) { 'ok'=>'text-ok', 'warn'=>'text-ink', 'alert'=>'text-brand-orange', 'crit'=>'text-crit', 'info'=>'text-brand', default=>'text-muted' } }}">{{ $label }}</span>
</span>
