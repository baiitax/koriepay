<div x-data="{ tab: 'active' }" class="space-y-6 pb-24 animate-in fade-in slide-in-from-bottom-4 duration-500 pt-8">
    
    <div class="flex items-center justify-between px-4 sm:px-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 hover:scale-105 transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <p class="text-[10px] font-black text-[#8B5CF6] uppercase tracking-[0.2em] leading-none mb-1">Community Wealth</p>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Adashi Hub</h1>
            </div>
        </div>
        
        <div class="flex items-center gap-2 bg-white p-1.5 rounded-[1.25rem] border border-slate-100 shadow-sm">
            <a href="{{ route('customer.adashi.join') }}" wire:navigate title="Join via Code" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-purple-50 text-[#8B5CF6] hover:bg-purple-100 transition-colors active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                <span class="text-[9px] font-black uppercase tracking-widest hidden sm:block">Join</span>
            </a>
            <a href="{{ route('customer.adashi.create') }}" wire:navigate title="Create Pool" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-[#020617] text-white hover:bg-slate-800 transition-colors shadow-md active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span class="text-[9px] font-black uppercase tracking-widest hidden sm:block">Create</span>
            </a>
        </div>
    </div>

    <div class="mx-4 sm:mx-6 bg-[#020617] rounded-[2.5rem] p-8 text-white relative overflow-hidden shadow-2xl border border-slate-800">
        <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-[#8B5CF6]/30 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="absolute left-10 -top-20 w-40 h-40 bg-blue-500/20 rounded-full blur-[60px] pointer-events-none"></div>

        <div class="flex items-center gap-2 mb-8 relative z-10">
            <div class="w-8 h-8 rounded-full bg-white/10 border border-white/20 flex items-center justify-center backdrop-blur-sm shadow-inner">
                <svg class="w-4 h-4 text-[#a78bfa]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <span class="text-[9px] font-black uppercase tracking-[0.3em] text-white/60">Portfolio Overview</span>
        </div>

        <div class="grid grid-cols-2 gap-6 relative z-10 border-t border-white/10 pt-6">
            <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1.5 leading-none">Your Commitments</p>
                <p class="text-2xl sm:text-3xl font-mono font-black text-white tracking-tighter">₦{{ number_format($totalCommitted) }}<span class="text-[10px] text-slate-500 font-sans tracking-normal ml-1 uppercase">/cycle</span></p>
            </div>
            <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1.5 leading-none">Expected Payouts</p>
                <p class="text-2xl sm:text-3xl font-mono font-black text-[#a78bfa] tracking-tighter">₦{{ number_format($expectedPot) }}</p>
            </div>
        </div>
    </div>

    <div class="mx-4 sm:mx-6 flex p-1.5 bg-slate-100 rounded-[1.25rem] shadow-inner">
        <button @click="tab = 'active'" :class="tab === 'active' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'" class="flex-1 py-3.5 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] transition-all">Active ({{ $activePools->count() }})</button>
        <button @click="tab = 'pending'" :class="tab === 'pending' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'" class="flex-1 py-3.5 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] transition-all">Pending ({{ $pendingPools->count() }})</button>
    </div>

    <div x-show="tab === 'active'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4 px-4 sm:px-6">
        @forelse($activePools as $membership)
            <div class="bg-white p-6 sm:p-8 rounded-[2.5rem] border border-slate-200 shadow-sm hover:shadow-xl hover:border-[#8B5CF6]/30 transition-all duration-300 relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-gradient-to-b from-[#8B5CF6] to-[#6D28D9]"></div>
                
                <div class="flex justify-between items-start mb-6 pl-3">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">{{ $membership->group->name }}</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#8B5CF6] animate-pulse"></span>
                            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Your Turn: <span class="text-[#8B5CF6] font-black">Cycle #{{ $membership->payout_order }}</span></p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-purple-50 text-[#8B5CF6] rounded-lg text-[9px] font-black uppercase tracking-widest border border-purple-200">Live</span>
                </div>

                <div class="grid grid-cols-2 gap-4 bg-slate-50 p-5 rounded-[1.5rem] border border-slate-100 mb-6 pl-6 shadow-inner">
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Deduction</p>
                        <p class="text-sm font-mono font-black text-slate-900">{{ $membership->group->currency === 'NGN' ? '₦' : 'CFA' }}{{ number_format($membership->group->contribution_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Pot Size</p>
                        <p class="text-sm font-mono font-black text-[#8B5CF6]">{{ $membership->group->currency === 'NGN' ? '₦' : 'CFA' }}{{ number_format($membership->group->contribution_amount * $membership->group->max_members) }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-between pl-3 pt-2">
                    <div class="flex -space-x-3">
                        @foreach($membership->group->members->take(5) as $mem)
                            <div class="w-10 h-10 rounded-full border-2 border-white bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-xs font-black text-slate-600 shadow-sm transition-transform hover:-translate-y-1 hover:z-10" title="{{ $mem->user->name }}">
                                @if($mem->user->profile_photo_path)
                                    <img src="{{ asset('storage/' . $mem->user->profile_photo_path) }}" class="w-full h-full rounded-full object-cover">
                                @else
                                    {{ substr($mem->user->name, 0, 1) }}
                                @endif
                            </div>
                        @endforeach
                        @if($membership->group->max_members > 5)
                            <div class="w-10 h-10 rounded-full border-2 border-white bg-slate-50 flex items-center justify-center text-[9px] font-black text-slate-400 shadow-sm z-0">
                                +{{ $membership->group->max_members - 5 }}
                            </div>
                        @endif
                    </div>
                    <a href="{{ route('customer.adashi.ledger', $membership->group->id) }}" wire:navigate class="text-[10px] font-black text-[#8B5CF6] uppercase tracking-widest hover:text-white hover:bg-[#8B5CF6] transition-all flex items-center gap-2 border border-[#8B5CF6]/30 px-4 py-3 rounded-xl active:scale-95 shadow-sm bg-white">
                        Open Ledger <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="py-16 text-center bg-white rounded-[2.5rem] border border-dashed border-slate-200 shadow-sm mx-2">
                <div class="w-16 h-16 bg-purple-50 rounded-full flex items-center justify-center mx-auto mb-4 text-[#8B5CF6]">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest">No Active Pools</h3>
                <p class="text-[10px] text-slate-500 mt-2 font-bold px-8 max-w-xs mx-auto leading-relaxed">Join or create a KoriePay Adashi pool to start building wealth with your community.</p>
            </div>
        @endforelse
    </div>

    <div x-show="tab === 'pending'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4 px-4 sm:px-6">
        @forelse($pendingPools as $membership)
            <div class="bg-white p-6 sm:p-8 rounded-[2.5rem] border border-slate-200 shadow-sm hover:shadow-xl hover:border-[#FCCB1A]/40 transition-all duration-300 relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-gradient-to-b from-[#FCCB1A] to-yellow-500"></div>
                
                <div class="flex justify-between items-start mb-6 pl-3">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">{{ $membership->group->name }}</h3>
                        <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-1">Awaiting Capacity</p>
                    </div>
                    <span class="px-3 py-1 bg-yellow-50 text-yellow-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-yellow-200">Pending</span>
                </div>

                <div class="pl-3 mb-6 space-y-2">
                    <div class="flex justify-between text-[9px] font-black uppercase tracking-widest text-slate-400">
                        <span>Fill Progress</span>
                        <span class="text-slate-900">{{ $membership->group->members->count() }} / {{ $membership->group->max_members }} Locked</span>
                    </div>
                    <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden shadow-inner border border-slate-200/50">
                        <div class="h-full bg-gradient-to-r from-[#FCCB1A] to-yellow-400 rounded-full transition-all duration-1000" style="width: {{ ($membership->group->members->count() / $membership->group->max_members) * 100 }}%"></div>
                    </div>
                </div>

                <div class="pl-3 space-y-4">
                    <div x-data="{ copied: false }" class="bg-slate-50 p-4 rounded-2xl flex items-center justify-between border border-slate-200 shadow-inner">
                        <div>
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Unique Invite Code</p>
                            <p class="text-base font-mono font-black tracking-[0.2em] text-slate-900">{{ $membership->group->invite_code }}</p>
                        </div>
                        <button @click="navigator.clipboard.writeText('{{ $membership->group->invite_code }}'); copied = true; setTimeout(() => copied = false, 2000)" class="w-12 h-12 rounded-xl bg-white border border-slate-200 hover:border-yellow-500 hover:text-yellow-600 text-slate-500 flex items-center justify-center transition-all active:scale-95 shadow-sm">
                            <span x-show="!copied"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></span>
                            <span x-show="copied" x-cloak><svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-end pt-2">
                        @if($membership->user_id === $membership->group->creator_id)
                            <a href="{{ route('customer.adashi.manage', $membership->group->id) }}" wire:navigate class="px-5 py-4 bg-[#020617] text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-slate-800 transition-all shadow-md active:scale-95 flex items-center gap-2">
                                Manage Roster <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </a>
                        @else
                            <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 rounded-xl border border-slate-100 text-slate-400">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span class="text-[9px] font-black uppercase tracking-widest">Awaiting Admin Action</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="py-16 text-center bg-slate-50 rounded-[2.5rem] border border-slate-200 shadow-inner mx-2">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100 text-slate-300 shadow-sm">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4M6 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </div>
                <p class="text-xs font-black text-slate-900 uppercase tracking-widest">No Pending Invitations</p>
            </div>
        @endforelse
    </div>
</div>