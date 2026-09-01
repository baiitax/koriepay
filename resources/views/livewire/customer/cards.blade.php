<div class="max-w-2xl mx-auto py-8 px-4 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">KoriePay Cards</h1>
            <p class="text-[9px] font-bold text-[#158987] uppercase tracking-widest">Global Spending Power</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 bg-[#e8f6f0] border border-[#29B475]/20 text-[#29B475] px-4 py-3 rounded-xl text-xs font-bold shadow-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl overflow-hidden p-8 sm:p-12 text-center relative">
        <div class="absolute top-0 right-0 w-64 h-64 bg-[#158987]/30 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-[#FCCB1A]/20 rounded-full blur-[80px] pointer-events-none"></div>

        <div class="relative z-10 space-y-8">
            
            <div class="w-64 h-40 sm:w-80 sm:h-48 mx-auto rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md shadow-2xl p-6 flex flex-col justify-between -rotate-3 hover:rotate-0 transition-transform duration-500">
                <div class="flex justify-between items-start">
                    <span class="font-black text-white italic text-xl">K</span>
                    <svg class="w-8 h-8 text-white/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                </div>
                <div>
                    <div class="w-10 h-6 bg-white/20 rounded mb-4"></div>
                    <p class="text-white font-mono tracking-[0.2em] text-sm sm:text-base">•••• •••• •••• 8890</p>
                </div>
            </div>

            <div class="space-y-3">
                <h2 class="text-3xl font-black text-white tracking-tight">The World in Your Wallet.</h2>
                <p class="text-xs font-bold text-slate-400 max-w-sm mx-auto leading-relaxed">
                    Virtual and physical USD/XOF cards are coming. Pay for Netflix, Apple Music, and global ads without limits.
                </p>
            </div>

            @if($isOnWaitlist)
                <div class="inline-flex items-center gap-2 px-6 py-3 bg-[#29B475]/20 border border-[#29B475]/30 rounded-2xl text-[#29B475]">
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#29B475] opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-[#29B475]"></span>
                    </span>
                    <span class="text-[10px] font-black uppercase tracking-widest">You're on the VIP Waitlist</span>
                </div>
            @else
                <button wire:click="joinWaitlist" class="px-8 py-4 bg-white text-slate-900 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-[0_0_40px_rgba(255,255,255,0.3)] hover:scale-105 transition-all active:scale-95">
                    Join the Waitlist
                </button>
            @endif

        </div>
    </div>
</div>