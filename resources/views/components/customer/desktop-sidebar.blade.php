@php
    $items = [
        ['label' => 'Home',        'route' => 'customer.dashboard', 'icon' => 'squares-2x2'],
        ['label' => 'Pay',         'route' => 'customer.pay',       'icon' => 'arrow-up-right'],
        ['label' => 'Transactions','route' => 'customer.history',   'icon' => 'clock'],
        ['label' => 'Wallets',     'route' => 'customer.vaults',    'icon' => 'wallet'],
        ['label' => 'Profile',     'route' => 'customer.profile',   'icon' => 'user-check'],
        ['label' => 'KYC Center',  'route' => 'customer.kyc-center','icon' => 'shield-check'],
    ];
@endphp

<div class="panel flex h-full flex-col p-4">
    <a href="{{ route('customer.dashboard') }}" class="mb-6 flex items-center gap-2.5 px-2" aria-label="KoriePay home">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand to-brand-2 text-base font-black text-white shadow-glass">K</span>
        <span class="text-lg font-black tracking-tight">KoriePay</span>
    </a>

    <nav aria-label="Primary" class="flex flex-1 flex-col gap-1">
        @foreach ($items as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}"
               aria-current="{{ $active ? 'page' : 'false' }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ $active ? 'bg-brand/10 text-brand' : 'text-muted hover:bg-panel-2 hover:text-ink' }}">
                <x-kp.icon :name="$item['icon']" class="h-5 w-5" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="mt-4 space-y-3 border-t border-line pt-4">
        <a href="{{ route('customer.security') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-muted hover:bg-panel-2 hover:text-ink">
            <x-kp.icon name="shield" class="h-5 w-5" /> Security
        </a>
        <a href="{{ route('customer.support') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-muted hover:bg-panel-2 hover:text-ink">
            <x-kp.icon name="chat" class="h-5 w-5" /> Support
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-crit hover:bg-crit/10">
                <x-kp.icon name="logout" class="h-5 w-5" /> Sign out
            </button>
        </form>
        <div class="pt-2">
            <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-widest text-muted">Language</p>
            <livewire:customer.language-switcher />
        </div>
    </div>
</div>
