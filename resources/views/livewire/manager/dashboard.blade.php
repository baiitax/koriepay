<div class="p-4 lg:p-8 max-w-[1600px] mx-auto space-y-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-4 mb-2">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-slate-900 leading-tight uppercase italic tracking-tighter">Command Center</h1>
            <p class="text-slate-500 font-bold text-sm flex items-center gap-2 mt-1 uppercase tracking-[0.2em] text-[10px]">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                {{ $countryName }} Grid // Regional Uplink Active
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="loadRegionalData" class="p-3 bg-white border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition-all active:scale-95 group">
                <svg wire:loading.class="animate-spin" class="w-5 h-5 text-slate-400 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
            <div class="bg-slate-900 px-5 py-3 rounded-2xl shadow-xl flex items-center gap-4 border border-slate-800">
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Base Asset</span>
                <span class="text-emerald-400 font-mono font-black text-xs">{{ $currency }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        <div class="bg-slate-900 p-8 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden group">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-emerald-500 rounded-full blur-[80px] opacity-20 group-hover:opacity-40 transition-opacity"></div>
            <p class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4">Total Network Liquidity</p>
            <h2 class="text-3xl font-black tracking-tighter font-mono">
                @if($currency === 'NGN') ₦ @endif{{ number_format($totalLiquidity, 2) }}
            </h2>
            <div class="mt-8 flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-emerald-400/80">
                <span class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Live Pool</span>
                <span class="text-slate-500 italic">Regional Treasury</span>
            </div>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm flex flex-col justify-between group hover:border-orange-200 transition-all">
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Frozen / Locked Assets</p>
                <h2 class="text-3xl font-black text-slate-900 tracking-tighter font-mono {{ $frozenLiquidity > 0 ? 'text-orange-600' : '' }}">
                    @if($currency === 'NGN') ₦ @endif{{ number_format($frozenLiquidity, 2) }}
                </h2>
            </div>
            <div class="mt-6 flex items-center gap-2 text-[10px] font-black uppercase tracking-widest {{ $frozenLiquidity > 0 ? 'text-orange-500' : 'text-slate-400' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                {{ $frozenLiquidity > 0 ? 'Protocol Restriction Active' : 'Assets Unlocked' }}
            </div>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm flex flex-col justify-between group">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Deployed Terminals</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter font-mono">{{ number_format($activeAgents) }}</h2>
                </div>
                <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl group-hover:bg-blue-600 group-hover:text-white transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <a href="{{ route('manager.agents') }}" class="mt-6 text-[10px] uppercase tracking-widest font-black text-blue-600 flex items-center gap-1 group-hover:translate-x-1 transition-transform" wire:navigate>
                Access Fleet Directory &rarr;
            </a>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm flex flex-col justify-between {{ $pendingKyc > 0 ? 'ring-2 ring-red-500/10 border-red-100' : '' }}">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">KYC Review Queue</p>
                    <h2 class="text-3xl font-black {{ $pendingKyc > 0 ? 'text-red-500' : 'text-slate-900' }} tracking-tighter font-mono">{{ $pendingKyc }}</h2>
                </div>
                <div class="p-3 {{ $pendingKyc > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }} rounded-2xl transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.95 11.95 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
            @if($pendingKyc > 0)
                <a href="{{ route('manager.compliance') }}" class="mt-6 text-[10px] uppercase tracking-widest font-black text-red-600 flex items-center gap-1 animate-pulse" wire:navigate>
                    Pending Approvals &rarr;
                </a>
            @else
                <span class="mt-6 text-[10px] uppercase tracking-widest font-black text-emerald-500">Compliance Clear</span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-slate-900 rounded-[2.5rem] p-8 shadow-2xl flex flex-col sm:flex-row items-center justify-between border border-slate-800 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] bg-blend-overlay">
            <div class="mb-4 sm:mb-0">
                <p class="text-[9px] font-black text-emerald-400 uppercase tracking-[0.3em] mb-2">24H Velocity Protocol</p>
                <div class="flex items-end gap-3">
                    <h2 class="text-4xl font-black text-white font-mono tracking-tighter">@if($currency === 'NGN') ₦ @endif{{ number_format($todayVolume) }}</h2>
                </div>
            </div>
            <div class="flex gap-12 border-t sm:border-t-0 sm:border-l border-slate-700 pt-6 sm:pt-0 sm:pl-12 w-full sm:w-auto">
                <div>
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Success Rate</p>
                    <p class="text-2xl font-black font-mono {{ $successRate < 95 ? 'text-orange-400' : 'text-emerald-400' }}">{{ $successRate }}%</p>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Today Tx</p>
                    <p class="text-2xl font-black font-mono text-white">{{ number_format($todayTxCount) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm p-8 flex flex-col justify-center">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 italic">Gateway Connectivity</p>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-tight">Core Ledger API</span>
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase rounded-lg border border-emerald-100">Live</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-tight">Regional Bank Nodes</span>
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase rounded-lg border border-emerald-100">Optimal</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-tight">Security Handshake</span>
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase rounded-lg border border-emerald-100">Encrypted</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-black text-slate-900 uppercase tracking-tighter italic">Regional Activity Feed</h3>
                <a href="{{ route('manager.activity-feed') }}" class="text-[9px] font-black uppercase tracking-widest text-emerald-600 hover:text-emerald-700 transition-colors" wire:navigate>View All Signals</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap">
                    <thead class="bg-white text-slate-400 text-[9px] uppercase font-black tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-8 py-5">Uplink Ref</th>
                            <th class="px-8 py-5">Node Identity</th>
                            <th class="px-8 py-5 text-right">Throughput</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentTransactions as $trx)
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-8 py-4">
                                <p class="font-mono text-xs font-black text-slate-700 uppercase tracking-tighter">{{ $trx->reference }}</p>
                                <p class="text-[9px] text-slate-400 font-bold uppercase">{{ $trx->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-8 py-4">
                                <p class="font-black text-slate-900 text-xs uppercase">{{ $trx->user?->name ?? 'System' }}</p>
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-[8px] font-black uppercase rounded-md tracking-tighter">{{ $trx->type }}</span>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <p class="font-black font-mono text-sm text-slate-900">{{ number_format($trx->amount) }}</p>
                                <p class="text-[9px] font-black text-slate-400 uppercase">{{ $currency }}</p>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="px-8 py-12 text-center text-slate-400 font-bold italic">No regional signals captured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-8 py-6 border-b border-slate-100 bg-slate-950 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    <h3 class="text-xs font-black text-white uppercase tracking-widest italic">Security Pulse</h3>
                </div>
                <a href="{{ route('manager.audit-logs') }}" wire:navigate class="text-[9px] font-black text-emerald-400 uppercase tracking-widest">Full Audit &rarr;</a>
            </div>

            <div class="flex-1 divide-y divide-slate-50">
                @forelse($recentAudits as $log)
                    <div class="px-8 py-5 hover:bg-slate-50/50 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-4">
                            <div class="w-1.5 h-1.5 rounded-full {{ $log->action === 'FREEZE' ? 'bg-red-500 animate-ping' : ($log->action === 'FUNDING' ? 'bg-emerald-500' : 'bg-blue-500') }}"></div>
                            <div>
                                <p class="text-[10px] font-mono font-black text-slate-900 uppercase tracking-tighter">{{ $log->action }} / Node-{{ $log->target_id }}</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">{{ $log->targetAgent->name ?? 'Operator' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-black text-slate-300 uppercase italic">{{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <div class="py-20 text-center text-slate-300 font-black uppercase text-[10px] tracking-widest italic">No security overrides.</div>
                @endforelse
            </div>
            
            <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.3em]">End of Transmission</p>
            </div>
        </div>
    </div>
</div>