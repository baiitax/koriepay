@props([
    'icon' => 'exclamation-triangle',
    'title' => 'Something went wrong',
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

{{-- Honest error boundary — never hides a failure behind a fabricated success. --}}
<div {{ $attributes->merge(['class' => 'panel p-8 sm:p-10 text-center border-crit/30']) }}>
    <div class="mx-auto w-14 h-14 rounded-2xl bg-crit/10 border border-crit/20 flex items-center justify-center text-crit">
        <x-kp.icon :name="$icon" class="w-7 h-7" stroke="1.6" />
    </div>
    <h4 class="mt-4 text-sm font-extrabold text-ink">{{ $title }}</h4>
    @if($description)
        <p class="mt-1.5 text-xs leading-relaxed text-muted max-w-md mx-auto">{{ $description }}</p>
    @endif
    @if($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}" class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-brand hover:text-brand-2">
            {{ $actionLabel }} <x-kp.icon name="arrow-up-right" class="w-3.5 h-3.5" stroke="2.2" />
        </a>
    @endif
</div>
