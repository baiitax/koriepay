<div class="max-w-4xl mx-auto space-y-12 animate-in fade-in slide-in-from-bottom-6 duration-700 pb-20">

    <div class="flex items-center gap-6 px-4">
        <div class="w-16 h-16 bg-slate-900 rounded-[1.8rem] flex items-center justify-center text-blue-500 shadow-2xl">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div>
            <h1 class="text-3xl font-black text-slate-900 uppercase italic leading-none">Node Configuration</h1>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mt-2">Manage Agent Identity & Security Credentials</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mx-4 p-4 bg-emerald-900 text-emerald-100 rounded-2xl text-[10px] font-black uppercase tracking-widest border-2 border-emerald-500 animate-in zoom-in duration-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white rounded-[3rem] border border-slate-100 shadow-xl p-10 md:p-14">
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-10 italic">Terminal Identity</h3>
                
                <form wire:submit="updateProfile" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-4">Full Business Name</label>
                            <input wire:model="name" type="text" class="w-full px-6 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold outline-none focus:border-blue-600 transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-4">Agent ID (Username)</label>
                            <input wire:model="username" type="text" class="w-full px-6 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-mono font-bold outline-none focus:border-blue-600 transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-4">Registered Phone</label>
                            <input wire:model="phone" type="text" class="w-full px-6 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold outline-none focus:border-blue-600 transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-slate-300 uppercase tracking-widest ml-4 italic">Email (Locked)</label>
                            <input value="{{ $email }}" disabled class="w-full px-6 py-4 bg-slate-100 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-400 cursor-not-allowed">
                        </div>
                    </div>
                    
                    <button type="submit" class="px-10 py-5 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all active:scale-95 shadow-xl shadow-slate-200">
                        Commit Profile Updates
                    </button>
                </form>
            </div>
        </div>

        <div class="space-y-8">
            <div class="bg-slate-900 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl"></div>
                <h3 class="text-xs font-black text-blue-500 uppercase tracking-widest mb-8 italic">Credential Rotation</h3>
                
                <form wire:submit="updatePassword" class="space-y-6">
                    <input wire:model="current_password" type="password" placeholder="Current Password" class="w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-xs outline-none focus:border-blue-500 transition-all">
                    <input wire:model="new_password" type="password" placeholder="New Password" class="w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-xs outline-none focus:border-blue-500 transition-all">
                    <input wire:model="new_password_confirmation" type="password" placeholder="Confirm New Password" class="w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-xs outline-none focus:border-blue-500 transition-all">
                    
                    <button type="submit" class="w-full py-5 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-500 transition-all shadow-lg shadow-blue-500/20">
                        Update Security Key
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm text-center">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Node Compliance Status</p>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest">KYC Verified</span>
                </div>
                <p class="text-[8px] text-slate-400 mt-4 italic uppercase">Last security audit: {{ now()->subDays(2)->format('d M Y') }}</p>
            </div>
        </div>
    </div>
</div>