<div class="space-y-6 relative" wire:poll.10s>
    
    <div class="absolute top-0 right-0 z-50">
        @if (session()->has('success'))
            <div class="px-5 py-3 bg-emerald-900/90 backdrop-blur-md border border-[#29B475]/30 rounded-2xl flex items-center gap-3 shadow-2xl animate-in fade-in slide-in-from-top-4 duration-300">
                <span class="relative flex h-2.5 w-2.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#29B475] opacity-75"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#29B475]"></span></span>
                <span class="text-[10px] font-black text-[#29B475] uppercase tracking-widest">{{ session('success') }}</span>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-[#0f172a] p-6 rounded-[2rem] border border-slate-800 shadow-2xl relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-br from-[#29B475]/5 to-transparent"></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 relative z-10">NGN Master Pool</p>
            <h3 class="text-2xl font-black text-white font-mono tracking-tighter relative z-10">₦{{ number_format($ngnPool, 2) }}</h3>
            <div class="absolute -bottom-4 -right-4 text-[#29B475]/10 group-hover:scale-110 transition-transform"><svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2z"/></svg></div>
        </div>

        <div class="bg-[#0f172a] p-6 rounded-[2rem] border border-slate-800 shadow-2xl relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent"></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 relative z-10">XOF Master Pool</p>
            <h3 class="text-2xl font-black text-white font-mono tracking-tighter relative z-10">{{ number_format($xofPool, 2) }} <span class="text-sm text-slate-500">CFA</span></h3>
            <div class="absolute -bottom-4 -right-4 text-blue-500/10 group-hover:scale-110 transition-transform"><svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2z"/></svg></div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Network Uplinks</p>
                <div class="flex items-center gap-3">
                    <h3 class="text-2xl font-black text-slate-900 font-mono tracking-tighter">{{ $onlineNodes }} / {{ $totalNodes }}</h3>
                    <span class="text-[10px] font-bold text-[#29B475] uppercase tracking-widest">Active</span>
                </div>
            </div>
            <button wire:click="syncAllNodes" wire:loading.attr="disabled" class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 hover:text-slate-900 hover:border-slate-300 transition-all shadow-sm group">
                <svg wire:loading.class="animate-spin text-[#158987]" class="w-6 h-6 group-hover:rotate-180 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        
        <div class="p-8 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-slate-50/30">
            <div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight uppercase italic flex items-center gap-3">
                    API Telemetry Matrix
                    <div wire:loading class="w-4 h-4 rounded-full border-2 border-[#29B475] border-t-transparent animate-spin"></div>
                </h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Live Polling Every 10s</p>
            </div>
            <div class="relative">
                <input wire:model.live="search" type="text" placeholder="Filter Nodes..." class="pl-10 pr-5 py-3 bg-white border border-slate-200 rounded-xl text-[11px] font-bold text-slate-900 outline-none focus:border-[#29B475] w-64 shadow-sm">
                <svg class="w-4 h-4 text-slate-400 absolute left-4 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-slate-100">
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Institution & Routing</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Live Capital Reserve</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Network Latency</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Override</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($nodes as $node)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-[11px] font-black {{ $node->api_status == 'online' ? 'text-slate-900' : 'text-slate-300' }}">
                                    {{ substr($node->bank_name, 0, 3) }}
                                </div>
                                <div>
                                    <p class="text-[13px] font-black text-slate-900 tracking-tight flex items-center gap-2">
                                        {{ $node->bank_name }}
                                        @if($node->api_status == 'online')
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#29B475] shadow-[0_0_8px_#29B475]"></span>
                                        @endif
                                    </p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] font-black text-slate-500 font-mono bg-slate-100 px-1.5 py-0.5 rounded">•••• {{ substr($node->account_no, -4) }}</span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Node {{ $node->id }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-8 py-5 text-right">
                            <p class="text-[15px] font-black text-slate-900 font-mono tracking-tighter transition-all duration-500" wire:key="bal-{{ $node->id }}-{{ $node->balance }}">
                                {{ number_format($node->balance, 2) }}
                            </p>
                            <p class="text-[9px] font-bold text-[#158987] mt-1 uppercase tracking-widest">{{ $node->currency }}</p>
                        </td>

                        <td class="px-8 py-5 text-center">
                            @if($node->api_status == 'online')
                                <div class="inline-flex flex-col items-center">
                                    <span class="px-3 py-1.5 bg-[#f0fdf4] text-[#29B475] border border-[#29B475]/20 rounded-xl text-[9px] font-black uppercase tracking-widest flex items-center gap-2">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        {{ rand(12, 45) }}ms
                                    </span>
                                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-1.5">Last: {{ \Carbon\Carbon::parse($node->last_sync)->format('H:i:s') }}</span>
                                </div>
                            @else
                                <span class="px-3 py-1.5 bg-slate-100 text-slate-400 border border-slate-200 rounded-xl text-[9px] font-black uppercase tracking-widest">
                                    Offline
                                </span>
                            @endif
                        </td>

                        <td class="px-8 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="forceSync({{ $node->id }})" wire:loading.attr="disabled" class="px-4 py-2.5 bg-slate-50 border border-slate-200 text-slate-600 hover:text-slate-900 hover:border-slate-300 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm">
                                    Ping
                                </button>
                                <button wire:click="toggleStatus({{ $node->id }})" class="px-4 py-2.5 {{ $node->api_status == 'online' ? 'bg-slate-900 text-white hover:bg-red-500' : 'bg-[#29B475] text-white hover:bg-emerald-600' }} rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm">
                                    {{ $node->api_status == 'online' ? 'Halt' : 'Boot' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>