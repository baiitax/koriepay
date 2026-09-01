<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        // If already verified, route them to their proper dashboard
        if ($user->hasVerifiedEmail()) {
            $role = strtolower(trim($user->role));
            $route = match($role) {
                'superadmin', 'admin' => route('admin.dashboard'),
                'manager'             => route('manager.dashboard'),
                'regional_agent'      => route('regional.dashboard'),
                'agent'               => route('agent.dashboard'),
                'customer'            => route('customer.dashboard'),
                default               => '/',
            };

            $this->redirectIntended(default: $route, navigate: true);
            return;
        }

        // TIER-1 FIX: Rate Limiting to prevent email spam/API abuse
        $throttleKey = 'resend-verification-' . $user->id;
        
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Session::flash('error', "Rate limit exceeded. Please wait {$seconds} seconds.");
            return;
        }

        $user->sendEmailVerificationNotification();
        RateLimiter::hit($throttleKey, 60); // Lock for 60 seconds

        Session::flash('status', 'verification-link-sent');
        
        // Dispatch event to trigger the Alpine.js timer in the UI
        $this->dispatch('email-resent'); 
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
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
                Verify Your <span class="text-[#158987]">Identity.</span>
            </h1>
            
            <p class="text-lg font-bold text-slate-500 leading-relaxed mb-12">
                As a regulated financial institution, we must verify your email address to secure your account and prevent unauthorized access.
            </p>
            
            <div class="space-y-4 hidden md:block">
                <div class="flex items-start gap-4 p-5 rounded-2xl border border-slate-100 bg-slate-50">
                    <div class="w-10 h-10 rounded-full bg-[#158987]/10 flex items-center justify-center text-[#158987] shrink-0 mt-0.5 shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 mb-1">Anti-Fraud Compliance</p>
                        <p class="text-xs font-bold text-slate-500 leading-relaxed">Email verification is step one of your KYC onboarding. It ensures you have secure access to recovery protocols.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full lg:w-[55%] flex items-center justify-center p-6 sm:p-12 relative z-10 overflow-y-auto no-scrollbar">
        <div class="w-full max-w-md bg-white border border-slate-200 rounded-[2.5rem] shadow-xl shadow-slate-200/50 p-8 sm:p-12 relative overflow-hidden">
            
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-1.5 bg-gradient-to-r from-[#158987] to-[#29B475] rounded-b-xl"></div>

            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-[#e8f6f0] text-[#29B475] rounded-[1.5rem] flex items-center justify-center mx-auto mb-6 border border-[#29B475]/20 shadow-sm rotate-3">
                    <svg class="w-10 h-10 -rotate-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                
                <h2 class="text-2xl font-black tracking-tight text-slate-900">Check Your Inbox</h2>
                <p class="text-sm font-bold text-slate-500 mt-3 leading-relaxed">
                    We've sent a secure verification link to:<br>
                    <span class="text-slate-900 font-black mt-1 inline-block px-3 py-1 bg-slate-50 rounded-lg border border-slate-200">{{ Auth::user()->email }}</span>
                </p>
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="mb-6 p-4 bg-[#e8f6f0] rounded-2xl border border-[#29B475]/20 flex items-center gap-3 animate-in fade-in zoom-in-95 duration-300">
                    <svg class="w-5 h-5 text-[#29B475] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <p class="text-[10px] font-black text-[#29B475] uppercase tracking-widest leading-tight">A new secure link has been sent.</p>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 bg-red-50 rounded-2xl border border-red-100 flex items-center gap-3 animate-in fade-in zoom-in-95 duration-300">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-[10px] font-black text-red-600 uppercase tracking-widest leading-tight">{{ session('error') }}</p>
                </div>
            @endif

            <div x-data="{ 
                    timer: 0, 
                    interval: null,
                    startTimer() {
                        clearInterval(this.interval);
                        this.timer = 60;
                        this.interval = setInterval(() => {
                            if(this.timer > 0) this.timer--;
                            else clearInterval(this.interval);
                        }, 1000);
                    }
                }" 
                @email-resent.window="startTimer()"
                class="space-y-4">
                
                <button wire:click="sendVerification" 
                        x-bind:disabled="timer > 0"
                        class="w-full py-5 rounded-[1.25rem] text-[11px] font-black uppercase tracking-widest transition-all flex justify-center items-center gap-3 shadow-lg"
                        :class="timer > 0 ? 'bg-slate-100 text-slate-400 cursor-not-allowed border border-slate-200 shadow-none' : 'bg-gradient-to-r from-[#158987] to-[#29B475] text-white hover:opacity-90 active:scale-[0.98] shadow-[#158987]/20'">
                    
                    <span wire:loading.remove wire:target="sendVerification" x-show="timer === 0">Resend Verification Email</span>
                    <span x-show="timer > 0" x-cloak>Resend available in <span x-text="timer"></span>s</span>
                    
                    <span wire:loading wire:target="sendVerification" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Sending...
                    </span>
                </button>

                <button wire:click="logout" type="button" class="w-full py-5 bg-white text-slate-500 border border-slate-200 rounded-[1.25rem] text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 hover:text-slate-900 transition-all active:scale-95 shadow-sm">
                    Switch Account / Log Out
                </button>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Can't find the email? Check your spam folder or ensure <span class="text-slate-600 font-black">support@koriepay.com</span> is whitelisted.
                </p>
            </div>
        </div>
    </div>
</div>