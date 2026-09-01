<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;

layout('layouts.guest'); 

state([
    'step' => 1,
    'phone' => '',
    'otp' => '',
    'password' => '',
    'password_confirmation' => '',
]);

// STEP 1: Verify Phone & Send OTP
$sendOtp = function () {
    $this->validate([
        'phone' => ['required', 'string'],
    ]);

    $cleanPhone = ltrim(preg_replace('/[^0-9]/', '', $this->phone), '0');
    $throttleKey = 'reset_otp|' . request()->ip();

    if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        throw ValidationException::withMessages([
            'phone' => "Too many requests. Try again in {$seconds}s.",
        ]);
    }

    $user = User::where('phone_number', 'LIKE', '%' . $cleanPhone)->first();

    if (!$user) {
        RateLimiter::hit($throttleKey);
        throw ValidationException::withMessages([
            'phone' => 'No account found matching this phone number.',
        ]);
    }

    // Generate Secure OTP
    $otpCode = '123456'; // TEMPORARY DEV OTP
    
    /*
    // PRODUCTION SMS INTEGRATION
    $otpCode = (string) rand(100000, 999999);
    // Send via Termii / Twilio here...
    */

    session()->put('reset_otp', $otpCode);
    session()->put('reset_user_id', $user->id);
    
    $this->step = 2;
};

// STEP 2: Verify OTP
$verifyOtp = function () {
    $this->validate([
        'otp' => ['required', 'digits:6']
    ]);

    if ($this->otp !== session('reset_otp')) {
        throw ValidationException::withMessages([
            'otp' => 'Invalid or expired security code.',
        ]);
    }

    // OTP Verified, move to password reset
    $this->step = 3;
};

// STEP 3: Save New Password
$resetPassword = function () {
    $this->validate([
        'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()->symbols()->uncompromised()],
    ]);

    $userId = session('reset_user_id');
    
    if (!$userId) {
        // Session expired
        return $this->redirect(route('login'), navigate: true);
    }

    $user = User::find($userId);
    $user->password = Hash::make($this->password);
    $user->save();

    // Clear Secure Session
    session()->forget(['reset_otp', 'reset_user_id']);
    
    $this->step = 4; // Success Screen
};

?>

<div class="min-h-screen w-full flex flex-col lg:flex-row bg-slate-50 relative overflow-hidden font-sans">
    
    <div class="absolute inset-0 bg-[url('/img/grid-pattern.svg')] opacity-[0.03] pointer-events-none"></div>
    <div class="absolute top-0 left-0 w-[500px] h-[500px] bg-[#158987]/10 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="w-full lg:w-[45%] relative z-10 flex flex-col justify-center p-8 lg:p-16 xl:p-24 min-h-[30vh] lg:min-h-screen bg-white border-r border-slate-200 shadow-[4px_0_24px_rgba(0,0,0,0.02)]">
        <div class="max-w-md mx-auto w-full">
            <a href="/" wire:navigate class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-[#158987] to-[#29B475] rounded-2xl text-white text-2xl font-black mb-10 shadow-[0_8px_20px_rgba(21,137,135,0.25)] hover:scale-105 transition-transform">
                K
            </a>
            
            <h1 class="text-4xl sm:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-6">
                Account <span class="text-[#158987]">Recovery.</span>
            </h1>
            
            <p class="text-lg font-bold text-slate-500 leading-relaxed mb-12">
                Regain access to your KoriePay vaults securely. Verify your identity to set a new password.
            </p>
            
            <div class="space-y-4 hidden md:block">
                <div class="flex items-start gap-4 p-5 rounded-2xl border border-slate-100 bg-slate-50">
                    <div class="w-10 h-10 rounded-full bg-[#158987]/10 flex items-center justify-center text-[#158987] shrink-0 mt-0.5 shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 mb-1">Encrypted Reset Protocol</p>
                        <p class="text-xs font-bold text-slate-500 leading-relaxed">We use secure out-of-band OTP verification to ensure only you can alter your account credentials.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full lg:w-[55%] flex items-center justify-center p-6 sm:p-12 relative z-10 overflow-y-auto no-scrollbar">
        <div class="w-full max-w-md bg-white border border-slate-200 rounded-[2.5rem] shadow-xl shadow-slate-200/50 p-8 sm:p-12 relative overflow-hidden">
            
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-1.5 bg-gradient-to-r from-[#158987] to-[#29B475] rounded-b-xl transition-all duration-500" style="width: {{ ($step/4)*100 }}%"></div>

            @if($step === 1)
                <div class="animate-in slide-in-from-right-4 duration-300">
                    <div class="mb-8 text-center sm:text-left">
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">Forgot Password?</h2>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-2">Enter your registered number</p>
                    </div>

                    <form wire:submit="sendOtp" class="space-y-5">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Phone Number</label>
                            <div class="relative flex items-center bg-slate-50 border border-slate-200 rounded-2xl overflow-hidden focus-within:ring-2 focus-within:ring-[#158987]/20 focus-within:border-[#158987] shadow-inner transition-all">
                                <span class="pl-4 pr-2 text-sm font-black text-slate-400 border-r border-slate-200">+234 / +227</span>
                                <input wire:model="phone" type="tel" inputmode="numeric" required autofocus
                                       class="w-full bg-transparent border-none py-4 px-4 text-base font-black text-slate-900 focus:ring-0 placeholder:text-slate-300 placeholder:font-bold" 
                                       placeholder="803 000 0000">
                            </div>
                            <x-input-error :messages="$errors->get('phone')" class="mt-2 text-[10px] text-red-500 font-bold uppercase pl-1" />
                        </div>

                        <button type="submit" class="w-full py-5 mt-6 bg-slate-900 text-white rounded-[1.25rem] text-[11px] font-black uppercase tracking-widest hover:bg-slate-800 transition-all active:scale-[0.98] shadow-lg shadow-slate-900/20 flex justify-center items-center gap-3 group">
                            <span wire:loading.remove wire:target="sendOtp">Send Recovery Code</span>
                            <span wire:loading wire:target="sendOtp" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Sending...
                            </span>
                            <svg wire:loading.remove wire:target="sendOtp" class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </form>
                </div>

            @elseif($step === 2)
                <div class="animate-in slide-in-from-right-4 duration-300">
                    <div class="mb-8 text-center">
                        <div class="w-16 h-16 bg-[#158987]/10 text-[#158987] rounded-full flex items-center justify-center mx-auto mb-4 border border-[#158987]/20 shadow-inner">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <h2 class="text-2xl font-black tracking-tight text-slate-900">Verify Identity</h2>
                        <p class="text-xs font-bold text-slate-500 mt-2 leading-relaxed">Enter the 6-digit recovery code sent to<br><span class="text-slate-900 font-black">*** *** **{{ substr($phone, -2) }}</span></p>
                        <p class="text-[9px] font-black text-orange-500 uppercase tracking-widest mt-3 bg-orange-50 inline-block px-3 py-1 rounded-lg border border-orange-200">Test Mode: Use 123456</p>
                    </div>

                    <form wire:submit="verifyOtp" class="space-y-6">
                        <div>
                            <input wire:model="otp" type="text" inputmode="numeric" maxlength="6" required autofocus
                                   class="w-full py-5 bg-slate-50 border border-slate-200 rounded-[1.25rem] text-4xl text-center font-mono font-black tracking-[0.4em] text-[#158987] focus:bg-white focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] shadow-inner transition-all placeholder:text-slate-300 placeholder:tracking-normal" 
                                   placeholder="••••••">
                            <x-input-error :messages="$errors->get('otp')" class="mt-3 text-[10px] text-red-500 font-bold uppercase text-center block" />
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" wire:click="$set('step', 1)" class="flex-1 py-5 bg-white text-slate-600 border border-slate-200 rounded-[1.25rem] text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all active:scale-95 shadow-sm">Back</button>
                            <button type="submit" class="flex-[2] py-5 bg-gradient-to-r from-[#158987] to-[#29B475] text-white rounded-[1.25rem] text-[11px] font-black uppercase tracking-widest hover:opacity-90 transition-all active:scale-[0.98] shadow-lg shadow-[#158987]/20 flex justify-center items-center gap-2">
                                <span wire:loading.remove wire:target="verifyOtp">Confirm Code</span>
                                <span wire:loading wire:target="verifyOtp" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Verifying...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

            @elseif($step === 3)
                <div class="animate-in slide-in-from-right-4 duration-300">
                    <div class="mb-8 text-center sm:text-left">
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">Secure Account</h2>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-2">Create a new, strong password</p>
                    </div>

                    <form wire:submit="resetPassword" class="space-y-5">
                        <div x-data="{ 
                            pwd: '', showPass: false, showConf: false,
                            get hasLength() { return this.pwd.length >= 8; },
                            get hasNumber() { return /\d/.test(this.pwd); },
                            get hasSpecial() { return /[!@#$%^&*(),.?\':{}|<>\-_+=\/\\\[\]~]/.test(this.pwd); },
                            get isStrong() { return this.hasLength && this.hasNumber && this.hasSpecial; }
                        }">
                            <div class="space-y-5 mb-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">New Password</label>
                                    <div class="relative">
                                        <input wire:model="password" x-model="pwd" :type="showPass ? 'text' : 'password'" required autofocus
                                               class="w-full px-5 py-4 pr-12 bg-slate-50 border rounded-2xl text-lg font-mono font-black tracking-widest text-[#29B475] focus:bg-white focus:ring-2 focus:ring-[#29B475]/30 focus:border-[#29B475] transition-all shadow-inner placeholder:text-slate-300 placeholder:tracking-normal"
                                               :class="isStrong ? 'border-[#29B475]' : 'border-slate-200'" placeholder="••••••••">
                                        <button type="button" @click="showPass = !showPass" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#158987] focus:outline-none transition-colors">
                                            <svg x-show="!showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="showPass" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                        </button>
                                    </div>
                                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-[10px] text-red-500 font-bold uppercase pl-1" />
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Confirm New Password</label>
                                    <div class="relative">
                                        <input wire:model="password_confirmation" :type="showConf ? 'text' : 'password'" required
                                               class="w-full px-5 py-4 pr-12 bg-slate-50 border border-slate-200 rounded-2xl text-lg font-mono font-black tracking-widest text-[#29B475] focus:bg-white focus:ring-2 focus:ring-[#29B475]/30 focus:border-[#29B475] transition-all shadow-inner placeholder:text-slate-300 placeholder:tracking-normal" 
                                               placeholder="••••••••">
                                        <button type="button" @click="showConf = !showConf" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#158987] focus:outline-none transition-colors">
                                            <svg x-show="!showConf" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="showConf" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-x-4 gap-y-2 px-3 py-3 mb-2 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="flex items-center space-x-1.5 transition-colors duration-300" :class="hasLength ? 'text-[#29B475]' : 'text-slate-400'">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <span class="text-[9px] font-black uppercase tracking-widest">8+ Chars</span>
                                </div>
                                <div class="flex items-center space-x-1.5 transition-colors duration-300" :class="hasNumber ? 'text-[#29B475]' : 'text-slate-400'">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <span class="text-[9px] font-black uppercase tracking-widest">1 Number</span>
                                </div>
                                <div class="flex items-center space-x-1.5 transition-colors duration-300" :class="hasSpecial ? 'text-[#29B475]' : 'text-slate-400'">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <span class="text-[9px] font-black uppercase tracking-widest">1 Symbol</span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-5 mt-6 bg-gradient-to-r from-[#158987] to-[#29B475] text-white rounded-[1.25rem] text-[11px] font-black uppercase tracking-widest hover:opacity-90 transition-all active:scale-[0.98] shadow-lg shadow-[#158987]/20 flex justify-center items-center gap-3">
                            <span wire:loading.remove wire:target="resetPassword">Save New Password</span>
                            <span wire:loading wire:target="resetPassword" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Securing Vault...
                            </span>
                        </button>
                    </form>
                </div>

            @elseif($step === 4)
                <div class="p-8 text-center space-y-6 animate-in zoom-in-95 duration-500 mt-2">
                    <div class="w-24 h-24 bg-gradient-to-br from-[#29B475] to-[#158987] text-white rounded-[2.5rem] flex items-center justify-center mx-auto shadow-2xl shadow-[#29B475]/40 rotate-3 border-4 border-white">
                        <svg class="w-10 h-10 -rotate-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Vault Secured!</h2>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-2 px-4 leading-relaxed">Your password has been successfully reset.</p>
                    </div>

                    <a href="{{ route('login') }}" wire:navigate class="block w-full py-5 bg-slate-900 text-white hover:bg-slate-800 transition-colors rounded-[1.25rem] text-[10px] font-black uppercase tracking-widest shadow-lg active:scale-95 mt-4">
                        Return to Login
                    </a>
                </div>
            @endif

            @if(in_array($step, [1, 2, 3]))
                <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                        Remembered your password? 
                        <a href="{{ route('login') }}" class="text-[#158987] hover:text-[#29B475] font-black ml-1 transition-colors" wire:navigate>Sign In Here</a>
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>