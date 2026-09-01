<div class="p-6 lg:p-8 bg-slate-50 min-h-screen font-sans">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-[#29B475] animate-pulse"></span>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Regional Command Node</p>
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight italic">Territory Analytics<span class="text-[#29B475]">.</span></h1>
            <p class="text-sm font-medium text-slate-500 mt-1">Real-time liquidity and network health monitoring.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('regional.capture') }}" class="px-5 py-3 bg-slate-900 text-white rounded-xl text-sm font-bold shadow-lg shadow-slate-900/20 hover:bg-slate-800 transition-all flex items-center gap-2 active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Capture Agent
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        
        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm group hover:shadow-xl hover:shadow-[#29B475]/5 transition-all">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-[#e8f6f0] text-[#29B475] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <span class="text-[11px] font-bold text-[#29B475] bg-[#e8f6f0] px-2 py-1 rounded-lg">{{ $metrics['volume_trend'] }}</span>
            </div>
            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Daily Liquidity Volume</h3>
            <p class="text-3xl font-black text-slate-900 font-mono tracking-tighter">₦{{ $metrics['regional_volume'] }}</p>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <span class="text-[11px] font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-lg">{{ $metrics['active_agents'] }} Active</span>
            </div>
            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Total Agents</h3>
            <p class="text-3xl font-black text-slate-900 tracking-tighter">{{ $metrics['total_agents'] }}</p>
        </div>

        <div class="bg-slate-900 p-6 rounded-[2rem] border border-slate-800 shadow-xl shadow-slate-900/20 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-[#29B475]/10 rounded-full blur-2xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <div class="w-10 h-10 bg-white/10 text-white rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                @if($metrics['pending_kyc'] > 0)
                <span class="text-[10px] font-black text-amber-400 bg-amber-400/10 border border-amber-400/20 px-2 py-1 rounded-lg animate-pulse">Action Required</span>
                @endif
            </div>
            <h3 class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1 relative z-10">Pending KYC</h3>
            <div class="flex items-end gap-3 relative z-10">
                <p class="text-3xl font-black text-white tracking-tighter">{{ $metrics['pending_kyc'] }}</p>
                <a href="{{ route('regional.kyc') }}" class="text-xs font-bold text-[#29B475] hover:text-white transition-colors mb-1.5 underline decoration-2 underline-offset-4">Process Now</a>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-[#158987]/10 text-[#158987] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">M-T-D Revenue</h3>
            <p class="text-3xl font-black text-slate-900 font-mono tracking-tighter">₦{{ $metrics['revenue_share'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-xl font-black text-slate-900 tracking-tight">Recent Network Captures</h2>
                <button class="text-xs font-bold text-[#158987] hover:text-[#29B475] transition-colors">View Directory</button>
            </div>
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Agent Detail</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Phone</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">KYC Status</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Captured</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentAgents as $agent)
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase">
                                        {{ substr($agent->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">{{ $agent->name }}</p>
                                        <p class="text-[10px] font-medium text-slate-400">ID:KP-{{ 1000 + $agent->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-slate-600">{{ $agent->phone }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest
                                    {{ $agent->kyc_status === 'approved' ? 'bg-[#e8f6f0] text-[#29B475]' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $agent->kyc_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-bold text-slate-400 text-right">{{ $agent->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-400 italic">No agents captured in this region yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm">
                <h2 class="text-xl font-black text-slate-900 tracking-tight mb-6 italic">Regional Toolkit</h2>
                <div class="space-y-4">
                    <button class="w-full flex items-center justify-between p-4 bg-slate-50 hover:bg-[#e8f6f0] rounded-2xl border border-slate-100 hover:border-[#29B475]/30 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm text-[#158987]">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <span class="text-sm font-bold text-slate-700">Audit Reports</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-[#29B475] transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    
                    <button class="w-full flex items-center justify-between p-4 bg-slate-50 hover:bg-[#e8f6f0] rounded-2xl border border-slate-100 hover:border-[#29B475]/30 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm text-[#158987]">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="text-sm font-bold text-slate-700">Commission Rates</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-[#29B475] transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            <div class="bg-gradient-to-br from-[#158987] to-[#29B475] p-8 rounded-[2rem] text-white shadow-xl shadow-[#29B475]/20">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-xs font-black uppercase tracking-widest">Network Shield</span>
                </div>
                <p class="text-xl font-bold leading-tight mb-4">Territory status is compliant and verified.</p>
                <div class="h-1.5 w-full bg-white/20 rounded-full overflow-hidden">
                    <div class="h-full bg-white w-[100%]"></div>
                </div>
            </div>
        </div>

    </div>
</div>