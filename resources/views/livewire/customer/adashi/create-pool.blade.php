<div class="max-w-2xl mx-auto space-y-6 pt-8 pb-24 animate-in fade-in slide-in-from-bottom-4 duration-500 px-4 sm:px-0">
    
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.adashi.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors active:scale-95">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Create Adashi Pool</h1>
                <p class="text-[10px] font-bold text-[#8B5CF6] uppercase tracking-widest mt-1">Cross-Border Wealth Engine</p>
            </div>
        </div>
    </div>

    @if (session()->has('error'))
        <div class="p-4 bg-red-50 text-red-600 rounded-2xl text-[10px] font-black uppercase tracking-widest border border-red-100 flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden relative">
        <div class="absolute top-0 left-0 h-1.5 bg-gradient-to-r from-[#8B5CF6] to-[#29B475] transition-all duration-500" style="width: {{ ($step / 3) * 100 }}%"></div>

        @if($step === 1)
            <div class="p-6 sm:p-8 space-y-6 mt-2">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Pool Name</label>
                    <input wire:model.live.debounce.300ms="name" type="text" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-black focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] shadow-inner transition-all placeholder:text-slate-300 placeholder:font-bold" 
                           placeholder="{{ $currency === 'NGN' ? 'e.g., Wuse Market Traders' : 'e.g., Niamey Grand Syndicate' }}">
                    @error('name') <span class="text-[9px] text-red-500 font-bold uppercase pl-1 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-1">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Currency</label>
                        <select wire:model.live="currency" class="w-full pl-4 pr-8 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-black focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-colors appearance-none cursor-pointer shadow-inner">
                            <option value="NGN">🇳🇬 NGN</option>
                            <option value="XOF">🇳🇪 XOF</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Contribution</label>
                        <div class="relative flex items-center bg-slate-50 border border-slate-200 rounded-2xl overflow-hidden focus-within:ring-2 focus-within:ring-[#8B5CF6]/20 focus-within:border-[#8B5CF6] shadow-inner transition-all">
                            <span class="pl-5 text-sm font-black text-slate-400">{{ $currency === 'NGN' ? '₦' : 'CFA' }}</span>
                            <input wire:model.live.debounce.300ms="contribution_amount" type="number" inputmode="numeric" 
                                   class="w-full bg-transparent border-none py-4 px-3 text-base font-mono font-black focus:ring-0 placeholder:text-slate-300" 
                                   placeholder="{{ $currency === 'NGN' ? '10000' : '5000' }}">
                        </div>
                        @error('contribution_amount') <span class="text-[9px] text-red-500 font-bold uppercase pl-1 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Frequency</label>
                        <select wire:model.live="frequency" class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-black focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] transition-colors appearance-none cursor-pointer shadow-inner">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Max Members</label>
                        <input wire:model.live="max_members" type="number" min="2" max="20" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-base font-mono font-black focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] shadow-inner transition-all">
                        @error('max_members') <span class="text-[9px] text-red-500 font-bold uppercase pl-1 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Start Date</label>
                    <input wire:model.live="start_date" type="date" min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-black text-slate-700 focus:ring-2 focus:ring-[#8B5CF6]/20 focus:border-[#8B5CF6] shadow-inner transition-all">
                    @error('start_date') <span class="text-[9px] text-red-500 font-bold uppercase pl-1 mt-1 block">{{ $message }}</span> @enderror
                </div>

                @if($contribution_amount && $max_members && !$errors->has('contribution_amount') && !$errors->has('max_members'))
                    <div class="p-6 bg-[#020617] rounded-[2rem] text-white animate-in zoom-in-95 duration-300 shadow-xl border border-slate-800">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Projected Payout</span>
                            <span class="px-2.5 py-1 bg-[#8B5CF6]/20 text-[#a78bfa] text-[8px] font-black uppercase rounded-lg border border-[#8B5CF6]/30">{{ $max_members }} Members</span>
                        </div>
                        <p class="text-3xl font-mono font-black text-[#29B475] tracking-tighter mt-1">
                            {{ $currency === 'NGN' ? '₦' : 'CFA' }} {{ number_format($expected_payout, 2) }}
                        </p>
                        <div class="mt-4 pt-4 border-t border-white/10 flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#29B475]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">Secured by KoriePay Escrow</p>
                        </div>
                    </div>
                @endif

                <button wire:click="validateStepOne" class="w-full py-5 bg-[#020617] text-white rounded-[2rem] text-[11px] font-black uppercase tracking-[0.2em] hover:bg-slate-800 transition-all shadow-xl shadow-slate-900/20 active:scale-[0.98] flex justify-center items-center gap-2 mt-6">
                    <span wire:loading.remove wire:target="validateStepOne">Review Smart Contract</span>
                    <span wire:loading wire:target="validateStepOne" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Processing...
                    </span>
                </button>
            </div>

        @elseif($step === 2)
            <div class="p-6 sm:p-8 space-y-6 animate-in slide-in-from-right-4 mt-2">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-purple-50 text-[#8B5CF6] rounded-full flex items-center justify-center mx-auto mb-4 border border-purple-100 shadow-inner">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">Verify Pool Rules</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Immutable Ledger Agreement</p>
                </div>

                <div class="bg-slate-50 rounded-[2rem] border border-slate-200 p-6 space-y-4 shadow-inner">
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pool Name</span>
                        <span class="text-xs font-black text-slate-900 truncate max-w-[150px]">{{ $name }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Contribution</span>
                        <span class="text-xs font-mono font-black text-slate-900">{{ $currency === 'NGN' ? '₦' : 'CFA' }} {{ number_format((float)$contribution_amount) }} <span class="text-[9px] text-[#8B5CF6] uppercase font-sans font-bold bg-purple-50 px-2 py-0.5 rounded ml-1">{{ ucfirst($frequency) }}</span></span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Start Date</span>
                        <span class="text-xs font-black text-slate-900">{{ \Carbon\Carbon::parse($start_date)->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Pot</span>
                        <span class="text-sm font-mono font-black text-[#29B475]">{{ $currency === 'NGN' ? '₦' : 'CFA' }} {{ number_format($expected_payout) }}</span>
                    </div>
                </div>

                <div class="flex gap-3 mt-8">
                    <button wire:click="$set('step', 1)" class="flex-1 py-5 bg-white text-slate-600 border border-slate-200 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all active:scale-95 shadow-sm">Back</button>
                    <button wire:click="deployPool" wire:loading.attr="disabled" class="flex-[2] py-5 bg-[#8B5CF6] text-white rounded-[1.5rem] text-[11px] font-black uppercase tracking-[0.2em] shadow-xl shadow-[#8B5CF6]/30 hover:bg-[#7c3aed] transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="deployPool">Launch Pool</span>
                        <span wire:loading wire:target="deployPool" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Deploying...
                        </span>
                    </button>
                </div>
            </div>

        @elseif($step === 3)
            <div class="p-8 text-center space-y-6 animate-in zoom-in-95 duration-500 mt-2">
                <div class="w-24 h-24 bg-gradient-to-br from-[#29B475] to-[#158987] text-white rounded-[2.5rem] flex items-center justify-center mx-auto shadow-2xl shadow-[#29B475]/40 rotate-3 border-4 border-white">
                    <svg class="w-10 h-10 -rotate-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                
                <div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Pool Created!</h2>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-2 px-4 leading-relaxed">Share your unique code to fill the remaining {{ $max_members - 1 }} slots before {{ \Carbon\Carbon::parse($start_date)->format('M d') }}.</p>
                </div>

                <div x-data="{ copied: false }" class="bg-[#020617] p-8 rounded-[2.5rem] border border-slate-800 shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-[#8B5CF6]/30 rounded-full blur-[40px] pointer-events-none"></div>
                    
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 relative z-10">Your Invite Code</p>
                    <div class="flex items-center justify-center gap-4 relative z-10">
                        <span class="text-4xl font-mono font-black tracking-[0.4em] text-[#29B475] drop-shadow-md">{{ $invite_code }}</span>
                    </div>
                    
                    <button @click="navigator.clipboard.writeText('{{ $invite_code }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                            class="mt-6 w-full py-4 bg-white/10 hover:bg-white/20 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border border-white/5 active:scale-95 relative z-10">
                        <span x-show="!copied">Copy Code</span>
                        <span x-show="copied" x-cloak class="text-[#29B475] flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Copied!
                        </span>
                    </button>
                </div>

                <a href="{{ route('customer.adashi.dashboard') }}" wire:navigate class="block w-full py-5 bg-slate-100 text-slate-900 hover:bg-slate-200 transition-colors rounded-[2rem] text-[10px] font-black uppercase tracking-[0.2em] shadow-sm active:scale-95 mt-4 text-center">
                    Return to Hub
                </a>
            </div>
        @endif
    </div>
</div>