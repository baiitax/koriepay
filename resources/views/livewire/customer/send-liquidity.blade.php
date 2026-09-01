<div class="max-w-2xl mx-auto space-y-6 pb-24 animate-in fade-in slide-in-from-bottom-4 duration-500 px-4 sm:px-0 pt-8">
    
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors active:scale-95">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">Send to KoriePay</h1>
                <p class="text-[10px] font-bold text-[#158987] uppercase tracking-[0.2em] mt-1">Instant Transfer • Step {{ min($step, 2) }} of 2</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden relative">
        <div class="absolute top-0 left-0 h-1.5 bg-gradient-to-r from-[#158987] to-[#29B475] transition-all duration-500" style="width: {{ $step === 1 ? '50%' : '100%' }}"></div>

        @if($step === 1)
            <div class="p-6 sm:p-8 space-y-8 mt-2">
                
                <div class="space-y-4">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1 mb-2">1. Who are you sending to?</h3>
                    
                    <div class="relative group">
                        <div class="absolute left-5 top-1/2 -translate-y-1/2 text-[#158987] font-black italic text-lg">@</div>
                        
                        <input wire:model.live.debounce.500ms="recipient_identifier" type="text" 
                               class="w-full pl-12 pr-12 py-5 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-black focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] shadow-inner transition-all placeholder:text-slate-400 placeholder:font-bold" 
                               placeholder="Email, Phone, or KorieTag">
                        
                        <div wire:loading wire:target="recipient_identifier" class="absolute right-5 top-1/2 -translate-y-1/2">
                            <svg class="w-5 h-5 text-[#158987] animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                    </div>

                    @if($recipient)
                        <div wire:loading.remove wire:target="recipient_identifier" class="p-4 bg-[#e8f6f0] rounded-2xl border border-[#29B475]/20 flex items-center justify-between animate-in slide-in-from-top-2 shadow-sm">
                            <div class="flex items-center gap-4 overflow-hidden">
                                <div class="w-12 h-12 rounded-full bg-[#020617] text-white flex items-center justify-center font-black text-lg shrink-0 shadow-md border-2 border-white overflow-hidden">
                                    @if($recipient->profile_photo_path)
                                        <img src="{{ asset('storage/' . $recipient->profile_photo_path) }}" class="w-full h-full object-cover">
                                    @else
                                        {{ substr($recipient->name, 0, 1) }}
                                    @endif
                                </div>
                                <div class="truncate">
                                    <p class="text-[9px] font-black text-[#29B475] uppercase tracking-widest flex items-center gap-1 mb-0.5">
                                        Verified User
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </p>
                                    <p class="text-sm font-black text-slate-900 truncate">{{ $recipient->name }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @error('recipient_identifier') 
                        <span wire:loading.remove wire:target="recipient_identifier" class="text-[9px] text-red-500 font-bold uppercase pl-1 block">{{ $message }}</span> 
                    @enderror
                </div>

                @if($recipient)
                    <div class="space-y-4 pt-6 border-t border-slate-100 animate-in slide-in-from-top-4 duration-300">
                        <div class="flex justify-between items-end mb-2">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">2. Enter Amount</h3>
                            <span class="text-[9px] font-black text-[#158987] bg-[#158987]/10 px-2 py-1 rounded-lg uppercase tracking-widest">
                                Bal: {{ number_format($balance, 2) }}
                            </span>
                        </div>

                        <div class="flex gap-3">
                            <div class="w-[35%] sm:w-[30%] relative">
                                <select wire:model.live="from_currency" class="w-full pl-4 pr-8 py-5 bg-slate-50 border border-slate-200 rounded-2xl text-xs sm:text-sm font-black focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] transition-colors appearance-none cursor-pointer shadow-inner">
                                    <option value="NGN">🇳🇬 NGN</option>
                                    <option value="XOF">🇳🇪 XOF</option>
                                </select>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            <div class="w-[65%] sm:w-[70%] relative">
                                <input wire:model.live.debounce.300ms="amount" type="number" inputmode="numeric" class="w-full pl-5 pr-4 py-5 bg-slate-50 border border-slate-200 rounded-2xl text-xl font-mono font-black focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] shadow-inner transition-colors placeholder:text-slate-300" placeholder="0.00">
                            </div>
                        </div>

                        @error('amount')
                            <div class="p-3 bg-red-50 border border-red-100 rounded-xl flex items-start gap-3 animate-in shake duration-300">
                                <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span class="text-[10px] font-bold text-red-600 leading-tight">{{ $message }}</span>
                            </div>
                        @enderror
                    </div>
                @endif

                @if($recipient && $amount && !$errors->has('amount'))
                    <div class="bg-[#020617] rounded-[2rem] p-6 mt-8 shadow-xl text-white animate-in slide-in-from-top-4 duration-300 border border-slate-800">
                        <div class="flex justify-between items-center mb-4 border-b border-white/10 pb-4">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Recipient Gets</span>
                            <select wire:model.live="to_currency" class="bg-white/10 border border-white/20 rounded-xl text-[10px] font-black uppercase text-white px-4 py-2 focus:ring-0 cursor-pointer appearance-none outline-none text-right hover:bg-white/20 transition-colors">
                                <option value="NGN" class="bg-slate-900">🇳🇬 NGN</option>
                                <option value="XOF" class="bg-slate-900">🇳🇪 XOF</option>
                            </select>
                        </div>

                        <div class="flex justify-between items-end mb-3">
                            <div>
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Final Credit</span>
                                <span class="text-2xl font-mono font-black text-[#29B475] leading-none tracking-tighter">
                                    {{ $to_currency === 'NGN' ? '₦' : 'CFA' }} {{ number_format((float)$amount * $exchange_rate, 2) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Network Fee</span>
                                <span class="text-xs font-mono font-black {{ $fee > 0 ? 'text-orange-400' : 'text-white' }}">
                                    {{ $fee > 0 ? $from_currency . ' ' . number_format($fee, 2) : 'Free' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                <button wire:click="validateStepOne" 
                        wire:loading.attr="disabled"
                        {{ !$recipient || !$amount || $errors->has('amount') ? 'disabled' : '' }}
                        class="w-full py-5 rounded-[2rem] text-[11px] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3 mt-6
                        {{ !$recipient || !$amount || $errors->has('amount') ? 'bg-slate-100 text-slate-400 cursor-not-allowed opacity-50' : 'bg-[#158987] text-white shadow-xl shadow-[#158987]/30 hover:bg-[#11706e] active:scale-[0.98]' }}">
                    <span wire:loading.remove wire:target="validateStepOne">Review Transfer</span>
                    <span wire:loading wire:target="validateStepOne" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Verifying...
                    </span>
                </button>
            </div>

        @elseif($step === 2)
            <div class="p-6 sm:p-8 space-y-6 animate-in slide-in-from-right-4 mt-2">
                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-orange-100 text-orange-500 shadow-inner">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 tracking-tight">Enter PIN</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Authorize Transfer</p>
                </div>

                <div class="bg-slate-50 p-6 rounded-[2rem] border border-slate-200 space-y-4 shadow-inner">
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sending To</span>
                        <div class="text-right">
                            <span class="text-sm font-black text-slate-900 block truncate max-w-[160px]">{{ $recipient->name }}</span>
                            <span class="text-[9px] text-[#158987] font-bold uppercase tracking-widest block mt-0.5">
                                {{ $recipient->username ? '@' . $recipient->username : $recipient->phone_number }}
                            </span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Deduction</span>
                        <span class="text-base font-mono font-black text-slate-900 tracking-tighter">-{{ $from_currency }} {{ number_format((float)$amount + $fee, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Recipient Gets</span>
                        <span class="text-base font-mono font-black text-[#29B475] tracking-tighter">+{{ $to_currency }} {{ number_format((float)$amount * $exchange_rate, 2) }}</span>
                    </div>
                </div>

                @if(auth()->user()->pin_locked_until && now()->lt(auth()->user()->pin_locked_until))
                    <div class="p-4 bg-red-50 text-red-600 rounded-2xl text-center text-[10px] font-black uppercase tracking-widest border border-red-100 animate-in zoom-in">
                        PIN Locked for {{ now()->diffInMinutes(auth()->user()->pin_locked_until) }}m
                    </div>
                @else
                    <div>
                        <input wire:model="transaction_pin" type="password" inputmode="numeric" maxlength="4" placeholder="••••" autofocus
                               class="w-full py-6 bg-slate-50 border border-slate-200 rounded-2xl text-4xl text-center font-mono font-black tracking-[0.5em] text-[#158987] focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] shadow-inner transition-all placeholder:text-slate-300 placeholder:tracking-normal">
                        @error('transaction_pin') <span class="text-[10px] text-red-500 font-bold uppercase text-center block mt-3">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="$set('step', 1)" class="flex-1 py-5 bg-white border border-slate-200 text-slate-600 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-colors active:scale-95 shadow-sm">Back</button>
                        <button wire:click="processTransfer" wire:loading.attr="disabled" class="flex-[2] py-5 bg-[#020617] text-white rounded-2xl text-[11px] font-black uppercase tracking-[0.2em] shadow-xl shadow-slate-900/20 hover:bg-slate-800 transition-all active:scale-[0.98] flex justify-center items-center gap-2">
                            <span wire:loading.remove wire:target="processTransfer">Send Money</span>
                            <span wire:loading wire:target="processTransfer" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>