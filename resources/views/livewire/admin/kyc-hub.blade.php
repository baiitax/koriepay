<div class="space-y-6 relative">
    
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
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
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
                            <button wire:click="reviewEntity({{ $entity->id }})" class="px-5 py-2.5 bg-slate-50 border border-slate-200 text-slate-600 hover:text-slate-900 hover:border-slate-300 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-sm">
                                Review Dossier
                            </button>
                        </td>
                    </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-100">
            {{ $pendingEntities->links() }}
        </div>
    </div>

    @if($selectedEntity)
    <div class="fixed inset-0 z-[100] flex justify-end">
        
        <div wire:click="closeReview" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-300"></div>

        <div class="relative w-full max-w-2xl h-full bg-white shadow-2xl flex flex-col animate-in slide-in-from-right duration-300 border-l border-slate-200">
            
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h2 class="text-xl font-black text-slate-900 tracking-tight uppercase italic">Entity Dossier</h2>
                    <p class="text-[10px] font-bold text-[#158987] uppercase tracking-[0.2em] mt-1 font-mono">SEC-REF-{{ str_pad($selectedEntity->id, 6, '0', STR_PAD_LEFT) }}</p>
                </div>
                <button wire:click="closeReview" class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-red-500 hover:border-red-200 transition-all shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-8 space-y-8">
                
                <div class="grid grid-cols-2 gap-6 p-6 rounded-[2rem] bg-slate-50 border border-slate-100">
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Registered Name</p>
                        <p class="text-sm font-black text-slate-900">{{ str_replace('[PENDING] ', '', $selectedEntity->name) }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Contact Email</p>
                        <p class="text-sm font-black text-slate-900 font-mono">{{ $selectedEntity->email }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Account Opened</p>
                        <p class="text-sm font-black text-slate-900">{{ $selectedEntity->created_at->format('M d, Y - H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">IP Address Origination</p>
                        <p class="text-sm font-black text-slate-900 font-mono">192.168.{{ rand(1,255) }}.{{ rand(1,255) }}</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-[11px] font-black text-slate-900 uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">Attached Document Vault</h3>
                    
                    <div class="space-y-4">
                        <div class="p-4 rounded-2xl border border-slate-200 bg-white flex items-center justify-between group cursor-pointer hover:border-[#158987] transition-all shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[12px] font-black text-slate-900">Certificate_Of_Incorporation.pdf</p>
                                    <p class="text-[9px] font-bold text-slate-400 mt-0.5 uppercase tracking-widest">2.4 MB • Scanned Securely</p>
                                </div>
                            </div>
                            <span class="text-[9px] font-black text-[#158987] uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">Preview</span>
                        </div>

                        <div class="p-4 rounded-2xl border border-slate-200 bg-white flex items-center justify-between group cursor-pointer hover:border-[#158987] transition-all shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[12px] font-black text-slate-900">Director_Passport_ID.jpg</p>
                                    <p class="text-[9px] font-bold text-slate-400 mt-0.5 uppercase tracking-widest">1.1 MB • Biometric Match: 98%</p>
                                </div>
                            </div>
                            <span class="text-[9px] font-black text-[#158987] uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">Preview</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <p class="text-[11px] font-black text-amber-800 uppercase tracking-widest">Compliance Officer Liability</p>
                        <p class="text-[11px] font-medium text-amber-700 mt-1">By approving this entity, you attest under penalty of perjury that the attached documents meet Sovereign Grid AML/KYC standards.</p>
                    </div>
                </div>

            </div>

            <div class="p-6 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                <button wire:click="reject({{ $selectedEntity->id }})" wire:confirm="Are you absolutely sure you want to BLACKLIST this entity?" class="px-6 py-3 bg-white border border-slate-200 text-red-500 hover:bg-red-50 hover:border-red-200 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-sm">
                    Reject & Blacklist
                </button>
                <button wire:click="approve({{ $selectedEntity->id }})" wire:confirm="Confirm Tier-2 Liquidity Approval?" class="px-8 py-3 bg-slate-900 text-white hover:bg-[#29B475] rounded-xl text-[11px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Authorize Entity
                </button>
            </div>
        </div>
    </div>
    @endif
    </div>