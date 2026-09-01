<div class="max-w-3xl mx-auto space-y-8 animate-in fade-in duration-700 pb-20">
    
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
            <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight italic">FX Order Desk</h1>
            <div class="flex items-center gap-2 mt-2">
                <span class="w-2 h-2 rounded-full bg-[#29B475] animate-pulse"></span>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Cross-Border Settlement • Active</p>
            </div>
        </div>
        <div class="text-right bg-slate-50 border border-slate-200 px-6 py-3 rounded-xl">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Market Rate (NGN/XOF)</p>
            <p class="text-lg font-black text-slate-900 font-mono tracking-tighter">1.00 : {{ number_format((float)$exchangeRate, 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-none border border-slate-200 shadow-sm relative">
        <div class="absolute top-0 left-0 w-full h-1 bg-slate-900"></div>

        <div class="p-10 md:p-14">
            @if($step == 1)
                <form wire:submit="verifyReceiver" class="space-y-8">
                    <div class="space-y-2 border-b border-slate-100 pb-8">
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest">Counterparty Identification</h2>
                        <p class="text-xs text-slate-400 font-medium">Locate the receiving entity within the Sahel Grid.</p>
                    </div>

                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Recipient Identifier</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input wire:model="receiverIdentifier" type="text" autofocus
                                   class="w-full pl-14 pr-8 py-5 bg-slate-50 border border-slate-200 rounded-none text-lg font-bold outline-none focus:border-slate-900 focus:bg-white transition-all placeholder:text-slate-300" 
                                   placeholder="Email, @Username, or Phone Number">
                        </div>
                        @error('receiverIdentifier') <p class="text-[10px] font-black text-red-500 uppercase tracking-widest">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full py-5 bg-slate-900 text-white text-xs font-black uppercase tracking-[0.2em] hover:bg-[#29B475] transition-colors">
                            Verify & Lock Counterparty
                        </button>
                    </div>
                </form>

            @else
                <div class="space-y-10 animate-in slide-in-from-right-10 duration-500">
                    
                    <div class="flex items-center justify-between p-5 bg-slate-50 border border-slate-200">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-slate-900 text-white flex items-center justify-center font-black text-xl">
                                {{ substr($receiver->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Verified Beneficiary</p>
                                <p class="text-sm font-black text-slate-900 leading-none">{{ $receiver->name }}</p>
                                <p class="text-[10px] font-bold text-[#29B475] font-mono mt-1">{{ $receiver->email ?? $receiver->username }}</p>
                            </div>
                        </div>
                        <button wire:click="$set('step', 1)" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-red-500 transition-colors border-b border-transparent hover:border-red-500">
                            Reset Order
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Base Asset (NGN)</label>
                            <div class="relative">
                                <span class="absolute left-6 top-1/2 -translate-y-1/2 text-2xl font-black text-slate-300 italic">₦</span>
                                <input wire:model.live="sourceAmount" type="number" step="100" autofocus
                                       class="w-full pl-14 pr-6 py-6 bg-white border-2 border-slate-200 text-3xl font-black font-mono text-slate-900 outline-none focus:border-slate-900 transition-all rounded-none">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Target Asset (XOF)</label>
                            <div class="w-full px-6 py-6 bg-slate-50 border-2 border-slate-100 text-3xl font-black font-mono text-[#29B475] flex justify-between items-center rounded-none">
                                <span>{{ number_format((float)$destinationAmount, 0) }}</span>
                                <span class="text-sm text-slate-400 uppercase">XOF</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-8">
                        <h3 class="text-[10px] font-black text-slate-900 uppercase tracking-widest mb-4">Trade Breakdown</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-5 bg-slate-50 border border-slate-200 flex justify-between items-center">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Spread (3%)</span>
                                <span class="text-sm font-black text-slate-900 font-mono">₦{{ number_format((float)$fee, 2) }}</span>
                            </div>
                            <div class="p-5 bg-slate-900 text-white border border-slate-900 flex justify-between items-center shadow-inner">
                                <span class="text-[10px] font-black text-[#29B475] uppercase tracking-widest">Node Profit</span>
                                <span class="text-sm font-black text-[#29B475] font-mono">+₦{{ number_format((float)$fee, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <button wire:click="executeSettlement" 
                            wire:confirm="Confirm institutional transfer? ₦{{ number_format((float)$sourceAmount, 2) }} will be debited from float."
                            class="w-full py-6 bg-[#29B475] text-slate-900 text-xs font-black uppercase tracking-[0.2em] hover:bg-emerald-400 transition-all active:scale-95 flex justify-center items-center gap-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Execute Order & Settle Liquidity
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>