@php
    $items = [
        ['label' => 'Home',   'route' => 'customer.dashboard', 'icon' => 'squares-2x2', 'key' => 'home'],
        ['label' => 'Pay',    'route' => 'customer.pay',       'icon' => 'arrow-up-right', 'key' => 'pay', 'primary' => true],
        ['label' => 'History','route' => 'customer.history',   'icon' => 'clock',     'key' => 'history'],
        ['label' => 'Wallets','route' => 'customer.vaults',    'icon' => 'wallet',    'key' => 'wallets'],
        ['label' => 'Me',     'route' => 'customer.profile',   'icon' => 'user-check', 'key' => 'me'],
    ];
@endphp

<nav aria-label="Primary" class="lg:hidden fixed inset-x-0 bottom-0 z-50 pb-safe">
    <div class="mx-auto max-w-md px-4 pb-3">
        <div class="glass-strong rounded-2xl border-line/60 px-2 py-1.5 flex items-center justify-around shadow-lg">
            @foreach ($items as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   aria-label="{{ $item['label'] }}"
                   aria-current="{{ $active ? 'page' : 'false' }}"
                   class="group relative flex min-h-11 min-w-14 flex-col items-center justify-center gap-0.5 rounded-xl px-2 py-1 transition-colors {{ $active ? 'text-brand' : 'text-muted hover:text-ink' }}">
                    <span class="absolute -top-1 h-1 w-6 rounded-full bg-brand/80 {{ $active ? '' : 'opacity-0' }} transition-opacity" aria-hidden="true"></span>
                    @if (! empty($item['primary']))
                        <span class="absolute -top-4 flex h-10 w-14 items-center justify-center rounded-2xl bg-brand text-white shadow-glass">
                            <x-kp.icon :name="$item['icon']" class="h-5 w-5" />
                        </span>
                    @else
                        <x-kp.icon :name="$item['icon']" class="h-5 w-5 transition-transform group-active:scale-90" />
                    @endif
                    <span class="text-[10px] font-semibold {{ empty($item['primary']) ? '' : 'sr-only' }}">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</nav>
