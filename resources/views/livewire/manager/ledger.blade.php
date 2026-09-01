<div class="p-4 lg:p-8 max-w-[1600px] mx-auto">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-slate-900 leading-tight">{{ __('Master Ledger') }}</h1>
            <p class="text-slate-500 font-bold text-sm mt-1">{{ __('Immutable record of all regional liquidity movement.') }}</p>
        </div>
        <button class="px-6 py-2.5 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition shadow-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Export to CSV
        </button>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by TxRef or Agent Name..." class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
        </div>
        <div class="w-full md:w-64">
            <select wire:model.live="statusFilter" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500 transition-all cursor-pointer">
                <option value="">All Statuses</option>
                <option value="completed">Completed</option>
                <option value="pending">Pending Processing</option>
                <option value="failed">Failed / Disputed</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto relative">
            <div wire:loading.delay.block class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center">
                <div class="loader-dot bg-emerald-500"></div><div class="loader-dot bg-emerald-500"></div><div class="loader-dot bg-emerald-500"></div>
            </div>

            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-400 text-[9px] uppercase font-black tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Reference & Time</th>
                        <th class="px-6 py-4">Terminal / Agent</th>
                        <th class="px-6 py-4">Gross Amount</th>
                        <th class="px-6 py-4">Net (Fee)</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($transactions as $trx)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-mono text-xs font-black text-slate-900">{{ $trx->reference }}</p>
                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">{{ $trx->created_at->format('M d, Y - H:i:s') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-900 text-sm">{{ $trx->user?->name ?? 'System Route' }}</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase">{{ $trx->type }}</p>
                        </td>
                        <td class="px-6 py-4 font-black text-slate-900 text-sm">
                            {{ number_format($trx->amount, 2) }} <span class="text-[9px] text-slate-400">{{ $trx->currency }}</span>
                        </td>
                        <td class="px-6 py-4 font-bold text-slate-600 text-xs">
                            {{ number_format($trx->amount - $trx->fee, 2) }} 
                            <span class="text-[9px] text-red-400 ml-1">(-{{ number_format($trx->fee, 2) }})</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($trx->status === 'completed')
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase rounded-md border border-emerald-100 flex inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Success</span>
                            @elseif($trx->status === 'failed')
                                <span class="px-2.5 py-1 bg-red-50 text-red-600 text-[9px] font-black uppercase rounded-md border border-red-100 flex inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Failed</span>
                            @else
                                <span class="px-2.5 py-1 bg-amber-50 text-amber-600 text-[9px] font-black uppercase rounded-md border border-amber-100 flex inline-flex items-center gap-1"><svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="text-[10px] font-black uppercase tracking-widest text-emerald-600 hover:text-emerald-700 transition-colors bg-emerald-50 px-3 py-1.5 rounded-lg">Inspect</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <p class="text-sm font-bold text-slate-400">No transactions match your search criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>