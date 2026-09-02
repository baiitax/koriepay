@php
    use App\Support\AdminNav;

    $navGroups = AdminNav::groups();
    $currentRoute = request()->route()?->getName();
    $pageLabel = AdminNav::labelFor((string) $currentRoute);
    $paletteActions = collect(AdminNav::actions())
        ->map(fn ($a) => ['label' => $a['label'], 'group' => $a['group'], 'url' => route($a['route'])])
        ->values()
        ->all();
    $appEnv = config('app.env');
    $envTone = in_array($appEnv, ['production', 'prod'], true) ? 'ok' : 'warn';
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KoriePay Command Center — {{ $pageLabel }}</title>

    {{-- Pre-paint theme (avoids flash): command center defaults to dark. --}}
    <script>
        (function () {
            var t = localStorage.getItem('kp-theme');
            if (t !== 'light' && t !== 'dark') t = 'dark';
            document.documentElement.classList.toggle('dark', t === 'dark');
        })();
    </script>

    {{-- Command palette actions injected server-side (permission-filtered in Phase 4+). --}}
    <script>window.kpPaletteActions = @json($paletteActions);</script>

    <x-vite-assets />
</head>

<body
    x-data="{
        theme: 'dark',
        sidebarCollapsed: false,
        mobileNavOpen: false,
        openDropdown: null,
        country: 'all',
        paletteOpen: false,
        paletteQuery: '',
        paletteIndex: 0,
        now: '',

        init() {
            this.theme = (localStorage.getItem('kp-theme') ?? 'dark') === 'dark' ? 'dark' : 'light';
            this.tick();
            setInterval(() => this.tick(), 1000);
        },
        tick() {
            this.now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        },
        applyTheme() {
            document.documentElement.classList.toggle('dark', this.theme === 'dark');
            localStorage.setItem('kp-theme', this.theme);
        },
        toggleTheme() { this.theme = this.theme === 'dark' ? 'light' : 'dark'; this.applyTheme(); },

        toggleDropdown(name) { this.openDropdown = this.openDropdown === name ? null : name; },
        closeDropdowns() { this.openDropdown = null; },

        countryLabel() { return { all: 'All Countries', ne: '🇳🇪 Niger', ng: '🇳🇬 Nigeria' }[this.country] ?? 'All Countries'; },
        setCountry(code) { this.country = code; this.closeDropdowns(); },

        openPalette() {
            this.paletteQuery = ''; this.paletteIndex = 0; this.paletteOpen = true;
            this.$nextTick(() => { const el = this.$refs.paletteInput; if (el) { el.focus(); el.select(); } });
        },
        closePalette() { this.paletteOpen = false; },
        get filteredActions() {
            const q = this.paletteQuery.trim().toLowerCase();
            const all = window.kpPaletteActions ?? [];
            if (!q) return all;
            return all.filter(a => a.label.toLowerCase().includes(q) || a.group.toLowerCase().includes(q));
        },
        paletteMove(delta) {
            const n = this.filteredActions.length; if (!n) return;
            this.paletteIndex = (this.paletteIndex + delta + n) % n;
        },
        paletteRun(i) {
            const a = this.filteredActions[i];
            if (a) window.location.href = a.url;
        },

        onKey(e) {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) || e.target.isContentEditable;
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                this.paletteOpen ? this.closePalette() : this.openPalette();
                return;
            }
            if (typing) return;
            if (e.key === '/' && !this.paletteOpen) { e.preventDefault(); this.openPalette(); return; }
            if (this.paletteOpen) {
                if (e.key === 'Escape') { e.preventDefault(); this.closePalette(); }
                else if (e.key === 'ArrowDown') { e.preventDefault(); this.paletteMove(1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); this.paletteMove(-1); }
                else if (e.key === 'Enter') { e.preventDefault(); this.paletteRun(this.paletteIndex); }
                return;
            }
            if (e.key.toLowerCase() === 'g') { this._gKey = Date.now(); clearTimeout(this._gT); this._gT = setTimeout(() => { this._gKey = 0; }, 800); return; }
            if (this._gKey && Date.now() - this._gKey < 800) {
                const map = { o: '/admin/dashboard', f: '/admin/transactions', a: '/admin/directory', s: '/admin/settings' };
                const target = map[e.key.toLowerCase()];
                if (target) { e.preventDefault(); this._gKey = 0; window.location.href = target; }
            }
        },
    }"
    x-init="init()"
    @keydown.window="onKey($event)"
    :class="{ 'overflow-hidden': mobileNavOpen }"
    class="min-h-screen bg-canvas text-ink antialiased font-sans selection:bg-brand-2/25 selection:text-ink"
>

    <a href="#cc-main" class="sr-only focus:not-sr-only focus:fixed focus:z-[100] focus:top-2 focus:left-2 focus:px-4 focus:py-2 focus:rounded-lg focus:bg-brand focus:text-white focus:text-sm focus:font-bold">Skip to content</a>

    {{-- =====================================================================
         MOBILE DRAWER (sidebar) — < lg
         ===================================================================== --}}
    <div x-show="mobileNavOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Navigation">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="mobileNavOpen = false"></div>
        <aside class="absolute inset-y-0 left-0 w-[280px] glass-strong border-r border-line shadow-2xl flex flex-col" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="h-16 flex items-center justify-between px-4 border-b border-line shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand to-brand-2 flex items-center justify-center text-white font-black text-lg italic shadow-glow-brand">K</span>
                    <span class="font-black text-ink text-base tracking-tight">KoriePay <span class="text-brand">Command</span></span>
                </a>
                <button type="button" @click="mobileNavOpen = false" class="p-2 rounded-lg text-muted hover:bg-panel-2 hover:text-ink" aria-label="Close navigation">
                    <x-kp.icon name="x-mark" class="w-5 h-5" stroke="2" />
                </button>
            </div>
            @include('layouts.partials.admin.sidebar', ['groups' => $navGroups, 'currentRoute' => $currentRoute, 'forceExpand' => true])
            <div class="p-3 border-t border-line">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-bold text-muted hover:text-crit hover:bg-crit/10 transition-all">
                        <x-kp.icon name="logout" class="w-[18px] h-[18px]" stroke="1.8" />
                        Logout
                    </button>
                </form>
            </div>
        </aside>
    </div>

    <div class="flex h-screen overflow-hidden">

        {{-- =====================================================================
             DESKTOP SIDEBAR — lg+
             ===================================================================== --}}
        <aside :class="sidebarCollapsed ? 'lg:w-[76px]' : 'lg:w-[264px]'"
               class="hidden lg:flex flex-col shrink-0 border-r border-line bg-panel/70 backdrop-blur-xl transition-all duration-300 ease-glass">
            <div class="h-16 flex items-center justify-between px-4 border-b border-line shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 overflow-hidden">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand to-brand-2 flex items-center justify-center text-white font-black text-lg italic shadow-glow-brand shrink-0">K</span>
                    <span x-show="!sidebarCollapsed" x-cloak class="font-black text-ink text-base tracking-tight whitespace-nowrap">KoriePay <span class="text-brand">Command</span></span>
                </a>
            </div>

            @include('layouts.partials.admin.sidebar', ['groups' => $navGroups, 'currentRoute' => $currentRoute, 'forceExpand' => false])

            <div class="p-3 border-t border-line shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-bold text-muted hover:text-crit hover:bg-crit/10 transition-all" title="Logout">
                        <x-kp.icon name="logout" class="w-[18px] h-[18px]" stroke="1.8" />
                        <span x-show="!sidebarCollapsed" x-cloak>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- =====================================================================
             MAIN COLUMN
             ===================================================================== --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- TOP COMMAND BAR --}}
            <header class="shrink-0 h-16 glass border-b border-line flex items-center gap-3 px-3 sm:px-5 z-30" x-data="{ showSearch: false }">
                {{-- Left: toggle + brand --}}
                <button type="button"
                        class="lg:hidden p-2 rounded-lg text-muted hover:bg-panel-2 hover:text-ink"
                        @click="mobileNavOpen = true"
                        aria-label="Open navigation">
                    <x-kp.icon name="bars-3" class="w-5 h-5" stroke="1.9" />
                </button>
                <button type="button"
                        class="hidden lg:inline-flex p-2 rounded-lg text-muted hover:bg-panel-2 hover:text-ink"
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                        :aria-expanded="!sidebarCollapsed">
                    <x-kp.icon name="bars-3" class="w-5 h-5" stroke="1.9" />
                </button>

                <div class="hidden md:block h-5 w-px bg-line mx-1"></div>

                <div class="hidden md:block min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-faint leading-none">{{ $pageLabel }}</p>
                    <p class="text-[11px] font-semibold text-muted leading-none mt-1 truncate">Super Admin · {{ strtoupper(Str::limit($user?->name ?? 'Operator', 22, '')) }}</p>
                </div>

                {{-- Center: global search trigger --}}
                <div class="flex-1 flex justify-center px-2">
                    <button type="button" @click="openPalette()"
                            class="hidden sm:flex items-center gap-2 w-full max-w-md px-3.5 py-2 rounded-xl glass-inset text-muted hover:text-ink hover:border-brand/40 transition-all group"
                            aria-haspopup="dialog" aria-label="Search commands (Ctrl+K or /)">
                        <x-kp.icon name="magnifying-glass" class="w-4 h-4" stroke="2" />
                        <span class="text-xs font-medium">Search anything…</span>
                        <span class="ml-auto inline-flex items-center gap-1">
                            <x-kp.kbd :keys="['Ctrl', 'K']" />
                        </span>
                    </button>
                </div>

                {{-- Right cluster --}}
                <div class="flex items-center gap-0.5 sm:gap-1">
                    {{-- Environment --}}
                    <span class="hidden xl:inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg glass-inset" title="Application environment">
                        <span class="w-1.5 h-1.5 rounded-full {{ $envTone === 'ok' ? 'bg-ok' : 'bg-brand-gold' }}"></span>
                        <span class="text-[10px] font-black uppercase tracking-wider text-muted">{{ $appEnv }}</span>
                    </span>

                    {{-- Country selector --}}
                    <div class="relative" @click.outside="closeDropdowns()">
                        <button type="button" @click="toggleDropdown('country')" :aria-expanded="openDropdown === 'country'"
                                class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg glass-inset text-muted hover:text-ink hover:border-brand/40 transition-all"
                                aria-label="Switch country context">
                            <x-kp.icon name="globe" class="w-4 h-4" stroke="1.9" />
                            <span class="text-xs font-bold" x-text="countryLabel()"></span>
                            <x-kp.icon name="chevron-down" class="w-3 h-3" stroke="2.4" x-bind:class="openDropdown === 'country' && 'rotate-180'" />
                        </button>
                        <div x-show="openDropdown === 'country'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-56 glass-strong rounded-2xl shadow-2xl border border-line p-1.5 z-40" role="menu">
                            <p class="px-3 py-2 text-[9px] font-black uppercase tracking-[0.16em] text-faint">Country context — filters every section</p>
                            <button type="button" @click="setCountry('all')" role="menuitemradio" :aria-checked="country === 'all'"
                                    class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all"
                                    :class="country === 'all' ? 'bg-brand/10 text-brand' : 'text-muted hover:bg-panel-2 hover:text-ink'">
                                <span class="w-5 text-center">🌍</span> All Countries
                                <span x-show="country === 'all'" class="ml-auto"><x-kp.icon name="check" class="w-4 h-4" stroke="2.4" /></span>
                            </button>
                            <button type="button" @click="setCountry('ne')" role="menuitemradio" :aria-checked="country === 'ne'"
                                    class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all"
                                    :class="country === 'ne' ? 'bg-brand/10 text-brand' : 'text-muted hover:bg-panel-2 hover:text-ink'">
                                <span class="w-5 text-center">🇳🇪</span> Niger
                                <span class="text-[10px] font-bold text-faint">XOF · BCEAO</span>
                                <span x-show="country === 'ne'" class="ml-auto"><x-kp.icon name="check" class="w-4 h-4" stroke="2.4" /></span>
                            </button>
                            <button type="button" @click="setCountry('ng')" role="menuitemradio" :aria-checked="country === 'ng'"
                                    class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all"
                                    :class="country === 'ng' ? 'bg-brand/10 text-brand' : 'text-muted hover:bg-panel-2 hover:text-ink'">
                                <span class="w-5 text-center">🇳🇬</span> Nigeria
                                <span class="text-[10px] font-bold text-faint">NGN · CBN</span>
                                <span x-show="country === 'ng'" class="ml-auto"><x-kp.icon name="check" class="w-4 h-4" stroke="2.4" /></span>
                            </button>
                        </div>
                    </div>

                    {{-- Theme toggle --}}
                    <button type="button" @click="toggleTheme()" class="p-2 rounded-lg text-muted hover:bg-panel-2 hover:text-ink transition-all" :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'" :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'">
                        <x-kp.icon :name="'sun'" class="w-[18px] h-[18px] hidden dark:block" stroke="1.9" />
                        <x-kp.icon :name="'moon'" class="w-[18px] h-[18px] dark:hidden" stroke="1.9" />
                    </button>

                    {{-- Notifications --}}
                    <div class="relative" @click.outside="closeDropdowns()">
                        <button type="button" @click="toggleDropdown('notifications')" :aria-expanded="openDropdown === 'notifications'" class="p-2 rounded-lg text-muted hover:bg-panel-2 hover:text-ink relative transition-all" aria-label="Notifications">
                            <x-kp.icon name="bell" class="w-[18px] h-[18px]" stroke="1.9" />
                        </button>
                        <div x-show="openDropdown === 'notifications'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-80 glass-strong rounded-2xl shadow-2xl border border-line z-40 overflow-hidden">
                            <div class="px-4 py-3 border-b border-line flex items-center justify-between">
                                <p class="text-sm font-extrabold text-ink">Notifications</p>
                                <x-kp.badge tone="neutral">0 new</x-kp.badge>
                            </div>
                            <div class="p-4">
                                <x-kp.empty-state icon="bell" title="No notifications yet" description="The notification pipeline (critical, financial, risk, security, operations) connects with the Phase 9 data layer. Nothing is being hidden — there is simply no data wired in yet." />
                            </div>
                        </div>
                    </div>

                    {{-- Security alerts --}}
                    <div class="relative" @click.outside="closeDropdowns()">
                        <button type="button" @click="toggleDropdown('security')" :aria-expanded="openDropdown === 'security'" class="p-2 rounded-lg text-muted hover:bg-panel-2 hover:text-ink relative transition-all" aria-label="Security alerts">
                            <x-kp.icon name="shield" class="w-[18px] h-[18px]" stroke="1.9" />
                        </button>
                        <div x-show="openDropdown === 'security'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-80 glass-strong rounded-2xl shadow-2xl border border-line z-40 overflow-hidden">
                            <div class="px-4 py-3 border-b border-line">
                                <p class="text-sm font-extrabold text-ink">Security Events</p>
                            </div>
                            <div class="p-4">
                                <x-kp.empty-state icon="shield-check" title="No security events" description="Failed logins, new devices, suspicious sessions and privilege changes will surface here. This panel wires to the security event stream in Phase 9." />
                            </div>
                        </div>
                    </div>

                    {{-- System status --}}
                    <div class="relative" @click.outside="closeDropdowns()">
                        <button type="button" @click="toggleDropdown('status')" :aria-expanded="openDropdown === 'status'" class="p-2 rounded-lg text-muted hover:bg-panel-2 hover:text-ink transition-all" aria-label="System status">
                            <x-kp.icon name="heart-pulse" class="w-[18px] h-[18px]" stroke="1.9" />
                        </button>
                        <div x-show="openDropdown === 'status'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-80 glass-strong rounded-2xl shadow-2xl border border-line z-40 overflow-hidden">
                            <div class="px-4 py-3 border-b border-line">
                                <p class="text-sm font-extrabold text-ink">System Status</p>
                                <p class="text-[10px] font-bold text-faint uppercase tracking-wider mt-0.5">Data as of <span x-text="now"></span></p>
                            </div>
                            <div class="p-4">
                                <div class="space-y-2.5">
                                    <x-kp.health-indicator state="unknown" label="Health telemetry" sub="Not connected yet" :pulse="false" />
                                </div>
                                <p class="mt-3 text-[11px] leading-relaxed text-muted">Live health checks (payments, transfers, rails, queues, providers) connect in Phase 9 Stage 6. No status is claimed before real telemetry exists.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Help / shortcuts --}}
                    <div class="relative hidden sm:block" @click.outside="closeDropdowns()">
                        <button type="button" @click="toggleDropdown('help')" :aria-expanded="openDropdown === 'help'" class="p-2 rounded-lg text-muted hover:bg-panel-2 hover:text-ink transition-all" aria-label="Help and keyboard shortcuts">
                            <x-kp.icon name="question-mark-circle" class="w-[18px] h-[18px]" stroke="1.9" />
                        </button>
                        <div x-show="openDropdown === 'help'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-72 glass-strong rounded-2xl shadow-2xl border border-line z-40 overflow-hidden">
                            <div class="px-4 py-3 border-b border-line">
                                <p class="text-sm font-extrabold text-ink">Keyboard Shortcuts</p>
                            </div>
                            <div class="p-3 space-y-2">
                                <div class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-panel-2"><span class="text-xs font-medium text-muted">Open command palette</span><x-kp.kbd :keys="['Ctrl', 'K']" /></div>
                                <div class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-panel-2"><span class="text-xs font-medium text-muted">Search</span><x-kp.kbd :keys="['/']" /></div>
                                <div class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-panel-2"><span class="text-xs font-medium text-muted">Overview</span><span class="inline-flex gap-1"><x-kp.kbd :keys="['G']" /><x-kp.kbd :keys="['O']" /></span></div>
                                <div class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-panel-2"><span class="text-xs font-medium text-muted">Transactions</span><span class="inline-flex gap-1"><x-kp.kbd :keys="['G']" /><x-kp.kbd :keys="['F']" /></span></div>
                                <div class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-panel-2"><span class="text-xs font-medium text-muted">Agents</span><span class="inline-flex gap-1"><x-kp.kbd :keys="['G']" /><x-kp.kbd :keys="['A']" /></span></div>
                                <div class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-panel-2"><span class="text-xs font-medium text-muted">System</span><span class="inline-flex gap-1"><x-kp.kbd :keys="['G']" /><x-kp.kbd :keys="['S']" /></span></div>
                            </div>
                        </div>
                    </div>

                    {{-- Profile --}}
                    <div class="relative" @click.outside="closeDropdowns()">
                        <button type="button" @click="toggleDropdown('profile')" :aria-expanded="openDropdown === 'profile'"
                                class="flex items-center gap-2.5 pl-2 pr-1 py-1 rounded-xl hover:bg-panel-2 transition-all group">
                            <span class="hidden sm:flex flex-col items-end leading-none">
                                <span class="text-xs font-extrabold text-ink">{{ $user?->name ?? 'Operator' }}</span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-brand mt-0.5">Super Admin</span>
                            </span>
                            <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-brand to-brand-2 text-white font-black text-[11px] flex items-center justify-center shadow-sm">
                                {{ strtoupper(Str::limit($user?->name ?? 'OP', 2, '')) }}
                            </span>
                            <x-kp.icon name="chevron-down" class="hidden sm:block w-3.5 h-3.5 text-faint" stroke="2.4" />
                        </button>
                        <div x-show="openDropdown === 'profile'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-64 glass-strong rounded-2xl shadow-2xl border border-line z-40 overflow-hidden">
                            <div class="px-4 py-3 border-b border-line">
                                <p class="text-sm font-extrabold text-ink truncate">{{ $user?->name }}</p>
                                <p class="text-[10px] font-bold text-muted uppercase tracking-wider mt-0.5">{{ $user?->email }}</p>
                            </div>
                            <div class="p-1.5">
                                <span class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-[12px] font-semibold text-faint cursor-not-allowed select-none" title="Ships in Phase 9">
                                    <x-kp.icon name="identification" class="w-4 h-4" stroke="1.9" /> Profile &amp; sessions <span class="ml-auto text-[8px] font-black uppercase">P9</span>
                                </span>
                                <span class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-[12px] font-semibold text-faint cursor-not-allowed select-none" title="Ships in Phase 9">
                                    <x-kp.icon name="shield-check" class="w-4 h-4" stroke="1.9" /> MFA &amp; devices <span class="ml-auto text-[8px] font-black uppercase">P9</span>
                                </span>
                                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-[12px] font-bold text-crit hover:bg-crit/10 transition-all">
                                        <x-kp.icon name="logout" class="w-4 h-4" stroke="1.9" /> Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- =====================================================================
                 CONTENT
                 ===================================================================== --}}
            <main id="cc-main" class="flex-1 overflow-y-auto cc-scrollbar relative">
                <div class="p-4 sm:p-5 lg:p-6 max-w-[1600px] mx-auto w-full">
                    {{ $slot }}
                </div>

                <footer class="px-6 pb-5">
                    <div class="border-t border-line pt-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-[10px] font-semibold text-faint">
                        <p>KoriePay Command Center · Super Admin · v0.1 (Stage 1 Foundation — Phase 9)</p>
                        <p class="inline-flex items-center gap-1.5"><x-kp.icon name="clock" class="w-3 h-3" stroke="2.2" /> Local time <span x-text="now"></span> · Every metric will display its own freshness stamp — never implied real-time.</p>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    {{-- =====================================================================
         COMMAND PALETTE (Ctrl/⌘+K or /)
         ===================================================================== --}}
    <div x-show="paletteOpen" x-cloak class="fixed inset-0 z-[80]" role="dialog" aria-modal="true" aria-label="Command palette">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closePalette()"></div>
        <div class="relative mx-auto mt-[12vh] w-[92vw] max-w-lg glass-strong rounded-2xl shadow-2xl border border-line overflow-hidden animate-scale-in">
            <div class="flex items-center gap-3 px-4 border-b border-line">
                <x-kp.icon name="magnifying-glass" class="w-5 h-5 text-faint shrink-0" stroke="2" />
                <input x-ref="paletteInput" x-model="paletteQuery" type="text" autocomplete="off" spellcheck="false"
                       placeholder="Search commands… (or type / to jump)"
                       class="flex-1 bg-transparent border-0 outline-none py-3.5 text-sm font-semibold text-ink placeholder:text-faint focus:ring-0"
                       aria-label="Search commands" />
                <x-kp.kbd :keys="['Esc']" />
            </div>
            <div class="max-h-[46vh] overflow-y-auto cc-scrollbar p-2">
                <template x-if="filteredActions.length === 0">
                    <div class="p-6 text-center">
                        <p class="text-xs font-semibold text-muted">No matching commands.</p>
                        <p class="mt-1 text-[10px] font-medium text-faint">Deep search (customers, agents, transactions, references) arrives with the Phase 9 data layer.</p>
                    </div>
                </template>
                <template x-for="(action, i) in filteredActions" :key="action.url">
                    <button type="button" @click="paletteRun(i)" @mouseenter="paletteIndex = i"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left transition-all"
                            :class="i === paletteIndex ? 'bg-brand/10 text-brand' : 'text-muted hover:bg-panel-2 hover:text-ink'">
                        <x-kp.icon name="chevron-right" class="w-4 h-4 shrink-0" stroke="2.2" />
                        <span class="flex-1 min-w-0">
                            <span class="block text-[13px] font-bold truncate" x-text="action.label"></span>
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-faint" x-text="action.group"></span>
                        </span>
                        <span class="text-[10px] font-black text-faint uppercase">Go</span>
                    </button>
                </template>
            </div>
            <div class="px-4 py-2.5 border-t border-line flex items-center gap-3 text-[10px] font-semibold text-faint">
                <span class="inline-flex items-center gap-1"><x-kp.kbd :keys="['↑']" /><x-kp.kbd :keys="['↓']" /> navigate</span>
                <span class="inline-flex items-center gap-1"><x-kp.kbd :keys="['Enter']" /> open</span>
                <span class="inline-flex items-center gap-1"><x-kp.kbd :keys="['G']" /> then <x-kp.kbd :keys="['O','F','A','S']" /> jump</span>
                <span class="ml-auto">Only actions you are permitted to perform appear.</span>
            </div>
        </div>
    </div>

    {{-- =====================================================================
         MOBILE BOTTOM NAV — < lg (directive §57: mobile ops command center)
         ===================================================================== --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 glass-strong border-t border-line grid grid-cols-5" aria-label="Mobile quick navigation">
        <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center gap-1 py-2.5 text-[9px] font-black uppercase tracking-wider {{ $currentRoute === 'admin.dashboard' ? 'text-brand' : 'text-muted' }}">
            <x-kp.icon name="squares-2x2" class="w-5 h-5" stroke="1.9" />Overview
        </a>
        <a href="{{ route('admin.transactions') }}" class="flex flex-col items-center gap-1 py-2.5 text-[9px] font-black uppercase tracking-wider {{ $currentRoute === 'admin.transactions' ? 'text-brand' : 'text-muted' }}">
            <x-kp.icon name="arrow-right-left" class="w-5 h-5" stroke="1.9" />Txns
        </a>
        <a href="{{ route('admin.directory') }}" class="flex flex-col items-center gap-1 py-2.5 text-[9px] font-black uppercase tracking-wider {{ $currentRoute === 'admin.directory' ? 'text-brand' : 'text-muted' }}">
            <x-kp.icon name="user-group" class="w-5 h-5" stroke="1.9" />Agents
        </a>
        <a href="{{ route('admin.kyc-hub') }}" class="flex flex-col items-center gap-1 py-2.5 text-[9px] font-black uppercase tracking-wider {{ $currentRoute === 'admin.kyc-hub' ? 'text-brand' : 'text-muted' }}">
            <x-kp.icon name="identification" class="w-5 h-5" stroke="1.9" />KYC
        </a>
        <button type="button" @click="mobileNavOpen = true" class="flex flex-col items-center gap-1 py-2.5 text-[9px] font-black uppercase tracking-wider text-muted">
            <x-kp.icon name="bars-3" class="w-5 h-5" stroke="1.9" />More
        </button>
    </nav>

    {{-- Padding so the mobile bottom nav never overlaps content. --}}
    <div class="lg:hidden h-16"></div>
</body>
</html>
