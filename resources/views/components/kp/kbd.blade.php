@props(['keys' => []]) {{-- e.g. ['ctrl', 'K'] or ['/'] --}}

<span class="inline-flex items-center gap-1" aria-hidden="true">
    @foreach($keys as $i => $key)
        @if($i > 0)
            <span class="text-faint text-[10px] font-bold px-0.5">+</span>
        @endif
        <kbd class="kbd">{{ $key }}</kbd>
    @endforeach
</span>
