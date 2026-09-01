<div class="max-w-3xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="absolute top-8 right-8 z-50">
        @if (session()->has('success'))
            <div class="px-5 py-4 bg-emerald-900/95 backdrop-blur-md border-2 border-[#29B475] rounded-2xl flex items-center gap-4 shadow-2xl animate-in fade-in slide-in-from-top-4 duration-300">
                <span class="text-xs font-black text-[#29B475] uppercase tracking-widest">{{ session('success') }}</span>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="px-5 py-4 bg-red-900/95 backdrop-blur-md border-2 border-red-500 rounded-2xl flex items-center gap-4 shadow-2xl animate-in fade-in slide-in-from-top-4 duration-300">
                <span class="text-xs font-black text-red-500 uppercase tracking-widest">{{ session('error') }}</span>
            </div>
        @endif
    </div>

    <div class="flex items-center gap-4 border-b border-slate-200 pb-6">
        <div class="w-16 h-16 bg-emerald-50 rounded-[1.5rem] flex items-center justify-center text-[#29B475] border border-emerald-100 shadow-sm">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
        </div>
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight uppercase italic">Customer Cash-In</h1>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Convert Physical Fiat to Digital Liquidity</p>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-8 md:p-12 transition-all">
        
        @if(!$verifiedCustomer)
            <form wire:submit="verifyCustomer" class="space-y-6">
                <div class="space-y-3">
                    <label class="flex items-center gap-2 text-[11px] font-black text-slate-400 uppercase tracking-widest">
                        <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Customer Identifier (Email, Username, or Phone)
                    </label>
                    <input wire:model="customerIdentifier" type="text" required autofocus
                           class="w-full px-6 py-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] text-lg font-bold text-slate-900 focus:bg-white focus:ring-0 focus:border-[#29B475] transition-all placeholder:text-slate-300" 
                           placeholder="e.g., john@email.com, @johndoe, 08012345678">
                    @error('customerIdentifier') <span class="text-[10px] font-black text-red-500 uppercase tracking-widest">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full px-10 py-5 bg-slate-900 text-white rounded-[1.5rem] text-sm font-black uppercase tracking-widest hover:bg-[#29B475] transition-all flex items-center justify-center gap-3">
                    <span wire:loading.remove wire:target="verifyCustomer">Verify Target Account</span>
                    <span wire:loading wire:target="verifyCustomer">Scanning Grid...</span>
                </button>
            </form>

        @else
            <form wire:submit="processCashIn" class="space-y-8 animate-in fade-in duration-300">
                
                <div class="p-6 bg-slate-50 border-2 border-emerald-500/20 rounded-[1.5rem] relative overflow-hidden">
                    <div class="absolute top-0 right-0 px-3 py-1 bg-emerald-100 text-emerald-700 text-[9px] font-black uppercase tracking-widest rounded-bl-lg">Identity Locked</div>
                    
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 bg-emerald-500 text-white rounded-xl flex items-center justify-center text-xl font-black shadow-md border-2 border-emerald-200">
                            {{ substr($verifiedCustomer->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-lg font-black text-slate-900">{{ $verifiedCustomer->name }}</p>
                            <p class="text-xs font-semibold text-slate-500 font-mono tracking-tight mt-0.5">
                                {{ $verifiedCustomer->email }} 
                                @if($verifiedCustomer->phone) • {{ $verifiedCustomer->phone }} @endif
                            </p>
                        </div>
                    </div>
                    
                    <button type="button" wire:click="resetVerification" class="mt-4 text-[10px] font-black text-red-500 uppercase tracking-widest hover:text-red-700 transition-colors">
                        &larr; Wrong Customer? Unlock & Research
                    </button>
                </div>

                <div class="space-y-3">
                    <label class="flex items-center gap-2 text-[11px] font-black text-slate-400 uppercase tracking-widest">
                        Physical Cash Received (NGN)
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                            <span class="text-2xl font-black text-slate-300">₦</span>
                        </div>
                        <input wire:model="amount" type="number" step="100" min="100" required autofocus
                               class="w-full pl-14 pr-6 py-6 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] text-3xl font-black text-slate-900 font-mono tracking-tighter focus:bg-white focus:ring-0 focus:border-[#29B475] transition-all placeholder:text-slate-200" 
                               placeholder="0.00">
                    </div>
                    @error('amount') <span class="text-[10px] font-black text-red-500 uppercase tracking-widest">{{ $message }}</span> @enderror
                </div>

                <button type="submit" 
                        wire:confirm="Confirm deposit to {{ $verifiedCustomer->name }}? This is irreversible."
                        class="w-full px-10 py-5 bg-[#29B475] text-white rounded-[1.5rem] text-sm font-black uppercase tracking-widest shadow-xl shadow-[#29B475]/30 hover:bg-emerald-600 transition-all flex items-center justify-center gap-3">
                    <span wire:loading.remove wire:target="processCashIn">Execute Digital Deposit</span>
                    <span wire:loading wire:target="processCashIn">Processing Settlement...</span>
                </button>
            </form>
        @endif

    </div>
</div>