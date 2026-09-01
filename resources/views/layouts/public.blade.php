<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KoriePay | @yield('title', 'Borderless Liquidity Grid')</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 selection:bg-korie-green selection:text-white" x-data="{ mobileMenuOpen: false, lang: 'en' }">

    <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-lg border-b border-slate-200">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            
            <a href="{{ route('home') }}" class="flex items-center space-x-2 z-50">
                <div class="w-10 h-10 bg-gradient-to-br from-korie-green to-pale-blue rounded-lg flex items-center justify-center shadow-sm border border-korie-green/10">
                    <span class="text-white font-bold text-xl">K</span>
                </div>
                <span class="text-xl font-black italic tracking-tight text-slate-900">KoriePay</span>
            </a>
            
            <div class="hidden lg:flex items-center space-x-8">
                
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="flex items-center space-x-1 text-slate-700 hover:text-korie-green transition font-bold text-sm py-4">
                        <span>Solutions</span>
                        <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition.opacity.duration.200ms class="absolute top-[100%] left-1/2 -translate-x-1/2 w-[600px] bg-white border border-slate-200 rounded-2xl shadow-xl p-6 grid grid-cols-2 gap-6">
                        <a href="{{ route('solutions.p2p') }}" class="group block p-4 rounded-xl hover:bg-slate-50 transition">
                            <div class="font-bold text-slate-900 group-hover:text-korie-green mb-1">Cross-Border P2P</div>
                            <div class="text-xs text-slate-500">NGN/XOF instant settlement.</div>
                        </a>
                        <a href="{{ route('solutions.agency') }}" class="group block p-4 rounded-xl hover:bg-slate-50 transition">
                            <div class="font-bold text-slate-900 group-hover:text-korie-green mb-1">Agency Banking</div>
                            <div class="text-xs text-slate-500">Manage vast cash-in/cash-out networks.</div>
                        </a>
                        <a href="{{ route('solutions.adashi') }}" class="group block p-4 rounded-xl hover:bg-slate-50 transition">
                            <div class="font-bold text-slate-900 group-hover:text-korie-green mb-1">Adashi Pools</div>
                            <div class="text-xs text-slate-500">Automated group savings/Esusu.</div>
                        </a>
                        <a href="{{ route('solutions.islamic') }}" class="group block p-4 rounded-xl hover:bg-slate-50 transition">
                            <div class="font-bold text-slate-900 group-hover:text-korie-green mb-1">Islamic Finance</div>
                            <div class="text-xs text-slate-500">Shariah-compliant Murabaha/Qard Hassan.</div>
                        </a>
                    </div>
                </div>

                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="flex items-center space-x-1 text-slate-700 hover:text-korie-green transition font-bold text-sm py-4">
                        <span>Developers</span>
                        <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition.opacity.duration.200ms class="absolute top-[100%] left-0 w-[250px] bg-white border border-slate-200 rounded-2xl shadow-xl p-3">
                        <a href="{{ route('developers.docs') }}" class="block p-3 rounded-lg hover:bg-slate-50 font-medium text-sm text-slate-700 hover:text-korie-green transition">API Documentation</a>
                        <a href="{{ route('developers.api') }}" class="block p-3 rounded-lg hover:bg-slate-50 font-medium text-sm text-slate-700 hover:text-korie-green transition">API Reference</a>
                        <a href="{{ route('developers.status') }}" class="block p-3 rounded-lg hover:bg-slate-50 font-medium text-sm text-slate-700 hover:text-korie-green transition flex justify-between">System Status <span class="w-2 h-2 rounded-full bg-korie-green self-center shadow-lg shadow-korie-green/20"></span></a>
                    </div>
                </div>

                <a href="{{ route('pricing') }}" class="text-slate-700 hover:text-korie-green transition font-bold text-sm">Pricing</a>
                <a href="{{ route('company.about') }}" class="text-slate-700 hover:text-korie-green transition font-bold text-sm">Company</a>
            </div>
            
            <div class="flex items-center space-x-4">
                
                <div class="relative hidden sm:block" x-data="{ langOpen: false }">
                    <button @click="langOpen = !langOpen" @click.away="langOpen = false" class="flex items-center space-x-2 border border-slate-200 rounded-lg px-3 py-2 text-sm hover:border-korie-green transition bg-white">
                        <span class="font-bold text-slate-700" x-text="lang.toUpperCase()">EN</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    
                    <div x-show="langOpen" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" class="absolute top-full right-0 mt-2 w-36 bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden">
                        <button @click="lang = 'en'; langOpen = false" class="w-full text-left px-4 py-3 transition font-bold text-sm flex justify-between items-center" :class="lang === 'en' ? 'bg-emerald-success/30 text-korie-teal' : 'hover:bg-slate-50 text-slate-600'">
                            English <svg x-show="lang === 'en'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        <button @click="lang = 'fr'; langOpen = false" class="w-full text-left px-4 py-3 transition font-bold text-sm flex justify-between items-center" :class="lang === 'fr' ? 'bg-emerald-success/30 text-korie-teal' : 'hover:bg-slate-50 text-slate-600'">
                            Français <svg x-show="lang === 'fr'" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        <button @click="lang = 'ha'; langOpen = false" class="w-full text-left px-4 py-3 transition font-bold text-sm flex justify-between items-center" :class="lang === 'ha' ? 'bg-emerald-success/30 text-korie-teal' : 'hover:bg-slate-50 text-slate-600'">
                            Hausa <svg x-show="lang === 'ha'" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </div>
                
                <a href="{{ route('login') }}" class="hidden md:block text-slate-700 hover:text-korie-green transition font-bold text-sm">Sign In</a>
                <a href="{{ route('register') }}" class="bg-gradient-to-r from-korie-green to-pale-blue text-white px-5 py-2.5 rounded-lg font-bold hover:shadow-lg hover:shadow-korie-green/20 transition active:scale-95 text-sm">
                    Access Grid
                </a>

                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden text-slate-700 p-2">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>

        <div x-show="mobileMenuOpen" x-cloak x-transition class="lg:hidden border-t border-slate-100 bg-white absolute w-full shadow-2xl overflow-y-auto max-h-[80vh]">
            <div class="px-6 py-4 space-y-2 flex flex-col">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 mt-4">Solutions</div>
                <a href="{{ route('solutions.agency') }}" class="font-bold text-slate-700 py-2">Agency Banking</a>
                <a href="{{ route('solutions.p2p') }}" class="font-bold text-slate-700 py-2">Borderless P2P</a>
                
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 mt-4">Developers</div>
                <a href="{{ route('developers.docs') }}" class="font-bold text-slate-700 py-2">API Documentation</a>
                
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 mt-4">Company</div>
                <a href="{{ route('pricing') }}" class="font-bold text-slate-700 py-2">Pricing</a>
                <a href="{{ route('company.about') }}" class="font-bold text-slate-700 py-2">About Us</a>
                <a href="{{ route('trust.security') }}" class="font-bold text-slate-700 py-2">Security & Trust</a>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="bg-white border-t border-slate-200 pt-20 pb-10">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8 mb-16">
                
                <div class="col-span-2 lg:col-span-2 pr-10">
                    <a href="{{ route('home') }}" class="flex items-center space-x-2 mb-6">
                        <div class="w-8 h-8 bg-gradient-to-br from-korie-green to-pale-blue rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">K</span>
                        </div>
                        <span class="text-lg font-black italic tracking-tight text-slate-900">KoriePay</span>
                    </a>
                    <p class="text-slate-500 text-sm mb-6 max-w-xs leading-relaxed">The borderless liquidity grid powering enterprise finance across West Africa.</p>
                    <div class="flex items-center space-x-2 text-sm font-bold text-slate-700">
                        <span class="w-2.5 h-2.5 rounded-full bg-korie-green shadow-lg shadow-korie-green/30"></span>
                        <span>All Systems Operational</span>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-slate-900 mb-4 text-sm uppercase tracking-wider">Solutions</h4>
                    <ul class="space-y-3 text-sm text-slate-500 font-medium">
                        <li><a href="{{ route('solutions.p2p') }}" class="hover:text-korie-green transition">Borderless P2P</a></li>
                        <li><a href="{{ route('solutions.agency') }}" class="hover:text-korie-green transition">Agency Banking</a></li>
                        <li><a href="{{ route('solutions.adashi') }}" class="hover:text-korie-green transition">Adashi Pools</a></li>
                        <li><a href="{{ route('pricing') }}" class="hover:text-korie-green transition">Pricing & Fees</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-slate-900 mb-4 text-sm uppercase tracking-wider">Developers</h4>
                    <ul class="space-y-3 text-sm text-slate-500 font-medium">
                        <li><a href="{{ route('developers.docs') }}" class="hover:text-korie-green transition">API Documentation</a></li>
                        <li><a href="{{ route('developers.api') }}" class="hover:text-korie-green transition">API Reference</a></li>
                        <li><a href="{{ route('developers.status') }}" class="hover:text-korie-green transition">System Status</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-slate-900 mb-4 text-sm uppercase tracking-wider">Company</h4>
                    <ul class="space-y-3 text-sm text-slate-500 font-medium">
                        <li><a href="{{ route('company.about') }}" class="hover:text-korie-green transition">About Us</a></li>
                        <li><a href="{{ route('company.careers') }}" class="hover:text-korie-green transition">Careers <span class="bg-korie-green/10 text-korie-green px-2 py-0.5 rounded text-[10px] uppercase font-bold ml-1">Hiring</span></a></li>
                        <li><a href="{{ route('support.blog') }}" class="hover:text-korie-green transition">Insights (Blog)</a></li>
                        <li><a href="{{ route('company.press') }}" class="hover:text-korie-green transition">Press/Newsroom</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-slate-900 mb-4 text-sm uppercase tracking-wider">Trust & Legal</h4>
                    <ul class="space-y-3 text-sm text-slate-500 font-medium">
                        <li><a href="{{ route('trust.security') }}" class="hover:text-korie-green transition">Security Posture</a></li>
                        <li><a href="{{ route('trust.compliance') }}" class="hover:text-korie-green transition">Licenses (CBN/BCEAO)</a></li>
                        <li><a href="{{ route('trust.privacy') }}" class="hover:text-korie-green transition">Privacy Policy</a></li>
                        <li><a href="{{ route('trust.terms') }}" class="hover:text-korie-green transition">Terms of Service</a></li>
                    </ul>
                </div>

            </div>
            
            <div class="border-t border-slate-200 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-slate-400 text-sm font-medium">&copy; {{ date('Y') }} KoriePay Liquidity Systems. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>