<div class="max-w-md mx-auto space-y-6 pb-24 animate-in fade-in slide-in-from-bottom-4 duration-500" 
     x-data="{ 
        loading: true,
        init() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        $wire.updateLocation(pos.coords.latitude, pos.coords.longitude);
                        this.loading = false;
                    },
                    (err) => {
                        this.loading = false;
                        alert('Please enable location services to find nearby nodes.');
                    }
                );
            }
        }
     }">
    
    <div class="flex items-center justify-between mb-2">
        <div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight">Liquidity <span class="text-[#158987]">Map</span></h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Find nearby Cash-In/Out Nodes</p>
        </div>
        <div class="w-10 h-10 bg-white border border-slate-200 rounded-2xl flex items-center justify-center text-[#158987] shadow-sm">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
    </div>

    <div class="relative group">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by business name..." class="w-full pl-12 pr-4 py-4 bg-white border border-slate-200 rounded-[1.5rem] text-sm font-bold shadow-sm focus:ring-[#158987]">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </div>

    <div class="space-y-4">
        <template x-if="loading">
            <div class="py-20 text-center space-y-4">
                <div class="w-12 h-12 border-4 border-[#158987] border-t-transparent rounded-full animate-spin mx-auto"></div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Pinging the Liquidity Grid...</p>
            </div>
        </template>

        <div x-show="!loading" class="space-y-4 animate-in fade-in duration-500">
            @forelse($agents as $agent)
                <div class="bg-white p-5 rounded-[2.5rem] border border-slate-200 shadow-sm flex items-center justify-between group hover:border-[#158987]/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-[#158987] border border-slate-100 font-black text-lg">
                            {{ substr($agent->business_name ?? $agent->name, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="text-xs font-black text-slate-900">{{ $agent->business_name ?? $agent->name }}</h3>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-[#29B475]/10 text-[#29B475] text-[8px] font-black rounded-md uppercase tracking-tighter">Verified Node</span>
                                <span class="text-[10px] font-mono font-bold text-slate-400">{{ round($agent->distance, 1) }} km</span>
                            </div>
                        </div>
                    </div>
                    
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $agent->latitude }},{{ $agent->longitude }}" 
                       target="_blank" 
                       class="w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center hover:bg-[#158987] transition-colors active:scale-90 shadow-lg shadow-slate-900/10">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    </a>
                </div>
            @empty
                <div x-show="!loading" class="py-20 text-center bg-slate-50 rounded-[3rem] border-2 border-dashed border-slate-200">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-slate-300 mx-auto mb-4 border border-slate-100 shadow-inner">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <p class="text-xs font-black text-slate-500 uppercase tracking-widest px-10">No Agents found within {{ $radius }}km of your position.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>