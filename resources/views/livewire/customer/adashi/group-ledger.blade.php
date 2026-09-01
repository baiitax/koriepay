<div class="max-w-md mx-auto space-y-6 py-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('customer.adashi.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ $group->name }}</h1>
            <div class="flex items-center gap-2 mt-0.5">
                <span class="w-2 h-2 rounded-full {{ $group->status === 'active' ? 'bg-[#29B475] animate-pulse' : ($group->status === 'completed' ? 'bg-blue-500' : 'bg-orange-500') }}"></span>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $group->status }} Ledger</p>
            </div>
        </div>
    </div>

    <div class="bg-[#020617] rounded-[2.5rem] p-6 text-white shadow-2xl relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-[#158987]/20 rounded-full blur-3xl"></div>
        
        <div class="flex justify-between items-end relative z-10 mb-6">
            <div>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Lump Sum Payout</p>
                <p class="text-3xl font-mono font-black text-white">
                    {{ $group->currency === 'NGN' ? '₦' : 'CFA' }}{{ number_format($group->contribution_amount * $group->max_members) }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Deduction</p>
                <p class="text-sm font-mono font-black text-[#29B475]">{{ number_format($group->contribution_amount) }} / {{ $group->frequency }}</p>
            </div>
        </div>

        <div class="bg-slate-800/50 p-4 rounded-2xl flex justify-between items-center border border-white/5 relative z-10">
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Timeline Progress</p>
                <p class="text-xs font-bold text-white mt-0.5">Cycle {{ min($currentCycle, $group->max_members) }} of {{ $group->max_members }}</p>
            </div>
            <div class="w-24 h-2 bg-slate-900 rounded-full overflow-hidden">
                <div class="h-full bg-[#29B475] transition-all duration-1000" style="width: {{ (($currentCycle - 1) / $group->max_members) * 100 }}%"></div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-2">Payout Queue</h3>

        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden p-2 space-y-1">
            @foreach($roster as $member)
                @php
                    $isMe = $member->user_id === auth()->id();
                    $isUpNext = $member->payout_order === $currentCycle && $group->status === 'active';
                    $hasPaidOut = $member->has_received_payout;
                    $isDefaulted = $member->status === 'defaulted';
                @endphp

                <div class="flex items-center p-3 rounded-2xl transition-all {{ $isUpNext ? 'bg-[#158987]/5 border border-[#158987]/20 shadow-inner' : 'hover:bg-slate-50' }}">
                    
                    <div class="w-10 text-center shrink-0">
                        @if($hasPaidOut)
                            <div class="w-6 h-6 mx-auto rounded-full bg-[#29B475] text-white flex items-center justify-center shadow-lg shadow-[#29B475]/30">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        @else
                            <span class="text-sm font-black {{ $isUpNext ? 'text-[#158987]' : 'text-slate-300' }}">#{{ $member->payout_order }}</span>
                        @endif
                    </div>

                    <div class="flex-1 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-xs shadow-sm 
                            {{ $isMe ? 'bg-[#020617] text-white' : 'bg-slate-100 text-slate-500' }}">
                            {{ substr($member->user->name, 0, 2) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-black {{ $isMe ? 'text-slate-900' : 'text-slate-700' }}">
                                    {{ $isMe ? 'You' : $member->user->name }}
                                </p>
                                @if($isMe)
                                    <span class="px-1.5 py-0.5 bg-slate-900 text-white rounded text-[7px] font-black uppercase tracking-widest">Me</span>
                                @endif
                            </div>
                            
                            @if($isDefaulted)
                                <p class="text-[9px] font-bold text-red-500 uppercase tracking-widest mt-0.5">Payment Failed</p>
                            @elseif($hasPaidOut)
                                <p class="text-[9px] font-bold text-[#29B475] uppercase tracking-widest mt-0.5">Lump Sum Received</p>
                            @elseif($isUpNext)
                                <p class="text-[9px] font-bold text-[#158987] uppercase tracking-widest mt-0.5 animate-pulse">Collecting This Cycle</p>
                            @else
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Waiting Turn</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>