@props([
    'lines' => 1,
    'height' => 'h-4',
    'width' => null,
])

<div role="status" aria-label="Loading" {{ $attributes }}>
    @for($i = 0; $i < $lines; $i++)
        <div class="skeleton {{ $height }} {{ $width ?? ($i === $lines - 1 ? 'w-3/4' : 'w-full') }} {{ $i > 0 ? 'mt-3' : '' }}"></div>
    @endfor
    <span class="sr-only">Loading…</span>
</div>
