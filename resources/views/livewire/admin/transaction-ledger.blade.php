<div class="space-y-6 relative">
    
    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-slate-50/50">
            <div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight uppercase italic">Global Transaction Ledger</h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Sovereign Clearing & Settlement</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <select wire:model.live="statusFilter" class="px-5 py-3 bg-white border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest outline-none focus:border-[#29B475] transition-all">
                    <option value="">All Statuses</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed / Reversed</option>
                </select>

                <select wire:model.live="pairFilter" class="px-5 py-3 bg-white border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest outline-none focus:border-[#29B475] transition-all">
                    <option value="">All Corridors</option>
                    <option value="NGN/XOF">NGN → XOF</option>
                    <option value="XOF/NGN">XOF → NGN</option>
                </select>

                <div class="relative">
                    <input wire:model.live="search" type="text" placeholder="Search Ref or Name..." class="pl-10 pr-5 py-3 bg-white border border-slate-200 rounded-xl text-[11px] font-bold text-slate-900 outline-none focus:border-[#29B475] w-64 transition-all shadow-sm">
                    <svg class="w-4 h-4 text-slate-400 absolute left-4 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-slate-100">
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Reference ID</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Beneficiary</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Route</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Principal Vector</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">State</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="px-8 py-5">
                            <span class="text-[11px] font-black text-slate-600 font-mono tracking-tighter group-hover:text-[#158987] transition-colors">{{ $tx->reference }}</span>
                            <p class="text-[9px] font-bold text-slate-400 mt-1 uppercase">{{ $tx->created_at->format('M d, H:i') }}</p>
                        </td>
                        <td class="px-8 py-5">
                            <p class="text-[11px] font-black text-slate-900 tracking-tight">{{ $tx->receiver_name }}</p>
                            <p class="text-[9px] font-bold text-slate-400 mt-1 uppercase tracking-widest">Ext. Node</p>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-[9px] font-black">{{ $tx->source_currency }}</span>
                                <svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-[9px] font-black">{{ $tx->destination_currency }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <p class="text-[13px] font-black text-slate-900">{{ number_format($tx->source_amount, 2) }} <span class="text-[9px] text-slate-400">{{ $tx->source_currency }}</span></p>
                            <p class="text-[10px] font-bold text-[#158987] mt-1 italic">→ {{ number_format($tx->destination_amount, 2) }} {{ $tx->destination_currency }}</p>
                        </td>
                        <td class="px-8 py-5 text-center">
                            @if($tx->status == 'completed')
                                <span class="px-3 py-1 bg-emerald-50 text-[#29B475] border border-emerald-100 rounded-full text-[9px] font-black uppercase tracking-widest">Settled</span>
                            @elseif($tx->status == 'pending')
                                <span class="px-3 py-1 bg-amber-50 text-amber-600 border border-amber-100 rounded-full text-[9px] font-black uppercase tracking-widest">Pending</span>
                            @else
                                <span class="px-3 py-1 bg-red-50 text-red-600 border border-red-100 rounded-full text-[9px] font-black uppercase tracking-widest">Failed</span>
                            @endif
                        </td>
                        <td class="px-8 py-5 text-right">
                            <button wire:click="inspectTransaction({{ $tx->id }})" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-[#158987] transition-all shadow-md">
                                Inspect
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-8 py-20 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            No transactions detected in the current filter scope.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-100">
            {{ $transactions->links() }}
        </div>
    </div>

    @if($showInspector && $selectedTx)
    <div class="fixed inset-0 z-[100] flex justify-end">
        <div wire:click="closeInspector" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
        
        <div class="relative w-full max-w-md bg-white h-full shadow-2xl flex flex-col animate-in slide-in-from-right duration-300">
            <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest italic">Tx Telemetry</h3>
                    <p class="text-[10px] font-bold text-slate-400 font-mono mt-1">{{ $selectedTx->reference }}</p>
                </div>
                <button wire:click="closeInspector" class="p-2 text-slate-400 hover:bg-white hover:text-red-500 rounded-xl transition-all">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-8 space-y-8">
                
                <div class="flex items-center justify-between p-5 bg-slate-50 border border-slate-100 rounded-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full {{ $selectedTx->status == 'completed' ? 'bg-[#29B475]/20 text-[#29B475]' : 'bg-amber-500/20 text-amber-500' }} flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span class="text-xs font-black text-slate-900 uppercase tracking-widest">{{ $selectedTx->status }}</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400">{{ $selectedTx->created_at->format('Y-m-d H:i:s') }}</span>
                </div>

                <div class="space-y-4">
                    <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Execution Math</h4>
                    <div class="grid grid-cols-2 gap-px bg-slate-100 border border-slate-100 rounded-2xl overflow-hidden">
                        <div class="bg-white p-4">
                            <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Source Block</p>
                            <p class="text-lg font-black text-slate-900">{{ number_format($selectedTx->source_amount, 2) }} <span class="text-[10px] text-slate-400">{{ $selectedTx->source_currency }}</span></p>
                        </div>
                        <div class="bg-white p-4">
                            <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Settlement Block</p>
                            <p class="text-lg font-black text-[#158987]">{{ number_format($selectedTx->destination_amount, 2) }} <span class="text-[10px] text-slate-400">{{ $selectedTx->destination_currency }}</span></p>
                        </div>
                        <div class="bg-white p-4 col-span-2">
                            <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Locked Exchange Rate</p>
                            <p class="text-sm font-black text-slate-900 font-mono">1 {{ $selectedTx->source_currency }} = {{ $selectedTx->exchange_rate }} {{ $selectedTx->destination_currency }}</p>
                        </div>
                    </div>
                </div>

                <div class="pt-8 border-t border-slate-100">
                    <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Sovereign Overrides</h4>
                    <div class="space-y-3">
                        <button class="w-full py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-[#158987] transition-all">Download Receipt PDF</button>
                        <button class="w-full py-4 bg-white border-2 border-red-50 text-red-500 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-red-50 transition-all">Force Reversal (Rollback)</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>