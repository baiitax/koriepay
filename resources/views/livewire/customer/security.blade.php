<div class="max-w-2xl mx-auto py-8 px-4 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Security & PINs</h1>
            <p class="text-[9px] font-bold text-[#158987] uppercase tracking-widest">KoriePay Protection Center</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 bg-[#e8f6f0] border border-[#29B475]/20 text-[#29B475] px-4 py-3 rounded-xl text-xs font-bold shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden p-8">
        
        <div class="flex items-start gap-4 mb-8 border-b border-slate-100 pb-6">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-500 shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div>
                <h3 class="font-black text-slate-900">Transaction PIN</h3>
                <p class="text-xs font-bold text-slate-500 mt-1 leading-relaxed">
                    A 4-digit security code is required to authorize all outbound transfers from your liquidity vaults.
                </p>
                
                @if($hasPin)
                    <div class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#e8f6f0] text-[#29B475] rounded-md text-[9px] font-black uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#29B475] animate-pulse"></span>
                        Active & Protected
                    </div>
                @endif
            </div>
        </div>

        @if(!$hasPin)
            <form wire:submit.prevent="setPin" class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Create 4-Digit PIN</label>
                        <input wire:model="pin" type="password" inputmode="numeric" maxlength="4" class="w-full text-center px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-2xl font-black font-mono tracking-[0.5em] text-[#158987] focus:ring-[#158987] shadow-inner" placeholder="••••">
                        <x-input-error :messages="$errors->get('pin')" class="mt-1 pl-1 text-[9px] text-red-500 font-bold uppercase" />
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Confirm PIN</label>
                        <input wire:model="pin_confirmation" type="password" inputmode="numeric" maxlength="4" class="w-full text-center px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-2xl font-black font-mono tracking-[0.5em] text-[#158987] focus:ring-[#158987] shadow-inner" placeholder="••••">
                    </div>
                </div>

                <button type="submit" wire:loading.attr="disabled" class="w-full py-4 bg-[#020617] text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-slate-800 transition-all shadow-xl shadow-slate-900/20">
                    <span wire:loading.remove wire:target="setPin">Activate Security PIN</span>
                    <span wire:loading wire:target="setPin">Encrypting...</span>
                </button>
            </form>
        @else
            <div class="text-center py-6 bg-slate-50 rounded-2xl border border-slate-100">
                <p class="text-xs font-bold text-slate-500">Your PIN is set. You can now execute secure transfers.</p>
                <button class="mt-4 text-[10px] font-black text-[#158987] uppercase tracking-widest hover:text-[#29B475] transition-colors">
                    Change PIN
                </button>
            </div>
        @endif

    </div>
</div>