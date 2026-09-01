<div class="space-y-6">
    
    <div class="absolute top-0 right-0 z-50">
        @if (session()->has('success'))
            <div class="px-5 py-3 bg-emerald-900/90 backdrop-blur-md border border-[#29B475]/30 rounded-2xl flex items-center gap-3 shadow-2xl animate-in fade-in slide-in-from-top-4 duration-300">
                <span class="text-[10px] font-black text-[#29B475] uppercase tracking-widest">{{ session('success') }}</span>
            </div>
        @endif
        @if (session()->has('warning'))
            <div class="px-5 py-3 bg-amber-900/90 backdrop-blur-md border border-amber-500/30 rounded-2xl flex items-center gap-3 shadow-2xl animate-in fade-in slide-in-from-top-4 duration-300">
                <span class="text-[10px] font-black text-amber-500 uppercase tracking-widest">{{ session('warning') }}</span>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-slate-50/50">
            <div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight uppercase italic flex items-center gap-3">
                    FX Oracle & Routing
                </h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Treasury Base Rates</p>
            </div>
            
            <div class="relative">
                <input wire:model.live="search" type="text" placeholder="Search Pairs (e.g. NGN/XOF)..." class="pl-10 pr-5 py-3 bg-white border border-slate-200 rounded-xl text-[11px] font-bold text-slate-900 outline-none focus:border-[#29B475] w-64 shadow-sm">
                <svg class="w-4 h-4 text-slate-400 absolute left-4 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-slate-100">
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Currency Pair</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Effective Rate</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Corridor Status</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Command</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($rates as $rate)
                    <tr class="hover:bg-slate-50/80 transition-colors group" x-data="{ editing: false, newRate: '{{ $rate->effective_rate }}' }">
                        
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-[9px] font-black text-slate-500 uppercase tracking-widest">
                                    FX
                                </div>
                                <div>
                                    <p class="text-[13px] font-black text-slate-900 tracking-tight font-mono">{{ $rate->pair }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 mt-1 uppercase tracking-widest">Base to Target</p>
                                </div>
                            </div>
                        </td>

                        <td class="px-8 py-5 text-right">
                            <div x-show="!editing" @click="editing = true" class="cursor-pointer group-hover:bg-white group-hover:shadow-sm px-4 py-2 rounded-xl border border-transparent group-hover:border-slate-200 inline-block transition-all">
                                <p class="text-[15px] font-black text-[#158987] font-mono tracking-tighter">{{ number_format($rate->effective_rate, 4) }}</p>
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-1 group-hover:text-slate-500">Click to Override</p>
                            </div>
                            
                            <div x-show="editing" x-cloak class="flex justify-end items-center gap-2">
                                <input type="number" step="0.0001" x-model="newRate" class="w-24 px-3 py-2 bg-white border-2 border-[#29B475] rounded-xl text-[13px] font-black text-slate-900 font-mono outline-none shadow-sm text-right">
                                <button @click="$wire.updateRate({{ $rate->id }}, newRate); editing = false" class="p-2 bg-[#29B475] text-white rounded-xl hover:bg-emerald-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <button @click="editing = false; newRate = '{{ $rate->effective_rate }}'" class="p-2 bg-slate-100 text-slate-400 rounded-xl hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </td>

                        <td class="px-8 py-5 text-center">
                            @if($rate->status == 'active')
                                <span class="px-3 py-1 bg-emerald-50 text-[#29B475] border border-emerald-100 rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-2 justify-center w-24 mx-auto">
                                    <div class="w-1.5 h-1.5 rounded-full bg-[#29B475]"></div> Active
                                </span>
                            @else
                                <span class="px-3 py-1 bg-amber-50 text-amber-600 border border-amber-100 rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-2 justify-center w-24 mx-auto">
                                    <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div> Halted
                                </span>
                            @endif
                        </td>

                        <td class="px-8 py-5 text-right">
                            <button wire:click="toggleStatus({{ $rate->id }})" class="px-4 py-2 {{ $rate->status == 'active' ? 'bg-white border-2 border-slate-200 text-slate-600 hover:border-amber-500 hover:text-amber-600' : 'bg-slate-900 text-white hover:bg-[#29B475]' }} rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm">
                                {{ $rate->status == 'active' ? 'Halt Pair' : 'Activate' }}
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No FX Pairs Provisioned</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-100">
            {{ $rates->links() }}
        </div>
    </div>
</div>