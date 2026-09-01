<div x-data="{ addModal: @entangle('addModal') }" @close-modal.window="addModal = false" class="max-w-md mx-auto space-y-6 pb-24 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('customer.profile') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors active:scale-95">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Linked Vaults</h1>
    </div>

    @if (session()->has('success'))
        <div class="bg-korie-green/10 border border-korie-green/20 text-korie-green px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 mb-4 shadow-inner">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse($vaults as $vault)
            <div class="bg-gradient-to-br from-slate-800 to-slate-950 rounded-[2rem] p-6 sm:p-8 text-white relative overflow-hidden shadow-2xl group">
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-[#29B475]/20 rounded-full blur-[50px] pointer-events-none"></div>
                
                <div class="flex justify-between items-start mb-6 relative z-10">
                    <div class="w-12 h-12 bg-white/10 backdrop-blur-md rounded-xl flex items-center justify-center border border-white/20">
                        <svg class="w-6 h-6 text-[#29B475]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    
                    <button wire:click="deleteVault({{ $vault->id }})" wire:confirm="Are you sure you want to unlink this vault?" class="w-8 h-8 rounded-full bg-white/5 hover:bg-red-500/20 text-slate-400 hover:text-red-500 flex items-center justify-center transition-colors border border-transparent hover:border-red-500/30">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
                
                <div class="relative z-10">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ $vault->bank_name }}</p>
                    <p class="text-2xl font-mono font-black tracking-[0.1em]">{{ substr($vault->account_number, 0, 4) }} •••• {{ substr($vault->account_number, -2) }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <svg class="w-3 h-3 text-[#29B475]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-[#29B475]">{{ $vault->account_name }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-slate-50 border border-slate-200 border-dashed rounded-[2rem] p-10 text-center flex flex-col items-center">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-slate-300 shadow-sm mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <p class="text-sm font-black text-slate-900">No Vaults Linked</p>
                <p class="text-[10px] font-bold text-slate-500 mt-1 max-w-[200px]">Link a traditional bank account to enable off-grid withdrawals.</p>
            </div>
        @endforelse
    </div>

    <button @click="addModal = true" class="w-full py-5 bg-white border-2 border-dashed border-slate-300 text-slate-500 rounded-[2rem] text-xs font-black uppercase tracking-[0.2em] hover:bg-slate-50 hover:border-[#29B475] hover:text-[#29B475] transition-all active:scale-[0.98] flex items-center justify-center gap-3">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        Securely Link Bank
    </button>

    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
        <div x-show="addModal" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="addModal = false"></div>
        <div x-show="addModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full" x-transition:enter-end="opacity-100 translate-y-0" class="relative bg-white w-full sm:max-w-md rounded-t-[2.5rem] sm:rounded-[2.5rem] p-6 sm:p-8 shadow-2xl">
            
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Link Vault</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">AML Verification Required</p>
                </div>
                <button @click="addModal = false" class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="linkAccount" class="space-y-5">
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Financial Institution</label>
                    <select wire:model="bankCode" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:ring-[#29B475] focus:border-[#29B475] transition-all shadow-inner">
                        <option value="">Select Bank...</option>
                        <option value="058">Guaranty Trust Bank (GTB)</option>
                        <option value="044">Access Bank</option>
                        <option value="033">United Bank for Africa (UBA)</option>
                        <option value="032">Union Bank</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Account Number</label>
                    <input wire:model="accountNumber" type="number" placeholder="10-Digit Account Number" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-lg font-mono font-black tracking-widest text-slate-900 focus:ring-[#29B475] focus:border-[#29B475] transition-all shadow-inner">
                    @error('accountNumber') <span class="text-[10px] text-red-500 font-bold uppercase pl-1 mt-1 block leading-tight">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Verification Number (BVN/NIN)</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400 group-focus-within:text-[#29B475] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <input wire:model="verificationNumber" type="password" placeholder="11-Digit Identity Hash" required class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-lg font-mono font-black tracking-widest text-slate-900 focus:ring-[#29B475] focus:border-[#29B475] transition-all shadow-inner">
                    </div>
                    @error('verificationNumber') <span class="text-[10px] text-red-500 font-bold uppercase pl-1 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 flex gap-3">
                    <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[9px] font-bold text-blue-700 leading-relaxed uppercase tracking-widest">To comply with AML regulations, the name on this bank account must strictly match your registered KoriePay identity.</p>
                </div>

                <button type="submit" wire:loading.attr="disabled" class="w-full py-4 bg-[#020617] text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-slate-800 transition-all active:scale-[0.98] shadow-xl shadow-slate-900/20 flex justify-center items-center gap-2 mt-4">
                    <span wire:loading.remove wire:target="linkAccount">Verify & Connect Vault</span>
                    <span wire:loading wire:target="linkAccount">Pinging Banking Grid...</span>
                    <svg wire:loading.remove wire:target="linkAccount" class="w-4 h-4 text-[#29B475]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>