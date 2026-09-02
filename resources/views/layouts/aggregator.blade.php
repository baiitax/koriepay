@php
    $nav = [
        ['label' => 'Overview',       'route' => 'aggregator.dashboard',   'icon' => 'squares-2x2', 'ready' => true],
        ['label' => 'Agents',         'route' => 'aggregator.agents',      'icon' => 'users',       'ready' => true],
        ['label' => 'Liquidity',      'route' => 'aggregator.liquidity',   'icon' => 'wallet',      'ready' => true],
        ['label' => 'Commissions',    'route' => 'aggregator.commissions', 'icon' => 'banknotes',   'ready' => true],
        ['label' => 'Settlements',    'route' => 'aggregator.settlements', 'icon' => 'cash',        'ready' => true],
        ['label' => 'Network',        'route' => 'aggregator.network',     'icon' => 'map',         'ready' => true],
        ['label' => 'Risk & Alerts',  'route' => 'aggregator.risk',        'icon' => 'shield',      'ready' => true],
        ['label' => 'Insights & EOD', 'route' => 'aggregator.insights',    'icon' => 'chart-bar',   'ready' => true],
        ['label' => 'Support',        'route' => 'aggregator.support',     'icon' => 'chat',        'ready' => true],
        ['label' => 'Documents',      'route' => 'aggregator.documents',   'icon' => 'archive',     'ready' => true],
        ['label' => 'Reports',        'route' => 'aggregator.reports',     'icon' => 'document',    'ready' => true],
        ['label' => 'Data quality',   'route' => 'aggregator.data-quality','icon' => 'clipboard',   'ready' => true],
        ['label' => 'Profile & limits','route' => 'aggregator.profile',    'icon' => 'user-group',  'ready' => true],
    ];
    $user = auth()->user();
    $agg = app(App\Domain\Aggregator\AggregatorTenantService::class)->current($user);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="KoriePay Aggregator — the operating system for your agent network.">
    <title>{{ $title ?? 'Aggregator Command' }} · KoriePay</title>
    <x-vite-assets />
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased selection:bg-brand-2/25">

    {{-- Ambient canvas — restrained teal/emerald aura, institutional not neon. --}}
    <div class="fixed inset-0 -z-10 bg-[radial-gradient(120%_90%_at_50%_-10%,rgba(21,137,135,0.16),transparent_55%),radial-gradient(80%_60%_at_100%_100%,rgba(41,180,117,0.08),transparent_60%)]"></div>

    <div class="flex h-screen overflow-hidden">

        {{-- ── Desktop sidebar (≥1024px) ─────────────────────────────────── --}}
        <aside class="hidden lg:flex w-[264px] shrink-0 flex-col border-r border-line bg-panel/70 backdrop-blur-xl">
            <a href="{{ route('aggregator.dashboard') }}" class="flex h-16 shrink-0 items-center gap-3 border-b border-line px-5" aria-label="KoriePay Aggregator home">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand to-brand-2 text-lg font-black italic text-white shadow-glow-brand">K</span>
                <span class="text-base font-black tracking-tight">KoriePay <span class="text-brand">Aggregator</span></span>
            </a>

            <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto px-3 py-4" aria-label="Aggregator navigation">
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                    <a href="{{ route($item['route']) }}" aria-current="{{ $active ? 'page' : 'false' }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold transition-colors {{ $active ? 'bg-brand/10 text-brand' : 'text-muted hover:bg-panel-2 hover:text-ink' }}">
                        <x-kp.icon :name="$item['icon']" class="h-[18px] w-[18px]" stroke="1.8" />
                        {{ $item['label'] }}
                        @if (($item['soon'] ?? false))
                            <span class="ml-auto rounded-full bg-line/60 px-2 py-0.5 text-[9px] font-black uppercase tracking-wide text-muted/60">Soon</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="shrink-0 space-y-1 border-t border-line p-3">
                <a href="{{ route('aggregator.profile') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-muted hover:bg-panel-2 hover:text-ink">
                    <x-kp.icon name="user-group" class="h-[18px] w-[18px]" stroke="1.8" /> Profile & limits
                </a>
                <a href="{{ route('aggregator.support') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-muted hover:bg-panel-2 hover:text-ink">
                    <x-kp.icon name="question" class="h-[18px] w-[18px]" stroke="1.8" /> Help & support
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-bold text-muted hover:bg-crit/10 hover:text-crit">
                        <x-kp.icon name="logout" class="h-[18px] w-[18px]" stroke="1.8" /> Sign out
                    </button>
                </form>
            </div>
        </aside>

        {{-- ── Main column ───────────────────────────────────────────────── --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Header (§8) — greeting, aggregator id, system status, actions. --}}
            <header class="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-line bg-panel/40 px-4 backdrop-blur-xl sm:px-6">
                <div class="min-w-0">
                    <p class="truncate text-sm font-black text-ink">
                        {{ $title ?? 'Good day' }},
                        <span class="text-brand">{{ explode(' ', (string) ($user?->name ?? 'Operator'))[0] }}</span>
                    </p>
                    <p class="text-[11px] font-semibold text-muted">Aggregator ID: {{ $agg?->code ?? '—' }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button type="button" wire:click="$dispatch('toggle-command-search')"
                            class="hidden items-center gap-2 rounded-xl border border-line px-3 py-2 text-xs font-bold text-muted hover:bg-panel-2 md:flex" aria-label="Search">
                        <x-kp.icon name="magnifying-glass" class="h-4 w-4" stroke="2" />
                        <span>Search</span>
                        <x-kp.kbd :keys="['⌘', 'K']" />
                    </button>
                    <button type="button" wire:click="$dispatch('toggle-command-search')"
                            class="rounded-xl border border-line p-2 text-muted hover:bg-panel-2 md:hidden" aria-label="Search">
                        <x-kp.icon name="magnifying-glass" class="h-[18px] w-[18px]" stroke="1.8" />
                    </button>
                    <a href="{{ route('aggregator.risk') }}" class="relative rounded-xl border border-line p-2 text-muted hover:bg-panel-2" aria-label="Risk alerts">
                        <x-kp.icon name="bell" class="h-[18px] w-[18px]" stroke="1.8" />
                    </a>
                    <a href="{{ route('aggregator.support') }}" class="rounded-xl border border-line p-2 text-muted hover:bg-panel-2" aria-label="Support">
                        <x-kp.icon name="chat" class="h-[18px] w-[18px]" stroke="1.8" />
                    </a>
                    <a href="{{ route('aggregator.profile') }}" class="rounded-xl border border-line p-2 text-muted hover:bg-panel-2" aria-label="Profile">
                        <x-kp.icon name="user-group" class="h-[18px] w-[18px]" stroke="1.8" />
                    </a>
                </div>
            </header>

            <main id="cc-main" class="min-h-0 flex-1 overflow-y-auto p-4 pb-28 sm:p-6 lg:pb-8" role="main"
                  x-data @keydown.window.cmd.k.prevent="$dispatch('toggle-command-search')"
                  @keydown.window.ctrl.k.prevent="$dispatch('toggle-command-search')">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- ── Mobile bottom navigation (≤1023px) — mobile ops command strip ── --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-panel/90 backdrop-blur-xl lg:hidden pb-safe" aria-label="Mobile navigation" x-data="{ more: false }">
        <div class="grid grid-cols-5">
            <a href="{{ route('aggregator.dashboard') }}" class="flex flex-col items-center gap-1 py-3 text-[9px] font-black uppercase tracking-wider {{ request()->routeIs('aggregator.dashboard') ? 'text-brand' : 'text-muted' }}">
                <x-kp.icon name="squares-2x2" class="h-5 w-5" /> Overview
            </a>
            <a href="{{ route('aggregator.agents') }}" class="flex flex-col items-center gap-1 py-3 text-[9px] font-black uppercase tracking-wider {{ request()->routeIs('aggregator.agents*') ? 'text-brand' : 'text-muted' }}">
                <x-kp.icon name="users" class="h-5 w-5" /> Agents
            </a>
            <a href="{{ route('aggregator.liquidity') }}" class="flex flex-col items-center gap-1 py-3 text-[9px] font-black uppercase tracking-wider {{ request()->routeIs('aggregator.liquidity') ? 'text-brand' : 'text-muted' }}">
                <x-kp.icon name="wallet" class="h-5 w-5" /> Liquidity
            </a>
            <a href="{{ route('aggregator.risk') }}" class="flex flex-col items-center gap-1 py-3 text-[9px] font-black uppercase tracking-wider {{ request()->routeIs('aggregator.risk') ? 'text-brand' : 'text-muted' }}">
                <x-kp.icon name="shield" class="h-5 w-5" /> Alerts
            </a>
            <button type="button" @click="more = !more" class="flex flex-col items-center gap-1 py-3 text-[9px] font-black uppercase tracking-wider text-muted" aria-label="More">
                <x-kp.icon name="bars-3" class="h-5 w-5" /> More
            </button>
        </div>

        {{-- More sheet — the rest of the console on mobile. --}}
        <div x-show="more" x-cloak x-transition.opacity class="absolute inset-x-0 bottom-full border-t border-line bg-panel/95 backdrop-blur-xl pb-2">
            @foreach ($nav as $item)
                @if (in_array($item['route'], ['aggregator.dashboard', 'aggregator.agents', 'aggregator.liquidity', 'aggregator.risk'], true))
                    @continue
                @endif
                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-5 py-2.5 text-[13px] font-semibold {{ request()->routeIs($item['route']) ? 'text-brand' : 'text-muted' }}">
                    <x-kp.icon :name="$item['icon']" class="h-4 w-4" /> {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    {{-- Toasts (server → browser via Livewire $dispatch('toast')) --}}
    <div x-data="{ toasts: [], key: 0 }"
         @toast.window="key++; toasts.push({ id: key, message: $event.detail.message, type: $event.detail.type || 'info' }); setTimeout(() => toasts = toasts.filter(t => t.id !== key), 4200)"
         class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4" aria-live="polite">
        <template x-for="t in toasts" :key="t.id">
            <div x-show="true" x-transition.opacity class="glass-strong pointer-events-auto max-w-sm rounded-xl border px-4 py-3 text-sm font-semibold shadow-glass"
                 :class="t.type === 'error' ? 'border-crit/30 text-crit' : 'border-line text-ink'">
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>

    {{-- Global command search overlay (Ctrl/Cmd+K) — renders inside the shell. --}}
    <livewire:aggregator.command-search />

    @livewireScripts
</body>
</html>
