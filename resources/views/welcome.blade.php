<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KoriePay - Borderless Liquidity Grid</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 selection:bg-korie-green selection:text-white" x-data="{ mobileMenuOpen: false, langMenuOpen: false }">

    <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-lg border-b border-slate-200">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            
            <a href="/" class="flex items-center space-x-2">
                <div class="w-10 h-10 bg-gradient-to-br from-korie-green to-korie-teal rounded-lg flex items-center justify-center shadow-sm">
                    <span class="text-white font-bold text-xl">K</span>
                </div>
                <span class="text-xl font-black italic tracking-tight text-slate-900">KoriePay</span>
            </a>
            
            <div class="hidden md:flex items-center space-x-8">
                <a href="#features" class="text-slate-700 hover:text-korie-green transition font-bold text-sm">Features</a>
                <a href="#solutions" class="text-slate-700 hover:text-korie-green transition font-bold text-sm">Solutions</a>
                <a href="#pricing" class="text-slate-700 hover:text-korie-green transition font-bold text-sm">Pricing</a>
            </div>
            
            <div class="flex items-center space-x-4">
                
                <div class="relative">
                    <button @click="langMenuOpen = !langMenuOpen" @click.away="langMenuOpen = false" class="flex items-center space-x-2 border border-slate-200 rounded-lg px-3 py-2 text-sm hover:border-korie-green transition">
                        <span>🇬🇧</span>
                        <span class="font-bold text-slate-700">EN</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    
                    <div x-show="langMenuOpen" x-cloak class="absolute top-full right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden" x-transition>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 hover:bg-slate-50 transition bg-emerald-success/50 text-korie-teal font-bold">
                            <span class="text-lg">🇬🇧</span><span>English</span>
                        </a>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 hover:bg-slate-50 transition text-slate-600 font-bold">
                            <span class="text-lg">🇫🇷</span><span>Français</span>
                        </a>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 hover:bg-slate-50 transition text-slate-600 font-bold">
                            <span class="text-lg">🇳🇬</span><span>Hausa</span>
                        </a>
                    </div>
                </div>
                
                <a href="{{ route('login') }}" class="hidden md:block text-slate-700 hover:text-korie-green transition font-bold text-sm">Sign In</a>
                <a href="{{ route('register') }}" class="bg-gradient-to-r from-korie-green to-korie-teal text-white px-6 py-2.5 rounded-lg font-bold hover:shadow-lg hover:shadow-korie-green/20 transition active:scale-95 text-sm">
                    Access Grid
                </a>

                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-slate-700">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>

        <div x-show="mobileMenuOpen" x-cloak class="md:hidden border-t border-slate-100 bg-white absolute w-full shadow-2xl" x-transition>
            <div class="px-6 py-4 space-y-4 flex flex-col">
                <a href="#features" class="font-bold text-slate-700">Features</a>
                <a href="#solutions" class="font-bold text-slate-700">Solutions</a>
                <a href="{{ route('login') }}" class="font-bold text-korie-teal">Sign In</a>
            </div>
        </div>
    </nav>

    <section class="relative pt-20 pb-24 lg:pt-32 lg:pb-32 overflow-hidden">
        <div class="container mx-auto px-6 grid lg:grid-cols-2 gap-12 items-center relative z-10">
            <div class="max-w-2xl">
                <div class="inline-flex items-center space-x-2 bg-emerald-success px-4 py-2 rounded-full mb-6 border border-korie-green/20 shadow-sm">
                    <span class="w-2.5 h-2.5 bg-korie-green rounded-full animate-pulse"></span>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Live in Niger & Nigeria</span>
                </div>
                
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold text-slate-900 mb-6 leading-[1.1] tracking-tight">
                    Banking Without Barriers, Powered by Trust.
                </h1>
                
                <p class="text-lg text-slate-500 mb-10 leading-relaxed font-medium">
                    Send money across Niger and Nigeria instantly. Join Adashi savings pools, get Shariah-compliant financing, and build your enterprise agent network.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 mb-10">
                    <a href="{{ route('register') }}" class="text-center bg-slate-900 text-white px-8 py-4 rounded-xl font-bold hover:bg-slate-800 shadow-xl shadow-slate-900/10 transition-all active:scale-[0.98]">
                        Open Account - Free
                    </a>
                    <a href="#solutions" class="text-center bg-white border-2 border-slate-200 text-slate-700 px-8 py-4 rounded-xl font-bold hover:border-korie-green hover:text-korie-teal transition-all active:scale-[0.98]">
                        Become an Agent
                    </a>
                </div>
            </div>
            
            <div class="relative mt-12 lg:mt-0 lg:ml-10">
                <div class="absolute inset-0 bg-gradient-to-tr from-korie-green/20 to-korie-teal/20 blur-3xl -z-10 rounded-[3rem]"></div>
                
                <div class="bg-white rounded-[2rem] shadow-2xl p-8 border border-slate-200/60 relative z-10 text-center min-h-[400px] flex flex-col justify-center items-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-2xl flex items-center justify-center shadow-inner mb-6 border border-slate-100">
                        <svg class="w-10 h-10 text-korie-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">KoriePay Terminal</h3>
                    <p class="text-slate-500 font-medium mt-2">Sign in to view your dashboard</p>
                </div>
                
                <div class="absolute -bottom-6 -left-8 bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-6 border border-slate-100 z-20">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Total Processed</div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight font-mono">₦2.4B <span class="text-slate-300 font-normal">/</span> 48M XOF</div>
                </div>
            </div>
        </div>
    </section>

    <section id="solutions" class="py-24 bg-white relative">
        <div class="container mx-auto px-6">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">
                    Built For Your specific Needs
                </h2>
                <p class="text-lg font-medium text-slate-500">
                    Whether you are managing personal finances, running a local shop, or operating a regional liquidity network.
                </p>
            </div>
            
            <div class="grid lg:grid-cols-3 gap-8 items-stretch">
                <div class="bg-slate-50 rounded-[2rem] p-8 border border-slate-200 hover:border-korie-green/50 transition-colors flex flex-col">
                    <h3 class="text-2xl font-black text-slate-900 mb-6 tracking-tight">For Individuals</h3>
                    <ul class="space-y-4 mb-10 flex-grow">
                        <li class="flex items-start space-x-3 font-medium text-slate-600"><span class="text-korie-teal">✔</span> <span>Send money across Niger & Nigeria</span></li>
                        <li class="flex items-start space-x-3 font-medium text-slate-600"><span class="text-korie-teal">✔</span> <span>Join Adashi savings pools</span></li>
                        <li class="flex items-start space-x-3 font-medium text-slate-600"><span class="text-korie-teal">✔</span> <span>Shariah-compliant financing</span></li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full text-center bg-white border-2 border-slate-200 text-slate-700 px-6 py-4 rounded-xl font-bold hover:border-korie-green hover:text-korie-teal transition-colors mt-auto">
                        Open Account
                    </a>
                </div>
                
                <div class="bg-slate-900 rounded-[2rem] p-8 border border-slate-800 shadow-2xl shadow-slate-900/20 relative overflow-hidden flex flex-col transform lg:-translate-y-4">
                    <div class="absolute top-6 right-6 bg-amber-warning text-slate-900 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">Popular</div>
                    <h3 class="text-2xl font-black text-white mb-6 tracking-tight relative z-10">For Agents</h3>
                    <ul class="space-y-4 mb-10 flex-grow relative z-10">
                        <li class="flex items-start space-x-3 font-medium text-slate-300"><span class="text-korie-green">✔</span> <span>Earn up to 1.5% per transaction</span></li>
                        <li class="flex items-start space-x-3 font-medium text-slate-300"><span class="text-korie-green">✔</span> <span>Instant commission settlement</span></li>
                        <li class="flex items-start space-x-3 font-medium text-slate-300"><span class="text-korie-green">✔</span> <span>Earn ₦150k - ₦2M monthly</span></li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full text-center bg-gradient-to-r from-korie-green to-korie-teal text-white px-6 py-4 rounded-xl font-bold hover:shadow-lg hover:shadow-korie-green/20 transition-all active:scale-[0.98] mt-auto relative z-10">
                        Become an Agent
                    </a>
                </div>
                
                <div class="bg-slate-50 rounded-[2rem] p-8 border border-slate-200 hover:border-korie-green/50 transition-colors flex flex-col">
                    <h3 class="text-2xl font-black text-slate-900 mb-6 tracking-tight">For Aggregators</h3>
                    <ul class="space-y-4 mb-10 flex-grow">
                        <li class="flex items-start space-x-3 font-medium text-slate-600"><span class="text-korie-teal">✔</span> <span>Manage vast agent networks</span></li>
                        <li class="flex items-start space-x-3 font-medium text-slate-600"><span class="text-korie-teal">✔</span> <span>Earn 0.3%-0.7% override</span></li>
                        <li class="flex items-start space-x-3 font-medium text-slate-600"><span class="text-korie-teal">✔</span> <span>Direct KYC approval authority</span></li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full text-center bg-white border-2 border-slate-200 text-slate-700 px-6 py-4 rounded-xl font-bold hover:border-korie-green hover:text-korie-teal transition-colors mt-auto">
                        Become an Aggregator
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-slate-900 relative overflow-hidden">
        <div class="container mx-auto px-6 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-extrabold text-white mb-6 tracking-tight">Trusted across West Africa</h2>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-slate-800/80 rounded-2xl p-8 border border-slate-700">
                    <div class="text-slate-400 font-bold text-xs mb-3 uppercase tracking-wider">Transactions Today</div>
                    <div class="text-4xl font-black text-white font-mono tracking-tighter">47,293</div>
                </div>
                <div class="bg-slate-800/80 rounded-2xl p-8 border border-slate-700">
                    <div class="text-slate-400 font-bold text-xs mb-3 uppercase tracking-wider">Volume Processed</div>
                    <div class="text-4xl font-black text-white font-mono tracking-tighter">₦2.4B</div>
                </div>
                <div class="bg-slate-800/80 rounded-2xl p-8 border border-slate-700">
                    <div class="text-slate-400 font-bold text-xs mb-3 uppercase tracking-wider">Active Agents</div>
                    <div class="text-4xl font-black text-white font-mono tracking-tighter">14,837</div>
                </div>
                <div class="bg-slate-800/80 rounded-2xl p-8 border border-slate-700">
                    <div class="text-slate-400 font-bold text-xs mb-3 uppercase tracking-wider">Average Response</div>
                    <div class="text-4xl font-black text-white font-mono tracking-tighter text-korie-green">0.3s</div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-white py-8 border-t border-slate-200 text-center">
        <p class="text-slate-500 font-medium text-sm">&copy; {{ date('Y') }} KoriePay Liquidity Systems. All rights reserved.</p>
    </footer>

</body>
</html>