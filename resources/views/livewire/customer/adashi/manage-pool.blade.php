<div class="space-y-6 pb-24 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center justify-between px-1 mb-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.adashi.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors active:scale-95">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight leading-none">Manage Roster</h1>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mt-1">{{ $group->name }}</p>
            </div>
        </div>
        <div class="px-3 py-1 bg-orange-50 text-orange-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-orange-100 shadow-sm">
            Pending
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 text-green-700 p-4 rounded-2xl text-[10px] font-black uppercase tracking-widest border border-green-200 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-[#020617] rounded-[2rem] p-6 sm:p-8 text-white relative overflow-hidden shadow-xl">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-orange-500/20 rounded-full blur-[60px] pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col gap-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1">Invite Code</p>
                    <p class="text-3xl font-mono font-black tracking-[0.2em] text-[#8B5CF6]">{{ $group->invite_code }}</p>
                </div>
                <button onclick="navigator.clipboard.writeText('{{ $group->invite_code }}'); alert('Code Copied!');" class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-xl flex items-center justify-center transition-colors active:scale-95">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between text-[10px] font-black uppercase tracking-widest text-slate-300">
                    <span>Filled Slots</span>
                    <span>{{ $roster->count() }} / {{ $group->max_members }}</span>
                </div>
                <div class="w-full h-3 bg-white/10 rounded-full overflow-hidden shadow-inner">
                    <div class="h-full bg-orange-500 rounded-full transition-all duration-1000" style="width: {{ ($roster->count() / $group->max_members) * 100 }}%"></div>
                </div>
                <p class="text-[9px] text-slate-400 leading-relaxed pt-2">Pool will automatically lock and activate the moment the {{ $group->max_members }}th member joins.</p>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] pl-2">Current Members</h3>

        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden p-2 space-y-1">
            @foreach($roster as $member)
                @php
                    $isCreator = $member->user_id === $group->creator_id;
                @endphp

                <div class="flex items-center justify-between p-3 rounded-2xl transition-all {{ $isCreator ? 'bg-slate-50 border border-slate-100' : 'hover:bg-slate-50' }}">
                    
                    <div class="flex items-center gap-4">
                        <div class="w-8 text-center shrink-0">
                            <span class="text-xs font-black text-slate-400">#{{ $member->payout_order }}</span>
                        </div>

                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-xs shadow-sm {{ $isCreator ? 'bg-[#020617] text-white' : 'bg-slate-100 text-slate-600' }}">
                            {{ substr($member->user->name, 0, 2) }}
                        </div>
                        
                        <div>
                            <p class="text-xs font-black text-slate-900">{{ $member->user->name }}</p>
                            @if($isCreator)
                                <p class="text-[8px] font-bold text-[#8B5CF6] uppercase tracking-widest mt-0.5">Pool Admin</p>
                            @else
                                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Member</p>
                            @endif
                        </div>
                    </div>

                    @if(!$isCreator)
                        <button wire:click="kickMember({{ $member->id }})" wire:confirm="Are you sure you want to kick this user? They will lose their slot in the pool." class="px-4 py-2 bg-red-50 text-red-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-colors active:scale-95 border border-red-100 hover:border-red-500">
                            Kick
                        </button>
                    @endif
                </div>
            @endforeach

            @for ($i = $roster->count() + 1; $i <= $group->max_members; $i++)
                <div class="flex items-center p-3 rounded-2xl opacity-50 border border-dashed border-slate-200 mx-1 my-1">
                    <div class="w-8 text-center shrink-0">
                        <span class="text-xs font-black text-slate-300">#{{ $i }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-full border-2 border-dashed border-slate-300 flex items-center justify-center text-slate-300 ml-4">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <p class="text-xs font-bold text-slate-400 ml-4 tracking-widest uppercase">Awaiting Member</p>
                </div>
            @endfor
        </div>
    </div>
</div>