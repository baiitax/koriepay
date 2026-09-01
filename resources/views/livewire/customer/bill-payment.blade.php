<div class="max-w-2xl mx-auto space-y-6 pb-32 animate-in fade-in slide-in-from-bottom-4 duration-500 pt-8 px-4 sm:px-0">
    
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors active:scale-95">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">Pay Bills</h1>
                <p class="text-[10px] font-bold text-[#158987] uppercase tracking-[0.2em] mt-1">Everyday Services</p>
            </div>
        </div>
        <div class="bg-[#e8f6f0] px-3 py-1.5 rounded-xl border border-[#29B475]/20 text-right">
            <p class="text-[8px] font-black text-[#29B475]/60 uppercase tracking-widest">Available</p>
            <p class="text-xs font-mono font-black text-[#29B475]">₦{{ number_format($balance, 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden relative">
        <div class="absolute top-0 left-0 h-1.5 bg-gradient-to-r from-blue-500 to-indigo-500 transition-all duration-500" style="width: {{ ($step/2)*100 }}%"></div>

        @if($step === 1)
            <div class="p-6 sm:p-8 mt-2">
                
                <div class="flex gap-2 overflow-x-auto no-scrollbar pb-6 border-b border-slate-100 mb-6">
                    <button wire:click="setType('airtime')" class="flex flex-col items-center gap-2 px-6 py-4 rounded-2xl border transition-all active:scale-95 {{ $type === 'airtime' ? 'bg-blue-50 border-blue-200 text-blue-600 shadow-sm' : 'bg-white border-slate-100 text-slate-400 hover:bg-slate-50' }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="text-[9px] font-black uppercase tracking-widest">Airtime</span>
                    </button>
                    <button wire:click="setType('data')" class="flex flex-col items-center gap-2 px-6 py-4 rounded-2xl border transition-all active:scale-95 {{ $type === 'data' ? 'bg-indigo-50 border-indigo-200 text-indigo-600 shadow-sm' : 'bg-white border-slate-100 text-slate-400 hover:bg-slate-50' }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                        <span class="text-[9px] font-black uppercase tracking-widest">Data</span>
                    </button>
                    <button wire:click="setType('power')" class="flex flex-col items-center gap-2 px-6 py-4 rounded-2xl border transition-all active:scale-95 {{ $type === 'power' ? 'bg-amber-50 border-amber-200 text-amber-600 shadow-sm' : 'bg-white border-slate-100 text-slate-400 hover:bg-slate-50' }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span class="text-[9px] font-black uppercase tracking-widest">Power</span>
                    </button>
                    <button wire:click="setType('tv')" class="flex flex-col items-center gap-2 px-6 py-4 rounded-2xl border transition-all active:scale-95 {{ $type === 'tv' ? 'bg-rose-50 border-rose-200 text-rose-600 shadow-sm' : 'bg-white border-slate-100 text-slate-400 hover:bg-slate-50' }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span class="text-[9px] font-black uppercase tracking-widest">TV</span>
                    </button>
                </div>

                <div class="space-y-6">
                    <div class="space-y-2 relative">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] pl-1">
                            {{ in_array($type, ['airtime', 'data']) ? 'Phone Number' : ($type === 'power' ? 'Meter Number' : 'Smartcard Number') }}
                        </label>
                        
                        <div class="relative">
                            <input wire:model.live.debounce.300ms="identifier" type="text" inputmode="numeric" 
                                   class="w-full px-5 py-5 bg-slate-50 border border-slate-200 rounded-2xl text-base font-black tracking-widest focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-inner transition-all placeholder:text-slate-300 placeholder:tracking-normal placeholder:font-sans" 
                                   placeholder="{{ in_array($type, ['airtime', 'data']) ? '080...' : 'Enter number' }}">
                            
                            @if($provider && in_array($type, ['airtime', 'data']))
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border shadow-sm
                                    {{ $provider === 'MTN' ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : 
                                      ($provider === 'Airtel' ? 'bg-red-50 text-red-600 border-red-200' : 
                                      ($provider === 'Glo' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-100 text-slate-700 border-slate-200')) }}">
                                    {{ $provider }}
                                </div>
                            @endif
                        </div>
                        @error('identifier') <span class="text-[9px] text-red-500 font-bold uppercase pl-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($type === 'data' && $provider)
                        <div class="space-y-2 animate-in slide-in-from-top-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] pl-1">Select Data Plan</label>
                            <div class="relative">
                                <select wire:model.live="package_id" class="w-full pl-5 pr-12 py-5 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-black focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 appearance-none cursor-pointer shadow-inner">
                                    <option value="">Choose a plan...</option>
                                    @foreach($this->dataPackages as $pkg)
                                        <option value="{{ $pkg['id'] }}">{{ $pkg['name'] }} - ₦{{ number_format($pkg['price']) }}</option>
                                    @endforeach
                                </select>
                                <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(in_array($type, ['airtime', 'power']))
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] pl-1">Amount</label>
                            <div class="relative flex items-center bg-slate-50 border border-slate-200 rounded-2xl overflow-hidden focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 shadow-inner transition-all">
                                <span class="pl-5 text-xl font-black text-slate-400">₦</span>
                                <input wire:model.live.debounce.300ms="amount" type="number" inputmode="numeric" class="w-full bg-transparent border-none py-5 px-2 text-2xl font-mono font-black tracking-tighter text-slate-900 focus:ring-0 placeholder-slate-300" placeholder="0">
                            </div>
                            @error('amount') <span class="mt-1 text-[9px] text-red-500 font-bold uppercase block pl-1">{{ $message }}</span> @enderror
                        </div>

                        @if($type === 'airtime')
                            <div class="grid grid-cols-4 gap-2 pt-2">
                                @foreach([100, 200, 500, 1000] as $amt)
                                    <button wire:click="$set('amount', {{ $amt }})" class="py-2 bg-slate-50 border border-slate-200 rounded-xl text-[10px] font-black font-mono text-slate-600 hover:bg-slate-100 transition-colors active:scale-95">
                                        +{{ $amt }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    @endif

                    <button wire:click="validateStepOne" 
                            wire:loading.attr="disabled"
                            {{ !$identifier || !$amount ? 'disabled' : '' }}
                            class="w-full py-5 rounded-[2rem] text-[11px] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-2 mt-8
                            {{ !$identifier || !$amount ? 'bg-slate-100 text-slate-400 cursor-not-allowed opacity-60' : 'bg-[#020617] text-white shadow-xl hover:bg-slate-800 active:scale-[0.98]' }}">
                        <span wire:loading.remove wire:target="validateStepOne">Proceed</span>
                        <span wire:loading wire:target="validateStepOne" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Validating...
                        </span>
                    </button>
                </div>
            </div>

        @elseif($step === 2)
            <div class="p-6 sm:p-8 space-y-6 animate-in slide-in-from-right-4 mt-2">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-blue-100 text-blue-500 shadow-inner">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 tracking-tight">Authorize Payment</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Enter your 4-digit PIN</p>
                </div>

                <div class="bg-slate-50 p-6 rounded-[2rem] border border-slate-200 space-y-4 shadow-inner">
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Service</span>
                        <div class="text-right">
                            <span class="text-sm font-black text-slate-900 block capitalize">{{ $type }} Top-up</span>
                            <span class="text-[9px] text-blue-600 font-bold uppercase tracking-widest block mt-0.5">
                                {{ $provider ?? 'Service' }} - {{ $identifier }}
                            </span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Amount</span>
                        <span class="text-base font-mono font-black text-slate-900 tracking-tighter">₦{{ number_format((float)$amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Fee</span>
                        <span class="text-xs font-mono font-black {{ $fee > 0 ? 'text-orange-500' : 'text-[#29B475]' }}">
                            {{ $fee > 0 ? '₦'.number_format($fee, 2) : 'Free' }}
                        </span>
                    </div>
                </div>

                <div>
                    <input wire:model="transaction_pin" type="password" inputmode="numeric" maxlength="4" placeholder="••••" autofocus
                           class="w-full py-6 bg-slate-50 border border-slate-200 rounded-2xl text-4xl text-center font-mono font-black tracking-[0.5em] text-[#020617] focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-inner transition-all placeholder:tracking-normal placeholder:text-slate-300">
                    @error('transaction_pin') <span class="text-[10px] text-red-500 font-bold uppercase text-center block mt-3">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-3">
                    <button wire:click="$set('step', 1)" class="flex-1 py-5 bg-white border border-slate-200 text-slate-600 rounded-[1.25rem] text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all active:scale-95 shadow-sm">Back</button>
                    <button wire:click="processPayment" wire:loading.attr="disabled" class="flex-[2] py-5 bg-[#020617] text-white rounded-[1.25rem] text-[11px] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-slate-800 transition-all active:scale-95 flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="processPayment">Pay Securely</span>
                        <span wire:loading wire:target="processPayment" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>