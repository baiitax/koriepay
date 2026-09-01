<div wire:poll.10s="calculateTreasury" class="p-4 lg:p-8 max-w-[1600px] mx-auto">
    
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-slate-900 leading-tight">{{ __('Regional Treasury') }}</h1>
            <p class="text-slate-500 font-bold text-sm mt-1 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ __('Live Liquidity Reconciliation') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Last API Sync</p>
                <p class="text-xs font-bold text-slate-600 font-mono" wire:loading.class="animate-pulse text-emerald-500">{{ $lastSync }}</p>
            </div>
            <div class="bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Partner Node</span>
                <span class="flex items-center gap-2 bg-slate-50 text-slate-700 font-black px-3 py-1 rounded-lg text-xs border border-slate-100">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    {{ $bankName }}
                </span>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-700 font-bold text-sm shadow-sm transition-all animate-fade-in-down">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 mb-8">
        <div class="flex justify-between items-end mb-3">
            <div>
                <h3 class="font-black text-slate-900 text-lg">System Reserve Health</h3>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">Target: 100% Backed</p>
            </div>
            <div class="text-right">
                <span class="text-2xl font-black {{ $reserveRatio < 90 ? 'text-red-500' : ($reserveRatio < 100 ? 'text-amber-500' : 'text-emerald-500') }} transition-colors duration-500">
                    {{ $reserveRatio }}%
                </span>
            </div>
        </div>
        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden flex">
            <div class="h-full {{ $reserveRatio < 90 ? 'bg-red-500' : ($reserveRatio < 100 ? 'bg-amber-400' : 'bg-emerald-500') }} transition-all duration-1000 ease-out relative" style="width: {{ $reserveRatio }}%">
                <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-start">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Digital Liabilities') }}</p>
                    <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-slate-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-slate-500"></span></span>
                </div>
                <h2 class="text-3xl font-black mt-2 tracking-tight text-slate-900 transition-all duration-300" wire:key="liab-{{ $digitalLiability }}">
                    {{ number_format($digitalLiability) }} <span class="text-sm text-slate-400 ml-1">{{ $currency }}</span>
                </h2>
            </div>
            <div class="mt-6 flex justify-between border-t border-slate-100 pt-4">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">24H Inflow</p>
                    <p class="text-sm font-bold text-emerald-500">+{{ number_format($inflow24h) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">24H Outflow</p>
                    <p class="text-sm font-bold text-red-500">-{{ number_format($outflow24h) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-slate-900 p-6 rounded-[2rem] text-white shadow-2xl relative overflow-hidden group flex flex-col justify-between border border-slate-800">
            <div class="absolute -right-4 -top-4 w-40 h-40 bg-blue-500 rounded-full blur-[60px] opacity-20 group-hover:opacity-40 transition-opacity duration-700"></div>
            <div>
                <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest">{{ __('Fiat Reserves (Bank)') }}</p>
                <h2 class="text-3xl font-black mt-2 tracking-tight transition-all duration-300" wire:key="fiat-{{ $fiatInBank }}">
                    {{ number_format($fiatInBank) }} <span class="text-sm text-slate-400 ml-1">{{ $currency }}</span>
                </h2>
            </div>
            <div class="mt-6 border-t border-slate-800 pt-4 flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-emerald-400">
                <svg class="w-4 h-4 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Syncing with {{ $bankName }}...
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm flex flex-col justify-between transition-colors duration-500 {{ $deficit > 0 ? 'bg-red-50/30 border-red-100' : 'bg-emerald-50/30 border-emerald-100' }}">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest {{ $deficit > 0 ? 'text-red-400' : 'text-emerald-500' }}">{{ __('Current Deficit') }}</p>
                <h2 class="text-3xl font-black mt-2 transition-all duration-300 {{ $deficit > 0 ? 'text-red-600' : 'text-emerald-600' }}" wire:key="def-{{ $deficit }}">
                    {{ $deficit > 0 ? '-' : '' }}{{ number_format($deficit) }}
                </h2>
            </div>
            
            @if($deficit > 0)
                <button 
                    wire:click="requestLiquidityWire" 
                    wire:loading.attr="disabled" 
                    class="mt-6 w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none flex items-center justify-center gap-2 disabled:opacity-50 shadow-lg shadow-red-500/20 active:scale-95"
                >
                    <span wire:loading.remove wire:target="requestLiquidityWire">Request Wire Transfer</span>
                    <span wire:loading wire:target="requestLiquidityWire" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Transmitting...
                    </span>
                </button>
            @else
                <div class="mt-6 w-full py-4 bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> Liquidity Optimal
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="bg-slate-900 rounded-[2rem] p-8 shadow-xl relative overflow-hidden flex flex-col justify-between bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] bg-blend-overlay border border-slate-800 group">
            <div class="absolute inset-0 bg-gradient-to-t from-emerald-900/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="relative z-10">
                <div class="w-12 h-12 bg-emerald-500/20 rounded-2xl border border-emerald-500/30 flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-white font-black text-xl mb-1">Commission Pool</h3>
                <p class="text-slate-400 font-bold text-xs">Accumulated regional revenue share.</p>
                
                <h2 class="text-4xl font-black text-emerald-400 mt-4 tracking-tight transition-all duration-300">
                    {{ number_format($commissionPool) }} <span class="text-sm text-slate-500">{{ $currency }}</span>
                </h2>
            </div>
            
            <div class="mt-8 relative z-10">
                @if (session()->has('commission_success'))
                    <div class="p-4 bg-emerald-500/20 border border-emerald-500/30 rounded-xl text-emerald-400 font-bold text-xs text-center flex items-center justify-center gap-2 animate-fade-in-down">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Payout Complete
                    </div>
                @else
                    <div x-data="{ confirming: false }">
                        <button 
                            x-show="!confirming" 
                            @click="confirming = true"
                            {{ $commissionPool == 0 ? 'disabled' : '' }} 
                            class="w-full py-4 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            Disburse Funds
                        </button>

                        <button 
                            x-show="confirming" 
                            x-cloak
                            wire:click="disburseCommissions" 
                            @click.away="confirming = false"
                            class="w-full py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-[0_0_20px_rgba(16,185,129,0.4)] transition-all animate-pulse flex justify-center items-center gap-2">
                            <span wire:loading.remove wire:target="disburseCommissions">Confirm Payout?</span>
                            <span wire:loading wire:target="disburseCommissions">Processing Batch...</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <div>
                    <h3 class="font-black text-slate-900">{{ __('Live Payout Ledger') }}</h3>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Real-time Top Earners Routing</p>
                </div>
                <div class="flex gap-1">
                    <span class="w-1.5 h-4 bg-emerald-400 rounded-full animate-[pulse_1s_infinite]"></span>
                    <span class="w-1.5 h-6 bg-emerald-400 rounded-full animate-[pulse_1.2s_infinite]"></span>
                    <span class="w-1.5 h-3 bg-emerald-400 rounded-full animate-[pulse_0.8s_infinite]"></span>
                </div>
            </div>
            <div class="p-0 flex-1 relative">
                <div wire:loading.delay.shorter wire:target="calculateTreasury" class="absolute inset-0 bg-white/40 backdrop-blur-[1px] z-10"></div>
                
                @forelse($topEarners as $index => $agent)
                <div class="px-6 py-4 border-b border-slate-50 flex items-center justify-between hover:bg-slate-50 transition group">
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center font-black text-xs group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                            #{{ $index + 1 }}
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 text-sm">{{ $agent->name }}</p>
                            <p class="text-[10px] font-bold text-slate-400 font-mono">{{ $agent->phone_number }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-black text-slate-900 text-sm">
                            +{{ number_format($commissionPool > 0 ? ($commissionPool / ($index + 2)) : 0) }}
                        </p>
                        <p class="text-[9px] font-black text-slate-400 uppercase">{{ $currency }}</p>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center">
                    <p class="text-sm font-bold text-slate-400">No agent data available for payout.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
<style>
    @keyframes shimmer { 100% { transform: translateX(100%); } }
    @keyframes fade-in-down { 0% { opacity: 0; transform: translateY(-10px); } 100% { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-down { animation: fade-in-down 0.4s ease-out forwards; }
    .animate-spin-slow { animation: spin 3s linear infinite; }
</style>
</div>

