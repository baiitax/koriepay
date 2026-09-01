<div class="space-y-6 max-w-7xl mx-auto">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 bg-[#020617] rounded-[2.5rem] p-10 shadow-2xl relative overflow-hidden flex items-center justify-between group">
            <div class="absolute top-0 right-0 w-64 h-64 bg-[#29B475]/10 rounded-full blur-[80px] -mr-32 -mt-32 transition-all group-hover:bg-[#29B475]/20"></div>
            
            <div class="relative z-10">
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em] mb-4 italic">Net Cumulative Revenue</p>
                <div class="flex items-baseline gap-3">
                    <h2 class="text-6xl font-black text-white tracking-tighter italic">
                        ${{ number_format($totalRevenueUSD, 2) }}
                    </h2>
                    <span class="text-xs font-bold text-[#29B475] uppercase tracking-widest leading-none">Global USD</span>
                </div>
                <div class="mt-8 flex items-center gap-6">
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-slate-500 uppercase">Growth Index</span>
                        <span class="text-xs font-bold text-white tracking-tight">+14.2% <small class="text-[#29B475] ml-1">↑</small></span>
                    </div>
                    <div class="w-px h-8 bg-white/10"></div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-slate-500 uppercase">System Uptime</span>
                        <span class="text-xs font-bold text-white tracking-tight">99.98%</span>
                    </div>
                </div>
            </div>

            <div class="hidden md:block relative z-10 text-right">
                <div class="p-4 bg-white/5 border border-white/10 rounded-2xl backdrop-blur-md">
                    <p class="text-[9px] font-black text-slate-400 uppercase mb-2">Revenue Velocity</p>
                    <div class="flex gap-1 h-12 items-end">
                        @foreach([30, 50, 40, 80, 60, 90, 70] as $h)
                            <div class="w-1.5 bg-[#29B475] rounded-full transition-all hover:h-full" style="height: {{ $h }}%"></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] border border-slate-200 p-8 shadow-sm flex flex-col justify-between">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Income Attribution</h3>
            
            <div class="space-y-6">
                <div>
                    <div class="flex justify-between text-[11px] font-black text-slate-900 uppercase mb-2">
                        <span>FX Spread Income</span>
                        <span class="text-[#158987]">${{ number_format($spreadIncome, 2) }}</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="bg-[#158987] h-full" style="width: 68%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-[11px] font-black text-slate-900 uppercase mb-2">
                        <span>Transaction Fees</span>
                        <span class="text-[#29B475]">${{ number_format($feeIncome, 2) }}</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="bg-[#29B475] h-full" style="width: 32%"></div>
                    </div>
                </div>
            </div>

            <button class="w-full mt-8 py-4 border-2 border-slate-900 text-slate-900 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-900 hover:text-white transition-all">
                Download Tax Audit
            </button>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-10 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest italic">Live Revenue Stream</h3>
                <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Real-time Attribution Mapping</p>
            </div>
            <div class="flex gap-2">
                <button class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-[9px] font-black uppercase text-slate-500 hover:text-slate-900 transition-all">Filter</button>
                <button class="px-4 py-2 bg-[#020617] rounded-xl text-[9px] font-black uppercase text-white shadow-lg">Export CSV</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-slate-100">
                        <th class="px-10 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Entry ID</th>
                        <th class="px-10 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Revenue Source</th>
                        <th class="px-10 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Node Path</th>
                        <th class="px-10 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Net Gain</th>
                        <th class="px-10 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($recentLogs as $log)
                    <tr class="hover:bg-slate-50 transition-colors group cursor-pointer">
                        <td class="px-10 py-5">
                            <span class="text-[11px] font-black text-slate-900 group-hover:text-[#158987]">{{ $log['id'] }}</span>
                        </td>
                        <td class="px-10 py-5">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full {{ $log['source'] == 'FX Spread' ? 'bg-[#158987]' : 'bg-[#29B475]' }}"></span>
                                <span class="text-[11px] font-bold text-slate-700 italic">{{ $log['source'] }}</span>
                            </div>
                        </td>
                        <td class="px-10 py-5 text-[10px] font-black text-slate-400 uppercase tracking-tighter">{{ $log['pair'] }} Interface</td>
                        <td class="px-10 py-5">
                            <span class="text-[12px] font-black text-slate-900">+${{ number_format($log['amount'], 2) }}</span>
                        </td>
                        <td class="px-10 py-5 text-right text-[10px] font-bold text-slate-400 italic">
                            {{ $log['time'] }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>