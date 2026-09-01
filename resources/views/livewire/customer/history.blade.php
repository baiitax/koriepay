<div x-data="{ receiptModal: false }" 
     @open-receipt-modal.window="receiptModal = true" 
     class="max-w-2xl mx-auto pb-24 animate-in fade-in slide-in-from-bottom-4 duration-500 relative">
    
    <div class="flex items-center justify-between mb-6 sticky top-0 bg-slate-50/90 backdrop-blur-xl pt-6 pb-4 z-20 px-4 sm:px-6 border-b border-slate-200/50">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 hover:scale-105 transition-all active:scale-95">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Ledger</h1>
                <p class="text-[10px] font-black text-[#158987] uppercase tracking-[0.2em] mt-1">Transaction History</p>
            </div>
        </div>
    </div>

    <div class="flex gap-2 px-4 sm:px-6 overflow-x-auto no-scrollbar pb-4">
        <button wire:click="setFilter('all')" class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap border {{ $filter === 'all' ? 'bg-[#020617] text-white border-[#020617] shadow-lg shadow-slate-900/20' : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300 hover:bg-slate-50 active:scale-95' }}">
            All Activity
        </button>
        <button wire:click="setFilter('credit')" class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap border {{ $filter === 'credit' ? 'bg-[#e8f6f0] text-[#29B475] border-[#29B475]/30 shadow-sm' : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300 hover:bg-slate-50 active:scale-95' }}">
            <span class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                Money In
            </span>
        </button>
        <button wire:click="setFilter('debit')" class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap border {{ $filter === 'debit' ? 'bg-red-50 text-red-600 border-red-200 shadow-sm' : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300 hover:bg-slate-50 active:scale-95' }}">
            <span class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                Money Out
            </span>
        </button>
    </div>

    <div class="mx-4 sm:mx-6 bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden min-h-[50vh]">
        <div class="divide-y divide-slate-100/80">
            @forelse($transactions as $tx)
                @php
                    // FIX: Robust amount extraction to ensure figures NEVER fail to show
                    $txAmount = $tx->amount ?? ($tx->is_credit ? $tx->destination_amount : $tx->source_amount) ?? 0;
                    $txCurrency = $tx->currency ?? ($tx->is_credit ? $tx->destination_currency : $tx->source_currency) ?? '₦';
                @endphp
                
                <div wire:click="viewReceipt({{ $tx->id }})" class="p-5 sm:p-6 flex items-center justify-between hover:bg-slate-50 transition-colors cursor-pointer group active:bg-slate-100">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform duration-300 shadow-inner border border-slate-50 {{ $tx->is_credit ? 'bg-[#e8f6f0] text-[#29B475]' : 'bg-red-50 text-red-500' }}">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                @if($tx->is_credit)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                @endif
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900 truncate max-w-[160px] sm:max-w-[280px] leading-tight mb-1">{{ $tx->description }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $tx->created_at->format('M d, g:i A') }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-base sm:text-lg font-black font-mono tracking-tighter leading-none mb-1.5 {{ $tx->is_credit ? 'text-[#29B475]' : 'text-red-500' }}">
                            {{ $tx->is_credit ? '+' : '-' }}{{ $txCurrency }}{{ number_format((float) $txAmount, 2) }}
                        </p>
                        <span class="px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-widest 
                            {{ $tx->status === 'completed' ? 'bg-emerald-50 text-emerald-600' : ($tx->status === 'failed' ? 'bg-red-50 text-red-600' : 'bg-yellow-50 text-yellow-600 animate-pulse') }}">
                            {{ $tx->status }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="p-16 flex flex-col items-center justify-center text-center">
                    <div class="w-20 h-20 rounded-full bg-slate-50 flex items-center justify-center text-slate-300 mb-6 border border-slate-100 shadow-inner">
                        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <p class="text-base font-black text-slate-900 tracking-tight">No Journal Records</p>
                    <p class="text-[11px] font-bold text-slate-500 mt-2 max-w-[200px] leading-relaxed uppercase tracking-widest">No activity found for this filter.</p>
                </div>
            @endforelse
        </div>
        
        @if($transactions->hasPages())
            <div class="p-6 border-t border-slate-100 bg-slate-50">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <div x-show="receiptModal" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
        <div x-show="receiptModal" x-transition.opacity class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="receiptModal = false"></div>
        
        <div x-show="receiptModal" 
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full" x-transition:enter-end="opacity-100 translate-y-0" 
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-full" 
             class="relative w-full sm:max-w-md pt-10 px-4 pb-8 sm:p-0 z-10">
            
            @if($selectedTx)
                @php
                    $modalAmount = $selectedTx->amount ?? ($selectedTx->is_credit ? $selectedTx->destination_amount : $selectedTx->source_amount) ?? 0;
                    $modalCurrency = $selectedTx->currency ?? ($selectedTx->is_credit ? $selectedTx->destination_currency : $selectedTx->source_currency) ?? '₦';
                @endphp

                <div class="bg-white rounded-t-[2.5rem] sm:rounded-[2.5rem] shadow-2xl overflow-hidden relative" id="receipt-card">
                    
                    <button @click="receiptModal = false" class="absolute top-6 right-6 w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors z-20 active:scale-95">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    <div class="pt-10 pb-8 px-8 text-center bg-slate-50 relative">
                        <div class="w-20 h-20 rounded-full mx-auto mb-6 flex items-center justify-center shadow-lg
                            {{ $selectedTx->is_credit ? 'bg-[#29B475] text-white shadow-[#29B475]/30' : 'bg-red-500 text-white shadow-red-500/30' }}">
                            @if($selectedTx->is_credit)
                                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                            @endif
                        </div>

                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-2">Total {{ $selectedTx->is_credit ? 'Received' : 'Sent' }}</p>
                        <h2 class="text-4xl font-mono font-black tracking-tighter mb-2 {{ $selectedTx->is_credit ? 'text-[#29B475]' : 'text-red-500' }}">
                            {{ $selectedTx->is_credit ? '+' : '-' }}{{ $modalCurrency }}{{ number_format((float) $modalAmount, 2) }}
                        </h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-widest {{ $selectedTx->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($selectedTx->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-slate-200 text-slate-600') }}">
                            @if($selectedTx->status === 'completed') <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> @endif
                            {{ $selectedTx->status }}
                        </span>
                    </div>

                    <div class="relative h-6 bg-white flex items-center justify-center border-t border-slate-50">
                        <div class="absolute -left-3 w-6 h-6 bg-slate-900/40 sm:bg-slate-900/60 rounded-full shadow-inner"></div>
                        <div class="w-full border-t-2 border-dashed border-slate-200 mx-6"></div>
                        <div class="absolute -right-3 w-6 h-6 bg-slate-900/40 sm:bg-slate-900/60 rounded-full shadow-inner"></div>
                    </div>

                    <div class="px-8 pb-8 pt-4 space-y-5 bg-white">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date & Time</span>
                            <span class="text-xs font-black text-slate-900">{{ $selectedTx->created_at->format('M d, Y • H:i') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Type</span>
                            <span class="text-xs font-black text-slate-900 capitalize">{{ str_replace('_', ' ', $selectedTx->type) }}</span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Description</span>
                            <span class="text-xs font-black text-slate-900 text-right max-w-[180px] leading-tight">{{ $selectedTx->description }}</span>
                        </div>
                        @if(($selectedTx->fee ?? 0) > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Network Fee</span>
                            <span class="text-xs font-mono font-black text-slate-900">{{ $modalCurrency }}{{ number_format((float) $selectedTx->fee, 2) }}</span>
                        </div>
                        @endif

                        <div class="pt-6 mt-2 border-t border-slate-100 flex flex-col items-center justify-center">
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-[0.3em] mb-2">Reference ID</span>
                            <span class="text-sm font-mono font-black text-slate-600 tracking-[0.2em] bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-100 w-full text-center truncate">{{ $selectedTx->reference }}</span>
                        </div>
                    </div>

                    <div class="p-6 bg-slate-50 border-t border-slate-100">
                        <button onclick="shareReceipt('{{ $selectedTx->reference }}', '{{ $modalAmount }}', '{{ $modalCurrency }}')" class="w-full py-4 bg-[#020617] text-white rounded-[1.25rem] text-[10px] font-black uppercase tracking-[0.2em] hover:bg-slate-800 transition-all active:scale-[0.98] flex items-center justify-center gap-3 shadow-xl shadow-slate-900/20">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                            Share Receipt
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function shareReceipt(ref, amount, currency) {
            if (navigator.share) {
                navigator.share({
                    title: 'KoriePay Receipt',
                    text: `KoriePay Transfer Receipt\nAmount: ${currency}${amount}\nRef: ${ref}\nStatus: Completed`,
                    url: window.location.href,
                }).catch(console.error);
            } else {
                alert('Native sharing is not supported on this device. Please take a screenshot of your receipt!');
            }
        }
    </script>
</div>