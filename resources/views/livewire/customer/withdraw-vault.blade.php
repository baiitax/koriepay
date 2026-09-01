<div class="max-w-2xl mx-auto space-y-6 pb-32 animate-in fade-in slide-in-from-bottom-4 duration-500 pt-8 px-4 sm:px-0">
    
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors active:scale-95">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">Send to Bank</h1>
                <div class="flex items-center gap-1.5 mt-1.5">
                    <div class="w-2 h-2 rounded-full animate-pulse {{ $currency === 'NGN' ? 'bg-blue-500' : 'bg-[#158987]' }}"></div>
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">
                        Secured via {{ $currency === 'NGN' ? 'NIBSS' : 'Regional Gateway' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden relative">
        <div class="absolute top-0 left-0 h-1.5 bg-gradient-to-r from-slate-800 to-[#020617] transition-all duration-500" style="width: {{ ($step/2)*100 }}%"></div>

        @if($step === 1)
            <div class="p-6 sm:p-8 space-y-8 mt-2">
                
                <div class="space-y-4">
                    <div class="flex justify-between items-end mb-2 border-b border-slate-100 pb-2">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">1. Transfer Value</h3>
                        <span class="text-[9px] font-bold text-[#158987] bg-[#158987]/10 px-2 py-1 rounded-lg uppercase">
                            Bal: {{ number_format($balance, 2) }}
                        </span>
                    </div>

                    <div class="flex gap-3">
                        <div class="w-[35%] sm:w-[30%] relative">
                            <select wire:model.live="currency" class="w-full pl-4 pr-8 py-5 bg-slate-50 border border-slate-200 rounded-2xl text-xs sm:text-sm font-black focus:ring-2 focus:ring-[#020617]/20 focus:border-[#020617] transition-colors appearance-none cursor-pointer shadow-inner">
                                <option value="NGN">🇳🇬 NGN</option>
                                <option value="XOF">🇳🇪 XOF</option>
                            </select>
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <div class="w-[65%] sm:w-[70%] relative">
                            <input wire:model.live.debounce.300ms="amount" type="number" inputmode="numeric" class="w-full pl-5 pr-4 py-5 bg-slate-50 border border-slate-200 rounded-2xl text-xl font-mono font-black focus:ring-2 focus:ring-[#020617]/20 focus:border-[#020617] shadow-inner transition-colors placeholder:text-slate-300" placeholder="0.00">
                        </div>
                    </div>

                    @if($amount && ($amount + $fee) > $balance)
                        <div class="p-3 bg-red-50 border border-red-100 rounded-xl flex items-center gap-3 animate-in shake duration-300">
                            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span class="text-[9px] font-bold text-red-500 uppercase tracking-widest leading-none">Insufficient funds (Includes {{ $fee }} fee)</span>
                        </div>
                    @endif
                </div>

                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1 mb-2">2. Destination Network</h3>
                    
                    <div class="relative">
                        <select wire:model.live="bank_code" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold focus:ring-2 focus:ring-[#020617]/20 focus:border-[#020617] transition-colors appearance-none cursor-pointer shadow-inner">
                            <option value="">Select {{ $currency === 'NGN' ? 'Bank' : 'Mobile Money Operator' }}...</option>
                            @foreach($available_banks as $bank)
                                <option value="{{ $bank['code'] }}">{{ $bank['name'] }}</option>
                            @endforeach
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <div class="relative group">
                        <input wire:model.live.debounce.500ms="account_number" type="text" inputmode="numeric" maxlength="10" 
                               class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-base font-bold tracking-[0.3em] focus:ring-2 focus:ring-[#020617]/20 focus:border-[#020617] shadow-inner transition-colors placeholder:tracking-normal placeholder:font-sans placeholder:text-slate-300" 
                               placeholder="10-digit Account Number">
                        
                        <div wire:loading wire:target="account_number" class="absolute right-5 top-1/2 -translate-y-1/2">
                            <svg class="w-5 h-5 text-[#020617] animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                    </div>

                    @if($resolved_account_name)
                        <div class="p-4 bg-[#e8f6f0] border border-[#29B475]/20 rounded-2xl flex items-center justify-between animate-in zoom-in-95 duration-200 shadow-sm">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-10 h-10 rounded-full bg-[#29B475] text-white flex items-center justify-center shrink-0 shadow-md shadow-[#29B475]/20">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="truncate">
                                    <p class="text-[9px] font-black text-[#29B475] uppercase tracking-widest">Matched Identity</p>
                                    <p class="text-xs font-black text-slate-900 truncate mt-0.5">{{ $resolved_account_name }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @error('account_number') 
                        <div class="p-3 bg-red-50 border border-red-100 rounded-xl flex items-center gap-2 animate-in slide-in-from-top-2">
                            <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span class="text-[10px] text-red-600 font-bold uppercase">{{ $message }}</span> 
                        </div>
                    @enderror
                </div>

                <button wire:click="validateStepOne" 
                        wire:loading.attr="disabled"
                        {{ !$resolved_account_name || ($amount + $fee) > $balance || !$amount ? 'disabled' : '' }}
                        class="w-full py-5 rounded-[2rem] text-[11px] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-2 mt-8
                        {{ !$resolved_account_name || ($amount + $fee) > $balance || !$amount ? 'bg-slate-100 text-slate-400 cursor-not-allowed opacity-60' : 'bg-[#020617] text-white shadow-xl shadow-slate-900/20 hover:bg-slate-800 active:scale-[0.98]' }}">
                    <span wire:loading.remove wire:target="validateStepOne">Proceed to Review</span>
                    <span wire:loading wire:target="validateStepOne" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Securing...
                    </span>
                </button>
            </div>

        @elseif($step === 2)
            <div class="p-6 sm:p-8 space-y-6 animate-in slide-in-from-right-4 mt-2">
                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-orange-100 text-orange-500 shadow-inner">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 tracking-tight">Authorize Transfer</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Enter your 4-digit PIN</p>
                </div>

                <div class="bg-[#020617] rounded-[2rem] p-6 text-white space-y-4 shadow-xl relative overflow-hidden">
                    <div class="absolute -right-5 -top-5 w-24 h-24 bg-[#158987]/20 rounded-full blur-2xl pointer-events-none"></div>
                    
                    <div class="flex justify-between items-start border-b border-white/10 pb-4 relative z-10">
                        <div>
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Destination</p>
                            <p class="text-sm font-black truncate max-w-[180px]">{{ $resolved_account_name }}</p>
                            <p class="text-[9px] font-bold text-[#29B475] uppercase mt-0.5 tracking-widest">{{ $bank_name }} - {{ $account_number }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center relative z-10 pt-2">
                        <div>
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Amount to Send</p>
                            <p class="text-xl font-mono font-black text-white">{{ $currency }} {{ number_format((float)$amount, 2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Network Fee</p>
                            <p class="text-xs font-mono font-black text-orange-400">{{ number_format($fee, 2) }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <input wire:model="transaction_pin" type="password" inputmode="numeric" maxlength="4" placeholder="••••" autofocus
                           class="w-full py-6 bg-slate-50 border border-slate-200 rounded-2xl text-4xl text-center font-mono font-black tracking-[0.5em] text-[#020617] focus:ring-2 focus:ring-[#020617]/20 focus:border-[#020617] shadow-inner transition-colors placeholder:tracking-normal placeholder:text-slate-300">
                    
                    @error('transaction_pin') 
                        <p class="text-center text-[10px] text-red-500 font-bold uppercase animate-bounce">{{ $message }}</p> 
                    @enderror

                    <div class="flex gap-3 pt-2">
                        <button wire:click="$set('step', 1)" class="flex-1 py-5 bg-white border border-slate-200 text-slate-600 rounded-[1.25rem] text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all active:scale-95 shadow-sm">Cancel</button>
                        <button wire:click="processWithdrawal" wire:loading.attr="disabled" class="flex-[2] py-5 bg-[#020617] text-white rounded-[1.25rem] text-[11px] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-slate-800 transition-all active:scale-95 flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="processWithdrawal">Release Funds</span>
                            <span wire:loading wire:target="processWithdrawal" class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="flex items-center justify-center gap-4 opacity-50 grayscale pt-2">
        <p class="text-[8px] font-black uppercase tracking-widest text-slate-500">Secured by</p>
        <div class="flex items-center gap-2 font-bold text-xs">
            <span class="text-slate-800">{{ $currency === 'NGN' ? 'Paystack' : 'DusuPay' }}</span> | <span class="text-slate-800">PCI DSS</span>
        </div>
    </div>
</div>