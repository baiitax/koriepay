<div class="p-4 lg:p-8 max-w-[1600px] mx-auto space-y-8">
    
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">{{ __('Regional Performance Ledger') }}</h1>
            <p class="text-slate-500 font-bold text-sm mt-1 uppercase tracking-widest text-[10px]">{{ __('Revenue vs Throughput Analytics') }}</p>
        </div>
        
        <div class="flex items-center gap-3 bg-slate-50 p-2 rounded-2xl border border-slate-100">
            <span class="text-[9px] font-black text-slate-400 uppercase px-3">Window</span>
            <select wire:model.live="timeframe" class="bg-white border-none rounded-xl text-xs font-black text-slate-700 focus:ring-2 focus:ring-emerald-500 shadow-sm">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto relative">
            <div wire:loading.delay.block class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center">
                <div class="flex gap-1">
                    <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                    <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                </div>
            </div>

            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-[0.2em] border-b border-slate-100">
                    <tr>
                        <th class="px-8 py-5">Agent Node</th>
                        <th class="px-8 py-5">Total Volume ({{ $currency }})</th>
                        <th class="px-8 py-5">Fees Generated</th>
                        <th class="px-8 py-5">Yield Efficiency</th>
                        <th class="px-8 py-5 text-right">Trend</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($performanceData as $data)
                    @php
                        $yield = $data->total_volume > 0 ? ($data->total_fees / $data->total_volume) * 100 : 0;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-all group">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-black text-xs shadow-lg">
                                    {{ substr($data->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-black text-slate-900 text-sm">{{ $data->name }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">UID: SP-{{ $data->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <p class="font-black text-slate-900">{{ number_format($data->total_volume ?? 0, 2) }}</p>
                        </td>
                        <td class="px-8 py-5">
                            <p class="font-black text-emerald-600">+{{ number_format($data->total_fees ?? 0, 2) }}</p>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-1.5 w-24 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 transition-all duration-1000" style="width: {{ min($yield * 20, 100) }}%"></div>
                                </div>
                                <span class="text-xs font-black text-slate-700">{{ number_format($yield, 2) }}%</span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <a href="{{ route('manager.agent-detail', $data->id) }}" wire:navigate class="px-4 py-2 bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-emerald-600 transition-all">
                                Full Audit
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center text-slate-400 font-bold">No performance data captured for this window.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($performanceData->hasPages())
        <div class="p-6 bg-slate-50 border-t border-slate-100">
            {{ $performanceData->links() }}
        </div>
        @endif
    </div>
</div>