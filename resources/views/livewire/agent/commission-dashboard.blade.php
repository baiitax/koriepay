<div class="max-w-4xl mx-auto space-y-10 animate-in fade-in duration-700 pb-20">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-slate-950 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden group border border-slate-800">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl group-hover:bg-emerald-500/20 transition-all"></div>
            <p class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.4em] mb-4">Earnings Vault</p>
            <h2 class="text-5xl font-black font-mono tracking-tighter">₦{{ number_format($wallet->commission_balance, 2) }}</h2>
        </div>

        <div class="bg-white rounded-[3rem] p-10 border border-slate-100 shadow-xl relative overflow-hidden group">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl"></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.4em] mb-4">Liquidity Float</p>
            <h2 class="text-5xl font-black font-mono tracking-tighter text-slate-900">₦{{ number_format($wallet->balance, 2) }}</h2>
        </div>
    </div>

    <div class="bg-white rounded-[3.5rem] shadow-[0_40px_80px_-15px_rgba(0,0,0,0.05)] border border-slate-50 overflow-hidden">
        <div class="px-12 py-8 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Paystack Identity Verified Settlement</h3>
            </div>
            <img src="https://upload.wikimedia.org/wikipedia/commons/b/b1/Paystack_Logo.png" class="h-4 opacity-50 grayscale hover:grayscale-0 transition-all" alt="Paystack Secure">
        </div>

        <div class="p-12 space-y-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Debit Source</label>
                        <select wire:model="withdrawSource" class="w-full px-6 py-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] text-xs font-black uppercase tracking-widest outline-none focus:border-blue-500 transition-all">
                            <option value="commission">Earnings Vault (Profit)</option>
                            <option value="float">Liquidity Float (Capital)</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Settlement Amount</label>
                        <div class="relative">
                            <span class="absolute left-6 top-1/2 -translate-y-1/2 text-xl font-black text-slate-300">₦</span>
                            <input wire:model="amount" type="number" class="w-full pl-12 pr-6 py-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] text-2xl font-black font-mono outline-none focus:border-blue-500 transition-all" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Destination Bank</label>
                        <select wire:model="bankCode" wire:change="verifyAccount" class="w-full px-6 py-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] text-xs font-black uppercase tracking-widest outline-none focus:border-blue-500 transition-all">
                            <option value="">Select Local Bank...</option>
                            <option value="058">GTBank</option>
                            <option value="011">First Bank</option>
                            <option value="057">Zenith Bank</option>
                            <option value="033">United Bank for Africa</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Account Number</label>
                        <div class="relative">
                            <input wire:model.live.debounce.500ms="accountNumber" type="text" maxlength="10" 
                                   class="w-full px-6 py-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] text-2xl font-black font-mono tracking-[0.3em] outline-none {{ $isVerified ? 'border-emerald-500 bg-emerald-50/20' : 'focus:border-blue-500' }} transition-all">
                            
                            @if($isLoading)
                                <div class="absolute right-6 top-1/2 -translate-y-1/2">
                                    <svg class="animate-spin h-5 w-5 text-blue-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>
                            @elseif($isVerified)
                                <div class="absolute right-6 top-1/2 -translate-y-1/2 text-emerald-500">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($isVerified)
                <div class="p-6 bg-slate-900 rounded-[2rem] flex items-center justify-between animate-in slide-in-from-top-4 duration-500">
                    <div>
                        <p class="text-[9px] font-black text-emerald-500 uppercase tracking-[0.3em] mb-1">Beneficiary Identified</p>
                        <h4 class="text-xl font-black text-white italic tracking-tight">{{ $verifiedAccountName }}</h4>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Network Status</p>
                        <p class="text-[10px] font-bold text-emerald-500 uppercase">Identity Match 100%</p>
                    </div>
                </div>
            @endif

            <button wire:click="processBankWithdrawal" 
                    {{ !$isVerified ? 'disabled' : '' }}
                    class="w-full py-6 {{ $isVerified ? 'bg-[#29B475] shadow-emerald-200' : 'bg-slate-100 text-slate-300' }} rounded-[2rem] text-xs font-black uppercase tracking-[0.2em] shadow-2xl transition-all active:scale-95 flex items-center justify-center gap-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Authorize External Clearing
            </button>
        </div>
    </div>
</div>