<div class="max-w-md mx-auto space-y-6 py-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Join Adashi Pool</h1>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Enter Secure Invite Code</p>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden relative">
        <div class="absolute top-0 left-0 h-1 bg-[#158987] transition-all duration-500" style="width: {{ ($step / 3) * 100 }}%"></div>

        @if($step === 1)
            <div class="p-8 space-y-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-[#158987]/10 text-[#158987] rounded-full flex items-center justify-center mx-auto mb-4 border border-[#158987]/20">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">Access Community Ring</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Request the 6-character code from the creator</p>
                </div>

                <div>
                    <input wire:model="invite_code" type="text" maxlength="6" placeholder="XXXXXX" 
                           class="w-full py-6 bg-slate-50 border-2 border-slate-100 rounded-2xl text-4xl text-center font-mono font-black tracking-[0.5em] text-[#158987] uppercase focus:ring-0 focus:border-[#158987] shadow-inner transition-all">
                    @error('invite_code') <span class="text-[10px] text-red-500 font-bold uppercase text-center block mt-3">{{ $message }}</span> @enderror
                </div>

                <button wire:click="verifyCode" wire:loading.attr="disabled" class="w-full py-5 bg-[#020617] text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-slate-800 transition-all flex justify-center items-center">
                    <span wire:loading.remove wire:target="verifyCode">Verify Contract</span>
                    <span wire:loading wire:target="verifyCode">Pinging Ledger...</span>
                </button>
            </div>

        @elseif($step === 2)
            <div class="p-8 space-y-6 animate-in slide-in-from-right-4">
                <div class="bg-slate-900 p-6 rounded-[2rem] text-white text-center shadow-xl">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Target Pool</p>
                    <h2 class="text-xl font-black text-white truncate">{{ $group->name }}</h2>
                    <div class="mt-4 inline-block px-3 py-1 bg-[#158987]/20 border border-[#158987]/30 text-[#158987] rounded-lg text-[9px] font-black uppercase tracking-widest">
                        Slot {{ $assigned_order }} of {{ $group->max_members }} Available
                    </div>
                </div>

                <div class="space-y-3 bg-slate-50 p-6 rounded-[2rem] border border-slate-100">
                    <div class="flex justify-between items-center border-b border-slate-200 pb-3">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Your Commitment</span>
                        <span class="text-xs font-black text-slate-900">{{ $group->currency }} {{ number_format($group->contribution_amount) }} / {{ $group->frequency }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-200 pb-3">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Your Payout Turn</span>
                        <span class="text-xs font-black text-slate-900">Cycle #{{ $assigned_order }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Lump Sum</span>
                        <span class="text-xs font-black text-[#29B475]">{{ $group->currency }} {{ number_format($expected_payout) }}</span>
                    </div>
                </div>

                <div class="p-4 bg-orange-50 border border-orange-100 rounded-2xl flex items-start gap-3">
                    <svg class="w-5 h-5 text-orange-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-[9px] font-bold text-orange-700 leading-relaxed uppercase tracking-widest">By joining, you authorize SahelPay to automatically deduct {{ number_format($group->contribution_amount) }} {{ $group->currency }} from your vault on cycle dates.</p>
                </div>

                <div class="flex gap-3">
                    <button wire:click="$set('step', 1)" class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-2xl text-[10px] font-black uppercase tracking-widest">Cancel</button>
                    <button wire:click="lockIn" wire:loading.attr="disabled" class="flex-[2] py-4 bg-[#29B475] text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-lg shadow-[#29B475]/30 flex justify-center items-center">
                        <span wire:loading.remove wire:target="lockIn">Accept & Lock In</span>
                        <span wire:loading wire:target="lockIn">Securing Slot...</span>
                    </button>
                </div>
            </div>

        @elseif($step === 3)
            <div class="p-8 text-center space-y-6 animate-in zoom-in-95 duration-500">
                <div class="w-24 h-24 bg-[#158987] text-white rounded-[2rem] flex items-center justify-center mx-auto shadow-2xl shadow-[#158987]/40 rotate-3">
                    <svg class="w-12 h-12 -rotate-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                
                <div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Contract Secured</h2>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-2 px-4 leading-relaxed">You are officially Member #{{ $assigned_order }} of {{ $group->name }}. Deductions begin automatically once the pool is full.</p>
                </div>

                <a href="{{ route('customer.dashboard') }}" wire:navigate class="block w-full py-5 bg-[#020617] text-white shadow-xl hover:bg-slate-800 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition-all">
                    Return to Dashboard
                </a>
            </div>
        @endif
    </div>
</div>