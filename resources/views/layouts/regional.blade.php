<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'KoriePay') }} - Regional Command</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 selection:bg-[#29B475] selection:text-white" x-data="{ sidebarOpen: false }">
    
    <div class="h-screen flex overflow-hidden">
        
        <div x-show="sidebarOpen" 
             x-transition.opacity.duration.300ms
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm md:hidden" 
             style="display: none;"></div>

             <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 flex flex-col transition-transform duration-300 ease-in-out md:relative md:translate-x-0">
            
            <div class="h-16 flex items-center justify-between px-6 border-b border-slate-100 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-[#29B475] to-[#158987] rounded-lg flex items-center justify-center shadow-md shadow-[#29B475]/20">
                        <span class="text-white font-black text-sm italic tracking-tighter">K</span>
                    </div>
                    <span class="text-lg font-black text-slate-900 tracking-tight italic">KoriePay <span class="text-slate-400 font-medium not-italic text-sm">Regional</span></span>
                </div>
                <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-8 overflow-y-auto custom-scrollbar">
                
                <div class="space-y-1.5">
                    <p class="px-3 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Command Center</p>
                    
                    <a href="{{ route('regional.dashboard') }}" class="{{ request()->routeIs('regional.dashboard') ? 'bg-[#e8f6f0] text-[#29B475] font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium' }} flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all text-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        Territory Overview
                    </a>
                </div>

                <div class="space-y-1.5">
                    <p class="px-3 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Field Operations</p>
                    
                    <a href="{{ route('regional.capture') }}" class="{{ request()->routeIs('regional.capture') ? 'bg-[#e8f6f0] text-[#29B475] font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium' }} flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all text-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Capture Agent
                    </a>

                    <a href="{{ route('regional.kyc') }}" class="{{ request()->routeIs('regional.kyc') ? 'bg-[#e8f6f0] text-[#29B475] font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium' }} flex items-center justify-between px-3 py-2.5 rounded-xl transition-all text-sm">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            KYC Pipeline
                        </div>
                        <span class="bg-amber-100 text-amber-700 text-[10px] font-black px-2 py-0.5 rounded-full">18</span>
                    </a>

                    <a href="#" class="text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all text-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Agent Directory
                    </a>
                </div>

                <div class="space-y-1.5">
                    <p class="px-3 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Analytics</p>
                    
                    <a href="#" class="text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all text-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Regional Liquidity
                    </a>

                    <a href="#" class="text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all text-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Commission Reports
                    </a>
                </div>

                <div class="space-y-1.5">
                    <p class="px-3 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">System</p>
                    
                    <a href="#" class="text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all text-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Profile & Settings
                    </a>
                </div>

            </nav>

            <div class="p-4 border-t border-slate-100 flex-shrink-0 bg-white z-10">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2.5 text-sm font-bold text-slate-700 bg-slate-100 hover:bg-red-50 hover:text-red-600 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Secure Logout
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 bg-slate-50">
            
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-10 shadow-sm shadow-slate-100/50">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-slate-500 hover:text-slate-900 focus:outline-none p-2 -ml-2 rounded-lg hover:bg-slate-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    
                    <div class="hidden md:block text-sm font-medium text-slate-500">
                        {{ \Carbon\Carbon::now()->format('l, F j, Y') }}
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button class="relative p-2 text-slate-400 hover:text-[#29B475] transition-colors rounded-full hover:bg-[#e8f6f0]">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
                    </button>

                    <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
                        <div class="hidden sm:block text-right">
                            <p class="text-sm font-bold text-slate-900 leading-none">{{ auth()->user()->name ?? 'Agent' }}</p>
                            <p class="text-[10px] font-bold text-[#158987] uppercase tracking-wider mt-1">Regional Lead</p>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-slate-900 flex items-center justify-center text-white text-sm font-bold shadow-md">
                            {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto">
                <div class="w-full mx-auto relative z-0">
                    {{ $slot }}
                </div>
            </main>
            
        </div>
    </div>
</body>
</html>