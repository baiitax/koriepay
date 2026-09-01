<div class="max-w-4xl mx-auto space-y-10 animate-in fade-in duration-700 pb-20">

    <div class="fixed top-8 right-8 z-50 space-y-4">
        @if (session()->has('success'))
            <div class="px-6 py-4 bg-slate-900 border-l-4 border-[#29B475] shadow-2xl text-white text-xs font-black uppercase tracking-widest animate-in slide-in-from-right-10">
                <span class="text-[#29B475] mr-2">✓</span> {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="px-6 py-4 bg-slate-900 border-l-4 border-red-500 shadow-2xl text-white text-xs font-black uppercase tracking-widest animate-in slide-in-from-right-10">
                <span class="text-red-500 mr-2">⚠</span> {{ session('error') }}
            </div>
        @endif
    </div>

    <div class="flex items-center justify-between border-b border-slate-200 pb-6 px-2">
        <div>
            <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight italic">Liquidity Inflow</h1>
            <div class="flex items-center gap-2 mt-2">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Capital Injection Desk • Securitized</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <button wire:click="$set('corridor', 'NGN')" class="p-6 border-2 transition-all text-left {{ $corridor === 'NGN' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-400 hover:border-slate-300' }}">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-lg font-black uppercase italic">NGN Corridor</h3>
                @if($corridor === 'NGN') <span class="w-2 h-2 bg-[#29B475] rounded-full animate-pulse"></span> @endif
            </div>
            <p class="text-[9px] font-bold uppercase tracking-widest opacity-70">Nigeria • Instant Paystack Clearing</p>
        </button>

        <button wire:click="$set('corridor', 'XOF')" class="p-6 border-2 transition-all text-left {{ $corridor === 'XOF' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-slate-50 text-slate-400 hover:border-slate-300' }}">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-lg font-black uppercase italic">XOF Corridor</h3>
                @if($corridor === 'XOF') <span class="w-2 h-2 bg-[#29B475] rounded-full animate-pulse"></span> @endif
            </div>
            <p class="text-[9px] font-bold uppercase tracking-widest opacity-70">Niger • Institutional Wire Transfer</p>
        </button>
    </div>

    <div class="bg-white border border-slate-200 shadow-sm relative">
        <div class="absolute top-0 left-0 w-full h-1 {{ $corridor === 'NGN' ? 'bg-[#29B475]' : 'bg-blue-600' }}"></div>

        <form wire:submit="initiateFunding" class="p-10 md:p-14 space-y-10">
            
            <div class="space-y-3">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Injection Amount ({{ $corridor }})</label>
                <div class="relative">
                    <span class="absolute left-6 top-1/2 -translate-y-1/2 text-2xl font-black text-slate-300 italic">{{ $corridor === 'NGN' ? '₦' : 'CFA' }}</span>
                    <input wire:model="amount" type="number" step="100" class="w-full pl-20 pr-6 py-6 bg-slate-50 border-2 border-slate-100 text-4xl font-black font-mono text-slate-900 outline-none focus:border-slate-900 transition-all rounded-none" placeholder="0.00">
                </div>
                @error('amount') <p class="text-[10px] font-black text-red-500 uppercase tracking-widest mt-2">{{ $message }}</p> @enderror
            </div>

            @if($corridor === 'NGN')
                <div class="p-6 bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Clearing Partner</p>
                        <p class="text-sm font-black text-slate-900 uppercase italic">Paystack Digital Gateway</p>
                    </div>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b1/Paystack_Logo.png" class="h-4 opacity-50 grayscale" alt="Paystack">
                </div>
            @endif

            @if($corridor === 'XOF')
                <div class="space-y-8 animate-in slide-in-from-right-10 duration-300 border-t border-slate-100 pt-8">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Select Target Institution</label>
                        <select wire:model="selectedBank" class="w-full px-6 py-5 bg-slate-50 border-2 border-slate-100 text-sm font-black uppercase outline-none focus:border-slate-900 transition-all rounded-none">
                            <option value="">-- Choose Corporate Bank --</option>
                            @foreach($nigerBanks as $code => $bank)
                                <option value="{{ $code }}">{{ $bank['name'] }} (Account: {{ $bank['account'] }})</option>
                            @endforeach
                        </select>
                        @error('selectedBank') <p class="text-[10px] font-black text-red-500 uppercase tracking-widest">{{ $message }}</p> @enderror
                    </div>

                    @if($selectedBank)
                        <div class="p-6 bg-blue-50 border border-blue-200 space-y-2">
                            <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest">Wire Transfer Instructions</p>
                            <p class="text-sm font-medium text-slate-700">Please transfer <strong>{{ number_format((float)$amount ?: 0, 2) }} XOF</strong> to <strong>{{ $nigerBanks[$selectedBank]['account'] }}</strong> at <strong>{{ $nigerBanks[$selectedBank]['name'] }}</strong>. Include your Agent ID in the narration.</p>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Cryptographic Proof (Receipt)</label>
                            <input wire:model="receipt" type="file" accept="image/*" class="w-full px-6 py-4 bg-slate-50 border-2 border-slate-100 text-xs font-bold text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-none file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-slate-900 file:text-white hover:file:bg-slate-800 transition-all cursor-pointer">
                            @error('receipt') <p class="text-[10px] font-black text-red-500 uppercase tracking-widest">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>
            @endif

            <div class="pt-6 border-t border-slate-100">
                <button type="submit" class="w-full py-6 bg-slate-900 text-white text-xs font-black uppercase tracking-[0.2em] hover:bg-slate-800 transition-all active:scale-95 flex justify-center items-center gap-3">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    {{ $corridor === 'NGN' ? 'Authorize Paystack Injection' : 'Log Institutional Wire' }}
                </button>
            </div>
        </form>
    </div>
</div>