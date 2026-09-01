<div x-data="{ receiptModal: false }" 
     @open-receipt-modal.window="receiptModal = true"
     class="max-w-md mx-auto space-y-6 pb-24">

    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors active:scale-95">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Transaction History</h1>
    </div>

    <div class="space-y-4 sticky top-[72px] bg-slate-50 z-20 py-2">
        <div class="relative group/search">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within/search:text-korie-paleblue transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search references..." 
                   class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-korie-paleblue/20 focus:border-korie-paleblue transition-all placeholder:text-slate-400 shadow-sm">
        </div>

        <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
            <button wire:click="setFilter('all')" class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap {{ $filter === 'all' ? 'bg-slate-900 text-white shadow-md' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-100' }}">
                All History
            </button>
            <button wire:click="setFilter('in')" class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap {{ $filter === 'in' ? 'bg-korie-green text-white shadow-md shadow-korie-green/20' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-100' }}">
                Money In ↓
            </button>
            <button wire:click="setFilter('out')" class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap {{ $filter === 'out' ? 'bg-slate-900 text-white shadow-md' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-100' }}">
                Money Out ↑
            </button>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($transactions as $tx)
                @php
                    $isMoneyIn = in_array($tx->type, ['deposit', 'receive']);
                    $iconClass = $isMoneyIn ? 'bg-korie-green/10 text-korie-green' : 'bg-slate-100 text-slate-600';
                @endphp
                <div wire:click="viewReceipt({{ $tx->id }})" class="p-4 sm:p-5 flex items-center justify-between hover:bg-slate-50 transition-colors cursor-pointer active:bg-slate-100">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $iconClass }}">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                @if($isMoneyIn)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                @endif
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ ucfirst($tx->type) }}</p>
                            <p class="text-[10px] font-mono font-bold text-slate-500 mt-0.5">{{ $tx->created_at->format('M d, Y • g:i A') }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black font-mono tracking-tighter {{ $isMoneyIn ? 'text-korie-green' : 'text-slate-900' }}">
                            {{ $isMoneyIn ? '+' : '-' }}{{ number_format($tx->amount, 2) }}
                        </p>
                        <p class="text-[9px] font-bold uppercase tracking-widest mt-0.5 {{ $tx->status === 'completed' ? 'text-slate-400' : 'text-korie-orange animate-pulse' }}">
                            {{ $tx->status }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="p-10 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center text-slate-300 mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">No matching records</p>
                </div>
            @endforelse
        </div>
        
        @if($transactions->hasPages())
            <div class="p-4 bg-slate-50 border-t border-slate-100">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <div x-show="receiptModal" x-cloak class="relative z-50">
        <div x-show="receiptModal" x-transition.opacity class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center text-center sm:items-center sm:p-0">
                <div x-show="receiptModal" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95" 
                     @click.away="receiptModal = false"
                     class="relative transform overflow-hidden bg-slate-50 text-left shadow-2xl transition-all w-full sm:my-8 sm:max-w-sm rounded-t-[2.5rem] sm:rounded-[2.5rem]">
                    
                    @if($selectedTx)
                    <div class="p-6">
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 mb-6 relative overflow-hidden">
                            <div class="text-center mb-6 border-b border-dashed border-slate-200 pb-6">
                                <div class="w-12 h-12 rounded-full mx-auto flex items-center justify-center mb-3 {{ in_array($selectedTx->type, ['deposit', 'receive']) ? 'bg-korie-green/10 text-korie-green' : 'bg-slate-100 text-slate-900' }}">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <h3 class="text-2xl font-black font-mono tracking-tighter text-slate-900">{{ number_format($selectedTx->amount, 2) }}</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Transaction Successful</p>
                            </div>

                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Type</span>
                                    <span class="text-xs font-black text-slate-900">{{ ucfirst($selectedTx->type) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date</span>
                                    <span class="text-[10px] font-mono font-bold text-slate-900">{{ $selectedTx->created_at->format('M d, Y - H:i:s') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Reference</span>
                                    <span class="text-[10px] font-mono font-black text-slate-600 bg-slate-50 px-2 py-1 rounded">{{ $selectedTx->reference ?? 'TX-'.$selectedTx->id }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button wire:click="downloadReceipt" class="flex-1 py-3.5 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition-all active:scale-[0.98] shadow-md flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download
                            </button>
                            <button @click="receiptModal = false" class="flex-1 py-3.5 bg-white border border-slate-200 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all active:scale-[0.98]">
                                Close
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>