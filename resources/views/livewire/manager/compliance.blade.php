<div class="p-4 lg:p-8 max-w-[1600px] mx-auto space-y-8">
    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">{{ __('Compliance Radar') }}</h1>
            <p class="text-slate-500 font-bold text-sm mt-1 uppercase tracking-widest text-[10px]">{{ __('Real-time Velocity & AML Monitoring') }}</p>
        </div>
        <div class="px-6 py-3 bg-red-50 border border-red-100 rounded-2xl">
            <p class="text-[9px] font-black text-red-500 uppercase tracking-widest mb-1">System Limit (Daily)</p>
            <p class="text-xl font-black text-red-600">{{ $countryCode === 'NGA' ? '₦' : 'CFA' }} {{ number_format($dailyLimit) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        @foreach($limitData as $agent)
            <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm hover:border-slate-300 transition-all">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-4 min-w-[250px]">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center font-black text-slate-500">
                            {{ substr($agent->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-black text-slate-900 text-sm">{{ $agent->name }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">UID: SP-{{ $agent->id }}</p>
                        </div>
                    </div>

                    <div class="flex-1 w-full space-y-2">
                        <div class="flex justify-between items-end px-1">
                            <span class="text-[9px] font-black uppercase {{ $agent->usage_percent > 80 ? 'text-red-500' : 'text-slate-400' }}">
                                {{ $agent->usage_percent > 80 ? 'High Velocity Alert' : 'Normal Activity' }}
                            </span>
                            <span class="text-xs font-black text-slate-900">{{ number_format($agent->usage_percent, 1) }}%</span>
                        </div>
                        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200/50">
                            <div class="h-full transition-all duration-1000 {{ $agent->usage_percent > 80 ? 'bg-red-500' : ($agent->usage_percent > 50 ? 'bg-amber-500' : 'bg-emerald-500') }}" 
                                 style="width: {{ min($agent->usage_percent, 100) }}%">
                            </div>
                        </div>
                    </div>

                    <div class="text-right min-w-[150px]">
                        <p class="text-sm font-black text-slate-900">{{ number_format($agent->daily_volume ?? 0, 2) }}</p>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Daily Inbound</p>
                    </div>

                    <a href="{{ route('manager.agent-detail', $agent->id) }}" wire:navigate class="px-6 py-3 bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-black transition-all">
                        Investigate
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>