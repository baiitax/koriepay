<div class="max-w-2xl mx-auto py-8 px-4 space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 hover:scale-105 transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Add Money</h1>
                <p class="text-[10px] font-black text-[#158987] uppercase tracking-[0.2em] mt-1">Fund your balance</p>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="bg-[#e8f6f0] border border-[#29B475]/30 text-[#29B475] px-4 py-3 rounded-2xl text-xs font-black shadow-sm flex items-center gap-3 animate-in slide-in-from-top-2">
            <div class="w-6 h-6 rounded-full bg-[#29B475] text-white flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex p-1.5 bg-slate-100 rounded-[1.5rem] shadow-inner">
        <button wire:click="$set('activeTab', 'bank')" class="flex-1 py-3.5 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-2 {{ $activeTab === 'bank' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-700' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M3 21v-4m22 4v-4m-3.1-2h3.2c.5 0 .9-.4.9-.9v-1.2c0-.5-.4-.9-.9-.9h-3.2c-.5 0-.9.4-.9.9v1.2c0 .5.4.9.9.9zm-10.9 0h3.2c.5 0 .9-.4.9-.9v-1.2c0-.5-.4-.9-.9-.9h-3.2c-.5 0-.9.4-.9.9v1.2c0 .5.4.9.9.9zm-10.9 0h3.2c.5 0 .9-.4.9-.9v-1.2c0-.5-.4-.9-.9-.9h-3.2c-.5 0-.9.4-.9.9v1.2c0 .5.4.9.9.9zM12 3L2 8h20L12 3z"/></svg>
            Bank Transfer
        </button>
        <button wire:click="$set('activeTab', 'card')" class="flex-1 py-3.5 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-2 {{ $activeTab === 'card' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-700' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Debit Card
        </button>
    </div>

    <div x-show="$wire.activeTab === 'bank'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
        
        <div class="bg-[#020617] rounded-[2.5rem] p-8 text-white relative overflow-hidden shadow-2xl border border-slate-800">
            <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-[#158987]/30 rounded-full blur-[80px] pointer-events-none"></div>

            <div class="relative z-10 flex items-start justify-between mb-8">
                <div>
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/60 mb-1">Your KoriePay Bank</h3>
                    <p class="text-xl font-black text-white">Providus Bank</p>
                </div>
                <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg text-[8px] font-black uppercase tracking-widest border border-emerald-500/30">Recommended</span>
            </div>

            <div x-data="{ copied: false }" class="relative z-10 space-y-6">
                <div class="bg-white/5 border border-white/10 rounded-2xl p-5 hover:bg-white/10 transition-colors cursor-pointer" 
                     @click="navigator.clipboard.writeText('{{ ltrim($user->phone_number, '0') }}'); copied = true; setTimeout(() => copied = false, 2000)">
                    <p class="text-[9px] font-bold uppercase tracking-[0.2em] text-white/40 mb-1">Account Number</p>
                    <div class="flex items-center justify-between">
                        <p class="text-3xl font-mono font-black tracking-widest text-white">{{ ltrim($user->phone_number, '0') }}</p>
                        
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 text-white">
                            <span x-show="!copied"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></span>
                            <span x-show="copied" x-cloak><svg class="w-5 h-5 text-[#29B475]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg></span>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-[9px] font-bold uppercase tracking-[0.2em] text-white/40 mb-1">Account Name</p>
                    <p class="text-sm font-black text-white uppercase tracking-wide">KORIEPAY - {{ $user->name }}</p>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-[2rem] p-6 space-y-4">
            <h4 class="text-xs font-black text-slate-900 uppercase tracking-widest">How it works</h4>
            <ul class="space-y-3">
                <li class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-white border border-slate-200 flex items-center justify-center text-[10px] font-black text-slate-500 shrink-0">1</div>
                    <p class="text-xs font-bold text-slate-600 leading-relaxed">Open your bank app (GTBank, Zenith, OPay, etc.)</p>
                </li>
                <li class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-white border border-slate-200 flex items-center justify-center text-[10px] font-black text-slate-500 shrink-0">2</div>
                    <p class="text-xs font-bold text-slate-600 leading-relaxed">Transfer money to the <span class="text-slate-900 font-black">Providus Bank</span> account above.</p>
                </li>
                <li class="flex items-start gap-3">
                    <div class="w-6 h-6 rounded-full bg-white border border-slate-200 flex items-center justify-center text-[10px] font-black text-slate-500 shrink-0">3</div>
                    <p class="text-xs font-bold text-slate-600 leading-relaxed">Your KoriePay balance will update <span class="text-[#29B475] font-black">instantly</span>. No extra fees.</p>
                </li>
            </ul>
        </div>
    </div>

    <div x-show="$wire.activeTab === 'card'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
        
        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl p-6 sm:p-8">
            <form wire:submit.prevent="initiateCardPayment" class="space-y-6">
                
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] pl-1">Amount to Fund</label>
                    <div class="relative flex items-center">
                        <span class="absolute left-6 text-2xl font-black text-slate-400">₦</span>
                        <input wire:model="amount" type="number" inputmode="numeric" min="100" class="w-full pl-14 pr-6 py-6 bg-slate-50 border border-slate-200 rounded-3xl text-3xl font-black font-mono tracking-tighter text-slate-900 focus:ring-4 focus:ring-[#158987]/20 focus:border-[#158987] transition-all shadow-inner placeholder-slate-300" placeholder="0.00">
                    </div>
                    <x-input-error :messages="$errors->get('amount')" class="mt-1 pl-1 text-[9px] text-red-500 font-bold uppercase" />
                </div>

                <div class="grid grid-cols-4 gap-2">
                    @foreach($quickAmounts as $quickAmount)
                        <button type="button" wire:click="setAmount({{ $quickAmount }})" class="py-3 bg-slate-50 border border-slate-200 rounded-xl text-[10px] font-black font-mono text-slate-600 hover:bg-slate-100 hover:border-slate-300 transition-colors active:scale-95">
                            +{{ number_format($quickAmount) }}
                        </button>
                    @endforeach
                </div>

                <div class="bg-[#e8f6f0] border border-[#29B475]/20 p-4 rounded-2xl flex items-start gap-3">
                    <svg class="w-5 h-5 text-[#29B475] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[10px] font-bold text-[#29B475] leading-relaxed">
                        A 1.5% payment gateway processing fee may be applied by the card network. For free deposits, use the Bank Transfer option.
                    </p>
                </div>

                <button type="submit" wire:loading.attr="disabled" class="w-full py-5 bg-[#158987] text-white rounded-[2rem] text-[11px] font-black uppercase tracking-[0.2em] shadow-xl shadow-[#158987]/30 hover:bg-[#11706e] transition-all flex items-center justify-center gap-3 active:scale-[0.98]">
                    <span wire:loading.remove wire:target="initiateCardPayment">Proceed to Payment</span>
                    <span wire:loading wire:target="initiateCardPayment" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Initializing...
                    </span>
                </button>
            </form>
        </div>

        <div class="flex items-center justify-center gap-4 opacity-50 grayscale">
            <p class="text-[8px] font-black uppercase tracking-widest text-slate-500">Secured by</p>
            <div class="flex items-center gap-2 font-bold text-xs">
                <span class="text-slate-800">Paystack</span> | <span class="text-slate-800">PCI DSS</span>
            </div>
        </div>
    </div>
</div>