<div class="p-4 lg:p-8 max-w-[1400px] mx-auto space-y-8">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
        <div class="flex items-center gap-6">
            <div class="w-20 h-20 rounded-3xl bg-slate-900 flex items-center justify-center text-white text-3xl font-black italic shadow-2xl border-4 border-slate-800">
                {{ substr($agent->name, 0, 1) }}
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">{{ $agent->name }}</h1>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $agent->is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-red-50 text-red-600 border-red-100' }}">
                        {{ $agent->is_active ? 'Live' : 'Frozen' }}
                    </span>
                </div>
                <p class="text-slate-500 font-bold text-sm mt-1 uppercase tracking-widest text-[10px]">
                    Terminal ID: SP-{{ str_pad($agent->id, 5, '0', STR_PAD_LEFT) }} • {{ $agent->email }}
                </p>
            </div>
        </div>

        <div class="flex gap-3 w-full md:w-auto">
            <button wire:click="toggleFreeze" wire:loading.attr="disabled" 
                class="flex-1 md:flex-none px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 {{ $agent->is_active ? 'bg-red-600 text-white hover:bg-red-700 shadow-red-500/20' : 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-emerald-500/20' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                {{ $agent->is_active ? 'Suspend Terminal' : 'Restore Terminal' }}
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="space-y-6">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Liquidity Nodes</h3>
            @foreach($wallets as $wallet)
            <div class="bg-slate-900 p-8 rounded-[2.5rem] text-white relative overflow-hidden group border border-slate-800">
                <div class="absolute -right-4 -top-4 w-32 h-32 bg-emerald-500 rounded-full blur-[60px] opacity-10 group-hover:opacity-30 transition-opacity"></div>
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">{{ $wallet->currency_code }} Asset</p>
                        <h2 class="text-4xl font-black tracking-tighter">
                            {{ number_format($wallet->balance, 2) }}
                        </h2>
                    </div>
                    <div class="bg-white/10 p-3 rounded-2xl backdrop-blur-md">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    </div>
                </div>
                <div class="mt-8 pt-6 border-t border-white/5 flex justify-between text-[9px] font-black uppercase text-slate-500 tracking-widest relative z-10">
                    <span>Last Inbound: 2h ago</span>
                    <span>Primary Settlement</span>
                </div>
            </div>
            @endforeach

            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                <h4 class="text-sm font-black text-slate-900 mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.95 11.95 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Compliance Profile
                </h4>
                <div class="space-y-4">
                    <div class="flex justify-between items-center pb-4 border-b border-slate-50">
                        <span class="text-xs font-bold text-slate-500 uppercase">KYC Level</span>
                        <span class="text-xs font-black text-slate-900">Tier 2 (Verified)</span>
                    </div>
                    <div class="flex justify-between items-center pb-4 border-b border-slate-50">
                        <span class="text-xs font-bold text-slate-500 uppercase">Identity Hash</span>
                        <span class="text-xs font-mono font-bold text-slate-400">9204...9210</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500 uppercase">Risk Rating</span>
                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[9px] font-black rounded uppercase tracking-widest">Low</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Terminal Ledger</h3>
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden min-h-[600px] flex flex-col relative">
                
                <div wire:loading.delay.block class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center">
                    <div class="loader-dot bg-emerald-500"></div><div class="loader-dot bg-emerald-500"></div><div class="loader-dot bg-emerald-500"></div>
                </div>

                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-widest border-b border-slate-100">
                            <tr>
                                <th class="px-8 py-5">Reference / Time</th>
                                <th class="px-8 py-5 text-center">Type</th>
                                <th class="px-8 py-5 text-right">Amount</th>
                                <th class="px-8 py-5 text-right">Balance After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($history as $trx)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-8 py-5">
                                    <p class="font-mono text-xs font-black text-slate-900 group-hover:text-emerald-600 transition-colors">{{ $trx->reference }}</p>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">{{ $trx->created_at->format('M d, H:i') }}</p>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[9px] font-black uppercase rounded-md">{{ $trx->type }}</span>
                                </td>
                                <td class="px-8 py-5 text-right font-black {{ $trx->direction === 'in' ? 'text-emerald-600' : 'text-slate-900' }}">
                                    {{ $trx->direction === 'in' ? '+' : '-' }}{{ number_format($trx->amount) }}
                                </td>
                                <td class="px-8 py-5 text-right text-xs font-bold text-slate-400">
                                    {{ number_format($trx->balance_after ?? 0) }} {{ $currency }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center text-slate-400 font-bold text-sm">
                                    No ledger entries found for this terminal.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($history->hasPages())
                <div class="p-6 bg-slate-50 border-t border-slate-100">
                    {{ $history->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>