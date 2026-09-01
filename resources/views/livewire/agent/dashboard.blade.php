<div class="space-y-8 animate-in fade-in duration-700">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-2">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">Command Center</h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="w-2 h-2 rounded-full bg-[#29B475] animate-pulse"></span>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Node Online • {{ now()->format('H:i T') }}</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="{{ route('agent.cash-in') }}" class="px-6 py-3 bg-[#29B475] text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-100 hover:scale-105 transition-all">New Deposit</a>
            <a href="{{ route('agent.cash-out') }}" class="px-6 py-3 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-100 hover:scale-105 transition-all">New Withdrawal</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-slate-900 rounded-[2rem] p-8 text-white relative overflow-hidden shadow-xl">
            <div class="absolute top-0 right-0 w-32 h-32 bg-[#29B475]/10 rounded-full blur-3xl -mr-10 -mt-10"></div>
            <p class="text-[9px] font-black text-[#29B475] uppercase tracking-[0.3em] mb-4">Today's Earnings</p>
            <h3 class="text-3xl font-black font-mono tracking-tighter italic">₦{{ number_format($cashOutVolume * 0.01, 2) }}</h3>
            <p class="text-[9px] font-bold text-slate-500 mt-2">Accrued from 1% commission</p>
        </div>

        <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] mb-4 text-emerald-500">Inbound Volume</p>
            <h3 class="text-3xl font-black text-slate-900 font-mono tracking-tighter">₦{{ number_format($cashInVolume, 2) }}</h3>
            <div class="w-full bg-slate-50 h-1.5 rounded-full mt-4">
                <div class="bg-emerald-500 h-full rounded-full" style="width: 65%"></div>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] mb-4 text-blue-500">Outbound Volume</p>
            <h3 class="text-3xl font-black text-slate-900 font-mono tracking-tighter">₦{{ number_format($cashOutVolume, 2) }}</h3>
            <div class="w-full bg-slate-50 h-1.5 rounded-full mt-4">
                <div class="bg-blue-500 h-full rounded-full" style="width: 45%"></div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-50 flex items-center justify-between bg-slate-50/30">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest italic">Live Operations Stream</h2>
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $dailyOperations }} Events Today</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">
                        <th class="px-8 py-4">Reference</th>
                        <th class="px-8 py-4">Protocol</th>
                        <th class="px-8 py-4 text-right">Settlement</th>
                        <th class="px-8 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($recentActivity as $tx)
                    <tr class="group hover:bg-slate-50/80 transition-all">
                        <td class="px-8 py-5">
                            <p class="text-[11px] font-black text-slate-900 font-mono tracking-widest group-hover:text-blue-600 transition-colors">{{ $tx->reference }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">{{ $tx->created_at->diffForHumans() }}</p>
                        </td>
                        <td class="px-8 py-5">
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $tx->type == 'cash_in' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600' }}">
                                {{ str_replace('_', ' ', $tx->type) }}
                            </span>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <p class="text-sm font-black text-slate-900 font-mono tracking-tighter">₦{{ number_format($tx->source_amount, 2) }}</p>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <div class="w-2 h-2 rounded-full mx-auto {{ $tx->status == 'completed' ? 'bg-[#29B475]' : 'bg-amber-400 animate-pulse' }}"></div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center text-slate-400 text-[10px] font-black uppercase tracking-widest italic">No terminal activity detected today</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>