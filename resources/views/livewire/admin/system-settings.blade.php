<div class="max-w-5xl mx-auto space-y-6">
    
    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight uppercase italic">System Configuration</h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Master Governance & API Uplinks</p>
            </div>
            
            <div class="flex bg-slate-100 p-1.5 rounded-2xl border border-slate-200/50">
                <button wire:click="setTab('general')" class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $activeTab == 'general' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                    General
                </button>
                <button wire:click="setTab('security')" class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $activeTab == 'security' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                    Security
                </button>
                <button wire:click="setTab('api')" class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $activeTab == 'api' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                    API Keys
                </button>
            </div>
        </div>

        <div class="p-10">
            @if($activeTab == 'general')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 animate-in fade-in slide-in-from-bottom-2 duration-500">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Platform Brand Name</label>
                        <input wire:model="siteName" type="text" class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:ring-4 focus:ring-[#29B475]/10 focus:border-[#29B475] outline-none transition-all">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Global Transaction Ceiling (NGN)</label>
                        <input wire:model="maxTransactionLimit" type="number" class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 outline-none">
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="p-6 bg-[#f8fafc] rounded-[2rem] border border-slate-200 flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-black text-slate-900 uppercase">Maintenance Mode</h4>
                            <p class="text-[10px] font-bold text-slate-400 mt-1">Suspend all node activity</p>
                        </div>
                        <button wire:click="$toggle('maintenanceMode')" class="w-14 h-8 rounded-full relative transition-colors {{ $maintenanceMode ? 'bg-red-500' : 'bg-slate-300' }}">
                            <div class="absolute top-1 left-1 w-6 h-6 bg-white rounded-full transition-transform {{ $maintenanceMode ? 'translate-x-6' : '' }}"></div>
                        </button>
                    </div>

                    <div class="p-6 bg-[#f0fdf4] rounded-[2rem] border border-[#29B475]/10 flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-black text-[#158987] uppercase tracking-tighter italic">Platform Fee (%)</h4>
                            <p class="text-[10px] font-bold text-[#29B475] mt-1">Flat rate on every cross-border leg</p>
                        </div>
                        <input wire:model="platformFee" type="number" step="0.1" class="w-20 bg-transparent text-right text-xl font-black text-[#158987] outline-none">
                    </div>
                </div>
            </div>
            @endif

            @if($activeTab == 'security')
            <div class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-500 text-center py-10">
                <div class="w-16 h-16 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-center mx-auto mb-4 text-slate-300">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest">Enhanced Security Protocol</h3>
                <p class="text-xs text-slate-500 max-w-xs mx-auto">Manage 2FA, IP Whitelisting, and Session Heartbeats for the Sovereign Admin Cluster.</p>
                <button class="mt-4 px-8 py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest">Configure Firewall</button>
            </div>
            @endif

            <div class="mt-12 pt-8 border-t border-slate-100 flex items-center justify-between">
                @if (session('status'))
                    <span class="text-[10px] font-black text-[#29B475] uppercase animate-pulse">{{ session('status') }}</span>
                @else
                    <span></span>
                @endif
                <button wire:click="saveSettings" class="px-10 py-4 bg-[#020617] text-white rounded-[1.5rem] text-[10px] font-black uppercase tracking-[0.2em] hover:bg-[#158987] transition-all shadow-xl shadow-slate-900/10">
                    Synchronize Configuration
                </button>
            </div>
        </div>
    </div>

    <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1 bg-white p-6 rounded-3xl border border-slate-200 flex items-center gap-4">
            <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center font-black text-[10px] text-slate-400">PHP</div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Server Environment</p>
                <p class="text-xs font-bold text-slate-900">v8.2.12 Stable</p>
            </div>
        </div>
        <div class="flex-1 bg-white p-6 rounded-3xl border border-slate-200 flex items-center gap-4">
            <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center font-black text-[10px] text-slate-400">DB</div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Database Node</p>
                <p class="text-xs font-bold text-slate-900 italic">MySQL 127.0.0.1 (sahelpay_db)</p>
            </div>
        </div>
    </div>

</div>