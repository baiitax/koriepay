<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sovereign Agent Terminal</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

    <x-vite-assets />
    @livewireStyles
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900" x-data="{ sidebarOpen: false }">
    
    <aside class="fixed inset-y-0 left-0 z-50 w-72 bg-[#0f172a] border-r border-slate-800 transition-transform duration-300 transform lg:translate-x-0 flex flex-col shadow-2xl" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="h-20 flex items-center px-8 border-b border-slate-800 bg-[#0b1120]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gradient-to-br from-[#29B475] to-[#158987] rounded-lg flex items-center justify-center shadow-lg shadow-[#29B475]/20">
                    <span class="text-white font-black text-sm italic tracking-tighter">K</span>
                </div>
                <div>
                    <span class="text-lg font-black text-white tracking-tight italic">KoriePay</span>
                    <span class="text-[9px] font-black text-[#29B475] uppercase tracking-[0.2em] block -mt-1">Agent Terminal</span>
                </div>
            </div>
        </div>

        <div class="px-8 py-6 border-b border-slate-800">
            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Assigned Outpost</p>
            <p class="text-sm font-black text-white truncate">{{ Auth::user()->name }}</p>
            <div class="flex items-center gap-2 mt-2">
                <div class="w-2 h-2 rounded-full bg-[#29B475] animate-pulse"></div>
                <span class="text-[10px] font-mono text-slate-400 tracking-widest">NODE-{{ str_pad(Auth::id(), 4, '0', STR_PAD_LEFT) }} ONLINE</span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
            
            <a href="{{ route('agent.dashboard') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('agent.dashboard') ? 'bg-[#29B475]/10 text-[#29B475]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span class="text-[11px] font-black uppercase tracking-widest">Terminal Hub</span>
            </a>

            <div class="pt-4 pb-2 px-4">
                <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">Liquidity Operations</p>
            </div>

            <a href="{{ route('agent.cash-in') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('agent.cash-in') ? 'bg-[#29B475]/10 text-[#29B475]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
                <span class="text-[11px] font-black uppercase tracking-widest">Deposit</span>
            </a>

            <a href="{{ route('agent.cash-out') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('agent.cash-out') ? 'bg-[#29B475]/10 text-[#29B475]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/></svg>
                <span class="text-[11px] font-black uppercase tracking-widest">Withdrawal</span>
            </a>

            <a href="{{ route('agent.cross-border') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('agent.cross-border') ? 'bg-slate-800 text-white border border-slate-700' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                <svg class="w-5 h-5 text-[#29B475]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                <span class="text-[11px] font-black uppercase tracking-widest">Fx Transfer</span>
            </a>

            <a href="{{ route('agent.fund-wallet') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('agent.fund-wallet') ? 'bg-[#29B475]/10 text-[#29B475]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2z"/><path d="M12 19c-4.418 0-8-1.79-8-4V5a2 2 0 012-2h12a2 2 0 012 2v10c0 2.21-3.582 4-8 4z"/></svg>
                <span class="text-[11px] font-black uppercase tracking-widest">Top-up Float</span>
            </a>
            
            <div class="pt-4 pb-2 px-4">
                <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">Audit & Reports</p>
            </div>

            <a href="{{ route('agent.ledger') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('agent.ledger') ? 'bg-[#29B475]/10 text-[#29B475]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="text-[11px] font-black uppercase tracking-widest">Ledger</span>
            </a>

            <div class="pt-4 pb-2 px-4">
                <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">Revenue & Growth</p>
            </div>

            <a href="{{ route('agent.commissions') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('agent.commissions') ? 'bg-[#29B475]/10 text-[#29B475]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="flex flex-col">
                    <span class="text-[11px] font-black uppercase tracking-widest">My Commissions</span>
                    <span class="text-[8px] font-bold text-[#29B475] uppercase tracking-widest">Profit Center</span>
                </div>
            </a>

            <div class="pt-4 pb-2 px-4 text-[9px] font-black text-slate-500 uppercase tracking-widest">Management</div>
            <a href="{{ route('agent.settings') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ request()->routeIs('agent.settings') ? 'bg-[#29B475]/10 text-[#29B475]' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c-.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543-.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543-.826-3.31 2.37-2.37z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-[11px] font-black uppercase tracking-widest">Settings</span>
            </a>
        </nav>
        
        <div class="p-4 border-t border-slate-800">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-red-500/10 hover:text-red-500 transition-all group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span class="text-[10px] font-black uppercase tracking-widest">Secure Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-72 min-h-screen flex flex-col">
        
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 sticky top-0 z-40 shadow-sm">
            
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 text-slate-400 hover:text-slate-600 bg-slate-50 rounded-xl border border-slate-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>

            <div class="hidden lg:flex flex-1 max-w-md relative">
                <input type="text" placeholder="Lookup Transaction Ref (e.g. KP-NET-8F2X)..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#29B475]/20 focus:border-[#29B475] transition-all outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-4 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div class="flex items-center gap-6">
                
                @php
                    // DYNAMIC DATABASE QUERY: Fetch the agent's primary NGN wallet float
                    $agentWallet = \App\Models\Wallet::where('user_id', Auth::id())
                                                    ->where('currency_code', 'NGN')
                                                    ->first();
                @endphp

                <div class="hidden md:flex items-center gap-3 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Active Float (NGN)</p>
                        <p class="text-sm font-black text-slate-900 font-mono tracking-tighter">
                            ₦{{ $agentWallet ? number_format($agentWallet->balance, 2) : '0.00' }}
                        </p>
                    </div>
                </div>

                <div class="h-8 w-px bg-slate-200 hidden md:block"></div>

                <div class="flex items-center gap-3 cursor-pointer group">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-black text-slate-900 tracking-tight group-hover:text-[#29B475] transition-colors">{{ Auth::user()->name }}</p>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Tier {{ Auth::user()->kyc_tier }} Agent</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-sm font-black shadow-md border-2 border-transparent group-hover:border-[#29B475] transition-all">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                </div>
            </div>

        </header>

        <main class="flex-1 p-8 overflow-y-auto">
            {{ $slot }}
        </main>
        
    </div>

    @livewireScripts
</body>
</html>