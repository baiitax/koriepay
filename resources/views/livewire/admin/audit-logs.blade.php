<div class="space-y-6">
    
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight uppercase italic">Sovereign Audit Trail</h2>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                Immutable Event Logging Active
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="relative">
                <input wire:model.live="search" type="text" placeholder="Search Hash, User, Action..." 
                       class="pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-[11px] font-bold text-slate-900 focus:ring-4 focus:ring-[#29B475]/10 outline-none w-64">
                <svg class="w-4 h-4 text-slate-400 absolute left-4 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button class="px-6 py-3 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl">Export CSV</button>
        </div>
    </div>

    <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/5 bg-white/[0.02]">
                        <th class="px-8 py-5 text-[9px] font-black text-slate-500 uppercase tracking-widest">Timestamp</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-500 uppercase tracking-widest">Principal Operator</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-500 uppercase tracking-widest">Action Vector</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-500 uppercase tracking-widest">System Metadata</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-500 uppercase tracking-widest text-right">Node IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.05] font-mono">
                    @forelse($logs as $log)
                    <tr class="hover:bg-white/[0.03] transition-colors group">
                        <td class="px-8 py-5 whitespace-nowrap">
                            <span class="text-[10px] text-slate-500 group-hover:text-[#29B475] transition-colors">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-[#158987]"></div>
                                <span class="text-[11px] font-bold text-slate-200">{{ $log->user_name }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <span class="px-2 py-1 bg-white/5 border border-white/10 rounded text-[9px] font-black text-white uppercase tracking-tighter">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-8 py-5">
                            <p class="text-[10px] text-slate-400 truncate max-w-xs italic">{{ $log->metadata }}</p>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <span class="text-[10px] text-slate-600 font-bold">{{ $log->ip_address }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center text-slate-500 italic text-xs tracking-widest uppercase">
                            No logs found in the sovereign buffer
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-8 py-6 bg-black/20 border-t border-white/5">
            {{ $logs->links() }}
        </div>
    </div>

</div>