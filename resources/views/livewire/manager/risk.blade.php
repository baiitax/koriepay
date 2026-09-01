<div class="p-4 lg:p-8 max-w-[1600px] mx-auto">
    
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-slate-900 leading-tight">{{ __('Risk & AML Radar') }}</h1>
            <p class="text-red-500 font-bold text-sm mt-1 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span> Security Overrides Active
            </p>
        </div>
    </div>

    @if (session()->has('status'))
        <div class="mb-6 p-4 bg-slate-900 border border-slate-700 rounded-xl flex items-center gap-3 text-white font-bold text-sm shadow-xl">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <div class="space-y-8">
            
            <div class="bg-red-50 rounded-[2rem] border border-red-100 p-6 shadow-sm">
                <div class="flex items-start gap-4 mb-4">
                    <div class="p-3 bg-red-100 text-red-600 rounded-2xl shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-red-900 text-lg">Emergency Terminal Lock</h3>
                        <p class="text-xs font-bold text-red-700/80 mt-1">Instantly suspend an agent's terminal. This halts all withdrawals and API access immediately.</p>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <select id="agentToFreeze" class="flex-1 px-4 py-3 bg-white border border-red-200 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-red-500">
                        <option value="">Select active agent to freeze...</option>
                        @foreach($allAgents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->email }})</option>
                        @endforeach
                    </select>
                    <button onclick="let id = document.getElementById('agentToFreeze').value; if(id) { @this.toggleAccountLock(id); }" class="px-6 py-3 bg-red-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-700 shadow-lg shadow-red-500/30 transition-all active:scale-95">
                        Execute Lock
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-black text-slate-900">{{ __('Suspended Entities') }}</h3>
                    <span class="px-2 py-1 bg-red-100 text-red-700 text-[10px] font-black rounded uppercase">{{ $frozenAgents->count() }} Frozen</span>
                </div>
                <div class="p-0">
                    @forelse($frozenAgents as $frozen)
                    <div class="p-6 border-b border-slate-50 flex items-center justify-between hover:bg-slate-50 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 text-sm">{{ $frozen->name }}</p>
                                <p class="text-[10px] font-bold text-slate-400">ID: #{{ $frozen->id }} • {{ $frozen->phone_number }}</p>
                            </div>
                        </div>
                        <button wire:click="toggleAccountLock({{ $frozen->id }})" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:border-emerald-500 hover:text-emerald-600 transition-colors">
                            Restore Access
                        </button>
                    </div>
                    @empty
                    <div class="p-12 text-center">
                        <p class="text-sm font-bold text-slate-400">All local terminals are currently in good standing.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="bg-slate-900 rounded-[2rem] border border-slate-800 shadow-2xl overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-800 bg-slate-950/50">
                <h3 class="font-black text-white">{{ __('AML / High-Value Radar') }}</h3>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Alerting on tx > {{ number_format($threshold) }} {{ $currency }}</p>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                @forelse($flaggedTransactions as $trx)
                <div class="bg-slate-800/50 border border-slate-700/50 p-4 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 group hover:bg-slate-800 transition">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                            <p class="font-mono text-[10px] font-bold text-slate-400">{{ $trx->reference }}</p>
                        </div>
                        <p class="font-black text-white text-sm">{{ $trx->user?->name ?? 'Unknown' }}</p>
                        <p class="text-[10px] font-bold text-slate-500 mt-0.5">{{ $trx->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="font-black text-orange-400 text-lg">{{ number_format($trx->amount) }} <span class="text-[10px]">{{ $trx->currency }}</span></p>
                        <button class="text-[9px] font-black uppercase tracking-widest text-slate-400 hover:text-white mt-1 border-b border-transparent hover:border-white transition-all">
                            Investigate Setup &rarr;
                        </button>
                    </div>
                </div>
                @empty
                <div class="h-full flex flex-col items-center justify-center p-12 text-center">
                    <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <p class="text-sm font-bold text-slate-400">No high-risk volume detected recently.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>