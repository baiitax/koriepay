<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password securely.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        // TIER-1 FIX: Safely check the hash directly since we use Phone Logins
        if (! Hash::check($this->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'The provided cryptographic key is incorrect.',
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        // Ensure dynamic fallback routing just in case
        $role = strtolower(trim(Auth::user()->role));
        $fallbackRoute = match($role) {
            'superadmin', 'admin' => route('admin.dashboard'),
            'manager'             => route('manager.dashboard'),
            'regional_agent'      => route('regional.dashboard'),
            'agent'               => route('agent.dashboard'),
            'customer'            => route('customer.dashboard'),
            default               => '/',
        };

        $this->redirectIntended(default: $fallbackRoute, navigate: true);
    }
}; ?>

<div class="min-h-screen w-full flex flex-col lg:flex-row bg-slate-50 relative overflow-hidden font-sans">
    
    <div class="absolute inset-0 bg-[url('/img/grid-pattern.svg')] opacity-[0.03] pointer-events-none"></div>
    <div class="absolute top-0 left-0 w-[500px] h-[500px] bg-[#158987]/10 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="w-full lg:w-[45%] relative z-10 flex flex-col justify-center p-8 lg:p-16 xl:p-24 min-h-[30vh] lg:min-h-screen bg-white border-r border-slate-200 shadow-[4px_0_24px_rgba(0,0,0,0.02)]">
        <div class="max-w-md mx-auto w-full">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-[#158987] to-[#29B475] rounded-2xl text-white text-2xl font-black mb-10 shadow-[0_8px_20px_rgba(21,137,135,0.25)]">
                K
            </div>
            
            <h1 class="text-4xl sm:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-6">
                Secure <span class="text-[#158987]">Zone.</span>
            </h1>
            
            <p class="text-lg font-bold text-slate-500 leading-relaxed mb-12">
                You are entering a highly restricted area of your KoriePay vault. Please re-authenticate to continue.
            </p>
            
            <div class="space-y-4 hidden md:block">
                <div class="flex items-start gap-4 p-5 rounded-2xl border border-slate-100 bg-slate-50">
                    <div class="w-10 h-10 rounded-full bg-[#158987]/10 flex items-center justify-center text-[#158987] shrink-0 mt-0.5 shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 mb-1">Zero-Trust Architecture</p>
                        <p class="text-xs font-bold text-slate-500 leading-relaxed">We require periodic re-verification to prevent unauthorized actions within your treasury.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full lg:w-[55%] flex items-center justify-center p-6 sm:p-12 relative z-10 overflow-y-auto no-scrollbar">
        <div class="w-full max-w-md bg-white border border-slate-200 rounded-[2.5rem] shadow-xl shadow-slate-200/50 p-8 sm:p-12 relative overflow-hidden">
            
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-1.5 bg-gradient-to-r from-[#158987] to-[#29B475] rounded-b-xl"></div>

            <div class="mb-8 text-center sm:text-left">
                <div class="w-16 h-16 bg-[#158987]/10 text-[#158987] rounded-full flex items-center justify-center mb-6 border border-[#158987]/20 shadow-inner">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">Confirm Identity</h2>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-2">Enter your cryptographic key</p>
            </div>

            <form wire:submit="confirmPassword" class="space-y-6">
                
                <div x-data="{ showPass: false }">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Master Password</label>
                    <div class="relative">
                        <input wire:model="password" :type="showPass ? 'text' : 'password'" required autofocus
                               class="w-full px-5 py-4 pr-12 bg-slate-50 border border-slate-200 rounded-[1.25rem] text-lg font-mono font-black tracking-widest text-[#158987] focus:bg-white focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] transition-all shadow-inner placeholder:text-slate-300 placeholder:tracking-normal" 
                               placeholder="••••••••">
                        <button type="button" @click="showPass = !showPass" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#158987] focus:outline-none transition-colors">
                            <svg x-show="!showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg x-show="showPass" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-[10px] text-red-500 font-bold uppercase pl-1" />
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="w-full py-5 bg-gradient-to-r from-[#158987] to-[#29B475] text-white rounded-[1.25rem] text-[11px] font-black uppercase tracking-widest hover:opacity-90 transition-all active:scale-[0.98] shadow-lg shadow-[#158987]/20 flex justify-center items-center gap-2">
                        <span wire:loading.remove wire:target="confirmPassword">Grant Access</span>
                        <span wire:loading wire:target="confirmPassword" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Verifying...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>