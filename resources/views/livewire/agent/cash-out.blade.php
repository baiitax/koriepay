<div class="max-w-2xl mx-auto space-y-8 animate-in fade-in duration-500">
    
    <div class="flex items-center gap-4 border-b border-slate-200 pb-6">
        <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight uppercase italic">Secure Cash-Out</h1>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-1">Step {{ $step }} of 3: {{ ['Search Customer', 'Set Amount', 'Verify Handshake'][$step-1] }}</p>
        </div>
    </div>

    @if (session()->has('info'))
        <div class="p-4 bg-amber-900 text-amber-100 rounded-xl font-mono text-sm border-2 border-amber-500 animate-pulse">
            {{ session('info') }}
        </div>
    @endif

    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-xl p-8 md:p-12">

        @if($step == 1)
            <form wire:submit="verifyCustomer" class="space-y-6">
                <div class="space-y-3">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Customer Identifier</label>
                    <input wire:model="customerIdentifier" type="text" class="w-full px-6 py-5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-lg font-bold outline-none focus:border-blue-500 transition-all" placeholder="Email, @Username, or Phone...">
                    @error('customerIdentifier') <span class="text-xs font-bold text-red-500 uppercase">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-blue-600 transition-all">Verify Wallet Availability</button>
            </form>

        @elseif($step == 2)
            <div class="space-y-8">
                <div class="p-6 bg-blue-50 rounded-2xl flex items-center justify-between border border-blue-100">
                    <div>
                        <p class="text-lg font-black text-slate-900">{{ $verifiedCustomer->name }}</p>
                        <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">Available: ₦{{ number_format($customerBalance, 2) }}</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Withdrawal Amount (NGN)</label>
                    <input wire:model="amount" type="number" class="w-full px-6 py-5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-3xl font-black font-mono outline-none focus:border-blue-500 transition-all">
                    @error('amount') <span class="text-xs font-bold text-red-500 uppercase">{{ $message }}</span> @enderror
                </div>

                <button wire:click="requestAuthorization" class="w-full py-5 bg-blue-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest shadow-lg shadow-blue-200">
                    Send Authorization Code to Customer
                </button>
            </div>

        @elseif($step == 3)
            <div class="space-y-8 text-center">
                <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto text-amber-500 border border-amber-100 mb-4">
                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 11V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                
                <div>
                    <h2 class="text-xl font-black text-slate-900 uppercase italic">Enter Handshake Code</h2>
                    <p class="text-xs font-semibold text-slate-500 mt-2">Ask the customer for the 6-digit code sent to their device.</p>
                </div>

                <input wire:model="authCodeInput" type="text" maxlength="6" 
                       class="w-full text-center px-6 py-6 bg-slate-50 border-2 border-slate-200 rounded-2xl text-5xl font-black tracking-[0.5em] text-slate-900 focus:border-blue-500 outline-none transition-all font-mono" 
                       placeholder="000000">
                @error('authCodeInput') <span class="text-xs font-bold text-red-500 uppercase">{{ $message }}</span> @enderror

                <div class="flex gap-4 pt-4">
                    <button wire:click="reset" class="px-8 py-5 bg-slate-100 text-slate-400 rounded-2xl text-xs font-black uppercase tracking-widest">Abort</button>
                    <button wire:click="authorizeDispense" class="flex-1 py-5 bg-[#29B475] text-white rounded-2xl text-sm font-black uppercase tracking-widest shadow-xl shadow-emerald-200">Verify & Dispense Cash</button>
                </div>
            </div>
        @endif

    </div>
</div>