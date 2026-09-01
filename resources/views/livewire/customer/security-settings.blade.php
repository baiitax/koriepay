<div class="max-w-md mx-auto space-y-6 pb-24 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('customer.profile') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors active:scale-95">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Security & Access</h1>
    </div>

    <form wire:submit="updatePassword" class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 space-y-5">
        
        @if (session()->has('success'))
            <div class="bg-korie-green/10 border border-korie-green/20 text-korie-green px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 mb-4 shadow-inner">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Current Password</label>
            <input wire:model="current_password" type="password" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-lg font-mono font-black tracking-[0.2em] text-[#158987] focus:bg-white focus:ring-0 focus:border-[#158987] transition-all shadow-inner placeholder:text-slate-300">
            @error('current_password') <span class="text-[10px] text-red-500 font-bold uppercase pl-1 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">New Password</label>
            <input wire:model="password" type="password" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-lg font-mono font-black tracking-[0.2em] text-[#158987] focus:bg-white focus:ring-0 focus:border-[#158987] transition-all shadow-inner">
            @error('password') <span class="text-[10px] text-red-500 font-bold uppercase pl-1 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Confirm New Password</label>
            <input wire:model="password_confirmation" type="password" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-lg font-mono font-black tracking-[0.2em] text-[#158987] focus:bg-white focus:ring-0 focus:border-[#158987] transition-all shadow-inner">
        </div>

        <button type="submit" class="w-full py-4 mt-2 bg-[#158987] text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-[#10706e] transition-all active:scale-[0.98] shadow-lg shadow-[#158987]/30 flex justify-center items-center gap-2">
            <span wire:loading.remove wire:target="updatePassword">Update Security Key</span>
            <span wire:loading wire:target="updatePassword">Encrypting...</span>
        </button>
    </form>

    <form wire:submit="updatePin" class="bg-[#020617] rounded-[2rem] border border-slate-800 shadow-xl p-6 space-y-5 text-white relative overflow-hidden mt-6 mb-6">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-[#29B475]/20 rounded-full blur-[40px] pointer-events-none"></div>
        
        <div class="flex items-center gap-3 mb-2 relative z-10">
            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $hasPin ? 'bg-[#29B475] text-[#020617]' : 'bg-[#29B475]/20 text-[#29B475]' }}">
                @if($hasPin)
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                @else
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                @endif
            </div>
            <div>
                <h3 class="text-sm font-black tracking-tight">Transaction PIN</h3>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $hasPin ? 'PIN is Active & Secured' : 'Set a 4-digit PIN for transfers' }}</p>
            </div>
        </div>

        @if (session()->has('pin_success'))
            <div class="relative z-10 bg-[#29B475]/20 border border-[#29B475]/30 text-[#29B475] px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 mb-2 shadow-inner">
                {{ session('pin_success') }}
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4 relative z-10">
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">{{ $hasPin ? 'New PIN' : 'Create PIN' }}</label>
                <input wire:model="pin" type="password" maxlength="4" placeholder="••••" required class="w-full px-5 py-4 bg-[#0f172a] border border-slate-700 rounded-2xl text-xl text-center font-mono font-black tracking-[0.3em] text-[#29B475] focus:ring-0 focus:border-[#29B475] transition-all shadow-inner">
                @error('pin') <span class="text-[9px] text-red-400 font-bold uppercase pl-1 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Confirm PIN</label>
                <input wire:model="pin_confirmation" type="password" maxlength="4" placeholder="••••" required class="w-full px-5 py-4 bg-[#0f172a] border border-slate-700 rounded-2xl text-xl text-center font-mono font-black tracking-[0.3em] text-[#29B475] focus:ring-0 focus:border-[#29B475] transition-all shadow-inner">
            </div>
        </div>

        <button type="submit" class="w-full py-4 mt-2 bg-[#29B475] text-[#020617] rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-[#239962] transition-all active:scale-[0.98] shadow-lg shadow-[#29B475]/20 flex justify-center items-center gap-2 relative z-10">
            <span wire:loading.remove wire:target="updatePin">{{ $hasPin ? 'Update Node PIN' : 'Secure Node PIN' }}</span>
            <span wire:loading wire:target="updatePin">Encrypting...</span>
        </button>
    </form>
    
    <div class="bg-slate-900 rounded-[2rem] p-6 text-white flex justify-between items-center shadow-lg border border-slate-800">
        <div>
            <p class="text-sm font-black tracking-tight">Two-Factor Auth</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Require OTP for transfers</p>
        </div>
        <div class="w-12 h-6 bg-[#29B475] rounded-full relative cursor-pointer shadow-inner">
            <div class="w-5 h-5 bg-white rounded-full absolute right-0.5 top-0.5 shadow-sm"></div>
        </div>
    </div>
</div>