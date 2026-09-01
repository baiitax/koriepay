@php
    // Stage 5 — locale from session (en|fr|ha stub). Applied here so every
    // component under this layout sees the same app locale.
    $locale = session('locale', 'en');
    if (in_array($locale, ['en', 'fr', 'ha'], true)) {
        app()->setLocale($locale);
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="KoriePay — your personal bank, wallet and currency exchange companion.">

    <title>{{ $title ?? 'KoriePay' }} · KoriePay</title>

    {{-- System font stack (zero network dependency — low-bandwidth friendly). --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom, 1.25rem); }
        .pt-safe { padding-top: env(safe-area-inset-top, 0px); }
    </style>
</head>
<body class="min-h-screen font-sans antialiased text-ink selection:bg-brand/30">

    {{-- Ambient canvas: deep neutral with a restrained teal aura — glass stays readable. --}}
    <div class="fixed inset-0 -z-10 bg-[radial-gradient(120%_90%_at_50%_-10%,rgba(21,137,135,0.22),transparent_55%),radial-gradient(80%_60%_at_100%_100%,rgba(41,180,117,0.10),transparent_60%)]"></div>

    <div class="mx-auto w-full max-w-7xl lg:flex lg:gap-6 lg:px-6">

        {{-- ── Desktop sidebar (≥1024px) ─────────────────────────────────── --}}
        <aside class="hidden lg:flex lg:w-64 lg:shrink-0 lg:flex-col lg:sticky lg:top-0 lg:h-screen lg:py-6">
            <x-customer.desktop-sidebar />
        </aside>

        {{-- ── Main column ───────────────────────────────────────────────── --}}
        <main class="min-h-screen w-full pb-28 lg:pb-10" role="main">
            {{ $slot }}
        </main>
    </div>

    {{-- ── Mobile bottom navigation (≤1023px) ───────────────────────────── --}}
    <x-customer.bottom-nav />

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

    @livewireScripts
</body>
</html>
