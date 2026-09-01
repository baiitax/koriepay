<div x-data="{ addBankPanel: false }" class="space-y-6 relative">
    
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 bg-white p-6 md:p-8 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 w-1.5 h-full bg-[#29B475]"></div>
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-black text-slate-900 tracking-tight leading-none uppercase italic">Settlement Bank Nodes</h2>
                <span class="px-2 py-0.5 bg-[#e8f6f0] text-[#29B475] text-[9px] font-black rounded-md border border-[#29B475]/10 tracking-widest uppercase">Encrypted</span>
            </div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#29B475] animate-pulse"></span>
                Real-time Liquidity Propagation Active
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <button wire:click="toggleSecurity" class="flex items-center gap-2 px-4 py-3 rounded-2xl border border-slate-200 {{ $isLocked ? 'text-slate-400' : 'text-[#29B475] bg-[#f0fdf4] border-[#29B475]/20' }} transition-all group">
                <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    @if($isLocked)
                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    @else
                        <path d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    @endif
                </svg>
                <span class="text-[10px] font-black uppercase tracking-widest">{{ $isLocked ? 'Unlock Balances' : 'Secure View' }}</span>
            </button>

            <button @click="addBankPanel = true" class="px-6 py-3 bg-[#020617] text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-[#158987] transition-all shadow-xl shadow-slate-900/10 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M12 4v16m8-8H4"/></svg>
                Register Bank Node
            </button>
        </div>
    </div>

    @if (session('notify'))
        <div class="p-4 bg-slate-900 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl flex items-center gap-3 animate-in slide-in-from-top duration-500">
            <span class="w-2 h-2 rounded-full bg-[#29B475] animate-ping"></span>
            {{ session('notify') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($bankNodes as $index => $node)
        <div class="bg-white rounded-[2.5rem] border border-slate-200 overflow-hidden group hover:border-[#29B475]/30 hover:shadow-xl hover:shadow-slate-200/50 transition-all duration-500">
            
            <div class="p-6 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-2xl bg-white shadow-sm border border-slate-200 flex items-center justify-center font-black text-xs text-[#158987] transform group-hover:rotate-6 transition-transform">
                        {{ substr($node['bank_name'], 0, 1) }}
                    </div>
                    <div>
                        <h3 class="text-[13px] font-black text-slate-900 leading-none tracking-tight">{{ $node['bank_name'] }}</h3>
                        <p class="text-[9px] font-bold text-slate-400 mt-1.5 uppercase tracking-widest italic">{{ $node['type'] }} NODE</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="w-2 h-2 rounded-full {{ $node['api_status'] == 'online' ? 'bg-[#29B475]' : 'bg-amber-500' }} ml-auto"></div>
                    <p class="text-[8px] font-black text-slate-400 uppercase mt-1 tracking-tighter">{{ $node['api_status'] }}</p>
                </div>
            </div>

            <div class="p-8 text-center bg-white relative">
                <div class="space-y-1 mb-8">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em]">Available Liquidity</p>
                    <div class="relative inline-block py-2">
                        <h4 class="text-3xl font-black text-slate-900 tracking-tighter transition-all duration-700 {{ $isLocked ? 'blur-xl opacity-10 select-none' : '' }}">
                            {{ $node['currency'] == 'USD' ? '$' : ($node['currency'] == 'NGN' ? '₦' : '') }}
                            {{ number_format($node['balance'], 2) }}
                            <small class="text-xs text-slate-400 ml-1">{{ $node['currency'] == 'XOF' ? 'CFA' : '' }}</small>
                        </h4>
                        @if($isLocked)
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-[8px] font-black text-slate-300 uppercase tracking-[0.5em] animate-pulse">Encrypted</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-px bg-slate-100 border border-slate-100 rounded-2xl overflow-hidden mb-6">
                    <div class="bg-white p-3 text-left">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-tighter">Account No.</p>
                        <p class="text-[11px] font-bold text-slate-700 tracking-tighter">{{ $node['account_no'] }}</p>
                    </div>
                    <div class="bg-white p-3 text-left">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-tighter">Swift / Sort</p>
                        <p class="text-[11px] font-bold text-slate-700 tracking-tighter">ZENI-NG-22</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button wire:click="syncNode({{ $index }})" class="py-3 bg-white border border-slate-200 rounded-xl text-[9px] font-black uppercase text-slate-600 hover:border-[#29B475] hover:text-[#29B475] transition-all flex items-center justify-center gap-2 group/btn">
                        <svg class="w-3.5 h-3.5 group-hover/btn:rotate-180 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh Node
                    </button>
                    <button class="py-3 bg-slate-900 rounded-xl text-[9px] font-black uppercase text-white hover:bg-[#158987] transition-all shadow-md shadow-slate-200">
                        Node Logs
                    </button>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50/80 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-bold text-slate-400 uppercase italic">Last Handshake: {{ $node['last_sync'] }}</span>
                </div>
                <div class="flex items-center gap-1">
                    @for($i=0; $i<5; $i++)
                        <div class="w-1 h-3 rounded-full {{ $i < 4 ? 'bg-[#29B475]' : 'bg-slate-200' }}"></div>
                    @endfor
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div x-show="addBankPanel" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-[-20px_0_50px_rgba(0,0,0,0.1)] z-[100] flex flex-col" x-cloak>
        
        <div class="h-24 px-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest italic">Provision New Node</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter mt-1">Establishing Banking Uplink</p>
            </div>
            <button @click="addBankPanel = false" class="p-2 hover:bg-white rounded-xl text-slate-400 hover:text-red-500 transition-all">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-8 space-y-6">
            <div class="space-y-4">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Institution Name</label>
                    <input type="text" placeholder="e.g. United Bank for Africa" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-900 focus:ring-4 focus:ring-[#29B475]/10 focus:border-[#29B475] outline-none transition-all">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Currency</label>
                        <select class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-900 outline-none">
                            <option>NGN</option>
                            <option>XOF</option>
                            <option>USD</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Account Type</label>
                        <select class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-900 outline-none">
                            <option>Settlement</option>
                            <option>Operational</option>
                            <option>Reserve</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Corporate Account Number</label>
                    <input type="text" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-900 outline-none">
                </div>

                <div class="p-5 bg-[#f0fdf4] rounded-[2rem] border border-[#29B475]/10 mt-10">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#29B475] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-[10px] font-bold text-[#158987] leading-relaxed tracking-tight uppercase">
                            Provisioning a new bank node will trigger an automated API handshake to verify account metadata. Ensure the Sort Code is accurate.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-8 border-t border-slate-100 bg-white">
            <button @click="addBankPanel = false" class="w-full py-5 bg-[#020617] text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-[2rem] hover:bg-[#158987] transition-all shadow-2xl shadow-slate-900/20">
                Establish Node Uplink
            </button>
        </div>
    </div>

</div>