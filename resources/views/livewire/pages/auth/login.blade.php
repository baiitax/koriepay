<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Http; // Required for API Integration
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;

layout('layouts.guest'); 

// Notice we REMOVED expected_otp and temp_user_id from the public state!
state([
    'step' => 1,
    'phone' => '', 
    'password' => '',
    'otp' => '',
    'userRole' => ''
]);

// STEP 1: Verify Credentials & Send OTP
$authenticate = function () {
    $this->validate([
        'phone' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    // Strip non-numeric characters and remove leading zero
    $cleanPhone = ltrim(preg_replace('/[^0-9]/', '', $this->phone), '0');
    
    $throttleKey = Str::transliterate(Str::lower($cleanPhone).'|'.request()->ip());

    if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        throw ValidationException::withMessages([
            'phone' => "Security lock engaged. Try again in {$seconds}s.",
        ]);
    }

    // Find the user by matching the end of their registered phone number
    $user = User::where('phone_number', 'LIKE', '%' . $cleanPhone)->first();

    if (! $user || ! Hash::check($this->password, $user->password)) {
        RateLimiter::hit($throttleKey);
        throw ValidationException::withMessages([
            'phone' => 'Invalid phone number or password.',
        ]);
    }

    // Role verification
    $this->userRole = strtolower(trim($user->role));
    $validRoles = ['superadmin', 'admin', 'manager', 'regional_agent', 'agent', 'customer'];

    if (!in_array($this->userRole, $validRoles)) {
        RateLimiter::hit($throttleKey); 
        throw ValidationException::withMessages([
            'phone' => "Authentication failed: Account lacks recognized system clearance.",
        ]);
    }

    // =======================================================================
    // TIER-1 FIX: Generate OTP and store in SECURE SERVER SESSION, not Livewire state
    // =======================================================================
    
    $otpCode = '123456'; // TEMPORARY TEST OTP

    /*
    // -----------------------------------------------------------------------
    // PRODUCTION SMS API INTEGRATION (e.g., TERMII for NGA/NER)
    // Uncomment this block when you go live!
    // -----------------------------------------------------------------------
    
    $otpCode = (string) rand(100000, 999999); // Generate real random 6-digit code
    
    try {
        $fullPhoneNumber = $user->country_code === 'NGA' ? '234' . $cleanPhone : '227' . $cleanPhone;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://api.ng.termii.com/api/sms/send', [
            'to' => $fullPhoneNumber,
            'from' => 'KoriePay',
            'sms' => "Your KoriePay login code is {$otpCode}. Never share this code with anyone.",
            'type' => 'plain',
            'channel' => 'generic',
            'api_key' => env('TERMII_API_KEY'),
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to reach SMS gateway.");
        }
    } catch (\Exception $e) {
        throw ValidationException::withMessages([
            'phone' => 'Failed to send OTP. Please try again.',
        ]);
    }
    // -----------------------------------------------------------------------
    */

    // Store securely in session
    session()->put('auth_otp', $otpCode);
    session()->put('auth_user_id', $user->id);
    
    $this->step = 2;
};

// STEP 2: Verify OTP & Login
$verifyOtp = function () {
    $this->validate([
        'otp' => ['required', 'digits:6']
    ]);

    // Check against the secure server session
    if ($this->otp !== session('auth_otp')) {
        throw ValidationException::withMessages([
            'otp' => 'Invalid or expired security code.',
        ]);
    }

    // OTP matches, retrieve the user ID from the session and log them in
    $userId = session('auth_user_id');
    Auth::loginUsingId($userId);
    
    // Clean up secure session data
    session()->forget(['auth_otp', 'auth_user_id']);

    $cleanPhone = ltrim(preg_replace('/[^0-9]/', '', $this->phone), '0');
    $throttleKey = Str::transliterate(Str::lower($cleanPhone).'|'.request()->ip());
    RateLimiter::clear($throttleKey);
    
    session()->regenerate(); 

    // Dynamic Role-Based Routing
    return match($this->userRole) {
        'superadmin', 'admin' => redirect()->intended(route('admin.dashboard')),
        'manager'             => redirect()->intended(route('manager.dashboard')),
        'regional_agent'      => redirect()->intended(route('regional.dashboard')), 
        'agent'               => redirect()->intended(route('agent.dashboard')),
        'customer'            => redirect()->intended(route('customer.dashboard')),
        default               => redirect()->intended('/login')->withErrors(['phone' => 'Authentication failed: Unrecognized role.']),
    };
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
                Welcome back to <span class="text-[#158987]">KoriePay.</span>
            </h1>
            
            <p class="text-lg font-bold text-slate-500 leading-relaxed mb-12">
                Access your vaults, manage your liquidity, and settle cross-border transactions securely.
            </p>
            
            <div class="space-y-4 hidden md:block">
                <div class="flex items-start gap-4 p-5 rounded-2xl border border-slate-100 bg-slate-50">
                    <div class="w-10 h-10 rounded-full bg-[#158987]/10 flex items-center justify-center text-[#158987] shrink-0 mt-0.5 shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 mb-1">Two-Factor Authenticated</p>
                        <p class="text-xs font-bold text-slate-500 leading-relaxed">Every session is secured with OTP verification to ensure your funds are impenetrable.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full lg:w-[55%] flex items-center justify-center p-6 sm:p-12 relative z-10 overflow-y-auto no-scrollbar">
        <div class="w-full max-w-md bg-white border border-slate-200 rounded-[2.5rem] shadow-xl shadow-slate-200/50 p-8 sm:p-12 relative overflow-hidden">
            
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-1.5 bg-gradient-to-r from-[#158987] to-[#29B475] rounded-b-xl"></div>

            @if($step === 1)
                <div class="animate-in slide-in-from-right-4 duration-300">
                    <div class="mb-8 text-center sm:text-left">
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">Sign In</h2>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-2">Enter your credentials</p>
                    </div>

                    <form wire:submit="authenticate" class="space-y-5">
                        
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

                        <div x-data="{ showPass: false }">
                            <div class="flex justify-between items-center mb-2 pl-1 pr-1">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Password</label>
                                <a href="#" class="text-[10px] font-black text-[#158987] hover:text-[#29B475] uppercase tracking-widest transition-colors">Reset?</a>
                            </div>
                            <div class="relative">
                                <input wire:model="password" :type="showPass ? 'text' : 'password'" required
                                       class="w-full px-5 py-4 pr-12 bg-slate-50 border border-slate-200 rounded-2xl text-lg font-mono font-black tracking-widest text-[#158987] focus:bg-white focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] transition-all shadow-inner placeholder:text-slate-300 placeholder:tracking-normal" 
                                       placeholder="••••••••">
                                <button type="button" @click="showPass = !showPass" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#158987] focus:outline-none transition-colors">
                                    <svg x-show="!showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="showPass" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-5 mt-6 bg-slate-900 text-white rounded-[1.25rem] text-[11px] font-black uppercase tracking-widest hover:bg-slate-800 transition-all active:scale-[0.98] shadow-lg shadow-slate-900/20 flex justify-center items-center gap-3 group">
                            <span wire:loading.remove wire:target="authenticate">Continue</span>
                            <span wire:loading wire:target="authenticate" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Authenticating...
                            </span>
                            <svg wire:loading.remove wire:target="authenticate" class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </form>
                </div>

            @elseif($step === 2)
                <div class="animate-in slide-in-from-right-4 duration-300">
                    <div class="mb-8 text-center">
                        <div class="w-16 h-16 bg-[#158987]/10 text-[#158987] rounded-full flex items-center justify-center mx-auto mb-4 border border-[#158987]/20 shadow-inner">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <h2 class="text-2xl font-black tracking-tight text-slate-900">Security Check</h2>
                        <p class="text-xs font-bold text-slate-500 mt-2 leading-relaxed">We sent a 6-digit verification code to<br><span class="text-slate-900 font-black">*** *** **{{ substr($phone, -2) }}</span></p>
                        
                        <p class="text-[9px] font-black text-orange-500 uppercase tracking-widest mt-3 bg-orange-50 inline-block px-3 py-1 rounded-lg border border-orange-200">Test Mode: Use 123456</p>
                    </div>

                    <form wire:submit="verifyOtp" class="space-y-6">
                        
                        <div>
                            <input wire:model="otp" type="text" inputmode="numeric" maxlength="6" required autofocus
                                   class="w-full py-5 bg-slate-50 border border-slate-200 rounded-[1.25rem] text-4xl text-center font-mono font-black tracking-[0.4em] text-[#158987] focus:bg-white focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] shadow-inner transition-all placeholder:text-slate-300 placeholder:tracking-normal" 
                                   placeholder="••••••">
                            <x-input-error :messages="$errors->get('otp')" class="mt-3 text-[10px] text-red-500 font-bold uppercase text-center block" />
                        </div>

                        <div class="text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                Didn't receive it? <button type="button" class="text-[#158987] hover:text-[#29B475] transition-colors ml-1">Resend Code</button>
                            </p>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" wire:click="$set('step', 1)" class="flex-1 py-5 bg-white text-slate-600 border border-slate-200 rounded-[1.25rem] text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all active:scale-95 shadow-sm">Cancel</button>
                            <button type="submit" class="flex-[2] py-5 bg-gradient-to-r from-[#158987] to-[#29B475] text-white rounded-[1.25rem] text-[11px] font-black uppercase tracking-widest hover:opacity-90 transition-all active:scale-[0.98] shadow-lg shadow-[#158987]/20 flex justify-center items-center gap-2">
                                <span wire:loading.remove wire:target="verifyOtp">Verify & Enter</span>
                                <span wire:loading wire:target="verifyOtp" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Verifying...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if($step === 1)
                <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                        Don't have an account? 
                        <a href="{{ route('register') }}" class="text-[#158987] hover:text-[#29B475] font-black ml-1 transition-colors" wire:navigate>Sign Up Now</a>
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>