<div class="space-y-6 animate-in fade-in duration-500">
    
    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 md:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight uppercase italic">Node Ledger</h1>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-1">Immutable Master Record</p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-4">
            <select wire:model.live="filterType" class="w-full sm:w-auto px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-[11px] font-black text-slate-600 uppercase tracking-widest focus:ring-0 focus:border-[#29B475] outline-none cursor-pointer">
                <option value="">All Operations</option>
                <option value="cash_in">Deposits (Cash-In)</option>
                <option value="cash_out">Dispenses (Cash-Out)</option>
            </select>

            <div class="relative w-full sm:w-64">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search Ref or Customer..." 
                       class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:ring-0 focus:border-[#29B475] transition-all outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-4 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100">
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Timestamp</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Reference</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Operation</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Customer</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right whitespace-nowrap">Gross Amount</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        
                        <td class="px-8 py-4">
                            <p class="text-[12px] font-black text-slate-900 tracking-tight">{{ $tx->created_at->format('M d, Y') }}</p>
                            <p class="text-[9px] font-bold text-slate-400 mt-0.5 font-mono">{{ $tx->created_at->format('H:i:s A') }}</p>
                        </td>

                        <td class="px-8 py-4">
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-md text-[10px] font-black font-mono tracking-widest border border-slate-200">
                                {{ $tx->reference }}
                            </span>
                        </td>

                        <td class="px-8 py-4">
                            @if($tx->type == 'cash_in')
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-[#29B475]"></div>
                                    <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Deposit</span>
                                </div>
                            @elseif($tx->type == 'cash_out')
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                                    <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Dispense</span>
                                </div>
                            @else
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-400"></div>
                                    <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Standard</span>
                                </div>
                            @endif
                        </td>

                        <td class="px-8 py-4">
                            <p class="text-[11px] font-black text-slate-900 truncate max-w-[150px]">{{ $tx->receiver_name }}</p>
                        </td>

                        <td class="px-8 py-4 text-right">
                            <p class="text-sm font-black text-slate-900 font-mono tracking-tighter">
                                {{ number_format($tx->source_amount, 2) }} <span class="text-[9px] text-slate-400 uppercase ml-1">{{ $tx->source_currency }}</span>
                            </p>
                        </td>

                        <td class="px-8 py-4 text-center">
                            @if($tx->status == 'completed')
                                <span class="px-3 py-1 bg-emerald-50 text-[#29B475] border border-emerald-100 rounded-full text-[9px] font-black uppercase tracking-widest inline-block">
                                    Cleared
                                </span>
                            @elseif($tx->status == 'pending')
                                <span class="px-3 py-1 bg-amber-50 text-amber-600 border border-amber-100 rounded-full text-[9px] font-black uppercase tracking-widest inline-block animate-pulse">
                                    Pending
                                </span>
                            @else
                                <span class="px-3 py-1 bg-red-50 text-red-500 border border-red-100 rounded-full text-[9px] font-black uppercase tracking-widest inline-block">
                                    Failed
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-8 py-20 text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-center mx-auto mb-4 text-slate-400">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <p class="text-[12px] font-black text-slate-900 tracking-tight">Ledger Empty</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">No transactions found for this node.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($transactions->hasPages())
        <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-100">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>