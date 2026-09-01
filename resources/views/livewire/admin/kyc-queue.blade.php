<div class="space-y-6">
    
    <div class="absolute top-0 right-0 z-50">
        @if (session()->has('success'))
            <div class="px-5 py-3 bg-emerald-900/90 backdrop-blur-md border border-[#29B475]/30 rounded-2xl flex items-center gap-3 shadow-2xl animate-in fade-in slide-in-from-top-4 duration-300">
                <span class="text-[10px] font-black text-[#29B475] uppercase tracking-widest">{{ session('success') }}</span>
            </div>
        @endif
        @if (session()->has('warning'))
            <div class="px-5 py-3 bg-red-900/90 backdrop-blur-md border border-red-500/30 rounded-2xl flex items-center gap-3 shadow-2xl animate-in fade-in slide-in-from-top-4 duration-300">
                <span class="text-[10px] font-black text-red-500 uppercase tracking-widest">{{ session('warning') }}</span>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-amber-50 p-6 rounded-[2rem] border border-amber-200 shadow-sm relative overflow-hidden">
            <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest mb-1 relative z-10">Awaiting Clearance</p>
            <h3 class="text-3xl font-black text-amber-700 font-mono tracking-tighter relative z-10">{{ $totalPending }}</h3>
            <div class="absolute -bottom-4 -right-4 text-amber-200"><svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2z"/></svg></div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 relative z-10">Verified Entities</p>
            <h3 class="text-3xl font-black text-slate-900 font-mono tracking-tighter relative z-10">{{ $totalVerified }}</h3>
        </div>

        <div class="bg-[#0f172a] p-6 rounded-[2rem] border border-slate-800 shadow-xl relative overflow-hidden">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 relative z-10">Rejected / Blacklisted</p>
            <h3 class="text-3xl font-black text-red-500 font-mono tracking-tighter relative z-10">{{ $totalRejected }}</h3>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        
        <div class="p-8 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-slate-50/50">
            <div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight uppercase italic">Compliance Officer Desk</h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Level 1 Entity Verification</p>
            </div>
            
            <div class="relative">
                <input wire:model.live="search" type="text" placeholder="Search pending entities..." class="pl-10 pr-5 py-3 bg-white border border-slate-200 rounded-xl text-[11px] font-bold text-slate-900 outline-none focus:border-[#29B475] w-64 shadow-sm transition-all">
                <svg class="w-4 h-4 text-slate-400 absolute left-4 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-slate-100">
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Corporate Entity</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Contact Info</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Current Status</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Adjudication</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($pendingEntities as $entity)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 border border-amber-200 flex items-center justify-center text-[10px] font-black text-amber-700 uppercase">
                                    {{ substr(str_replace('[PENDING] ', '', $entity->name), 0, 2) }}
                                </div>
                                <div>
                                    <p class="text-[13px] font-black text-slate-900 tracking-tight">{{ $entity->name }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 mt-1 uppercase tracking-widest">Joined {{ $entity->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </td>

                        <td class="px-8 py-5">
                            <p class="text-[11px] font-black text-slate-600 font-mono tracking-tighter">{{ $entity->email }}</p>
                            <p class="text-[9px] font-bold text-slate-400 mt-1 uppercase tracking-widest">ID: #{{ str_pad($entity->id, 5, '0', STR_PAD_LEFT) }}</p>
                        </td>

                        <td class="px-8 py-5 text-center">
                            <span class="px-3 py-1 bg-amber-50 text-amber-600 border border-amber-200 rounded-full text-[9px] font-black uppercase tracking-widest inline-flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></div> Under Review
                            </span>
                        </td>

                        <td class="px-8 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button 
                                    wire:click="reject({{ $entity->id }})" 
                                    wire:confirm="CRITICAL ACTION: Are you sure you want to REJECT and blacklist this entity?"
                                    class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:bg-red-50 hover:text-red-600 hover:border-red-200 rounded-xl transition-all shadow-sm group" title="Reject KYC">
                                    <svg class="w-4 h-4 group-hover:rotate-90 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                
                                <button 
                                    wire:click="approve({{ $entity->id }})" 
                                    wire:confirm="AUTHORIZATION: Are you sure you want to APPROVE this entity for Tier-2 Liquidity Access?"
                                    class="px-5 py-2.5 bg-slate-900 text-white hover:bg-[#29B475] rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-md flex items-center gap-2">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Clear Entity
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-center mx-auto mb-4 text-[#29B475]">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <p class="text-[12px] font-black text-slate-900 tracking-tight">Queue Empty</p>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">All entities have been adjudicated</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-100">
            {{ $pendingEntities->links() }}
        </div>
    </div>
</div>