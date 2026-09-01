@props([
    'icon' => 'inbox',
    'title' => null,
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

<div {{ $attributes->merge(['class' => 'panel p-8 sm:p-10 text-center']) }}>
    <div class="mx-auto w-14 h-14 rounded-2xl bg-panel-2 border border-line flex items-center justify-center text-faint">
        <x-kp.icon :name="$icon" class="w-7 h-7" stroke="1.6" />
    </div>
    @if($title)
        <h4 class="mt-4 text-sm font-extrabold text-ink">{{ $title }}</h4>
    @endif
    @if($description)
        <p class="mt-1.5 text-xs leading-relaxed text-muted max-w-md mx-auto">{{ $description }}</p>
    @endif
    @if($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}" class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-brand hover:text-brand-2">
            {{ $actionLabel }} <x-kp.icon name="arrow-up-right" class="w-3.5 h-3.5" stroke="2.2" />
        </a>
    @endif
</div>
