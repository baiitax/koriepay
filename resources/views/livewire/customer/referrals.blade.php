<div class="max-w-2xl mx-auto py-8 px-4 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Refer & Earn</h1>
            <p class="text-[9px] font-bold text-[#158987] uppercase tracking-widest">Growth Rewards Hub</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-[2rem] p-6 border border-slate-200 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Friends Invited</p>
            <p class="text-3xl font-black text-slate-900">{{ $totalReferred }}</p>
        </div>
        <div class="bg-[#e8f6f0] rounded-[2rem] p-6 border border-[#29B475]/20 shadow-sm">
            <p class="text-[9px] font-black text-[#29B475]/70 uppercase tracking-widest mb-1">Total Earned</p>
            <p class="text-3xl font-black text-[#29B475] font-mono">₦{{ number_format($totalEarned, 0) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-200 p-8 sm:p-10 text-center">
        <div class="w-16 h-16 bg-[#158987]/10 text-[#158987] rounded-full flex items-center justify-center mx-auto mb-6 border border-[#158987]/20">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
        </div>
        
        <h3 class="text-xl font-black text-slate-900 tracking-tight mb-2">Give ₦500, Get ₦500</h3>
        <p class="text-xs font-bold text-slate-500 mb-8 max-w-sm mx-auto leading-relaxed">
            Share your code. When a friend signs up and makes a transfer of ₦5,000 or more, you both get ₦500 instantly credited to your NGN Vault.
        </p>

        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 relative group max-w-sm mx-auto">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Your Unique Code</p>
            <p class="text-2xl font-black font-mono text-slate-900 tracking-[0.2em]">{{ $referralCode }}</p>
            
            <button onclick="navigator.clipboard.writeText('{{ $referralCode }}'); alert('Code copied!');" class="absolute right-4 top-1/2 -translate-y-1/2 text-[#158987] bg-white p-2 rounded-xl shadow-sm hover:scale-105 transition-transform">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </button>
        </div>
    </div>
</div>