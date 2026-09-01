<?php

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password; 
use Illuminate\Support\Str;
use function Livewire\Volt\layout;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;

layout('layouts.auth');

state([
    'name' => '',
    'email' => '',
    'phone_number' => '',
    'country_code' => 'NGA',
    'password' => '',
    'password_confirmation' => '',
    // TIER-1: Automatically capture the '?ref=' parameter from the URL if it exists
    'referred_by_code' => request()->query('ref', ''), 
]);

rules([
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
    'phone_number' => ['required', 'string', 'max:20', 'unique:'.User::class],
    'country_code' => ['required', 'in:NGA,NER'],
    'referred_by_code' => ['nullable', 'string', 'exists:users,referral_code'], // Ensures the code is valid if entered
    'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()->symbols()->uncompromised()],
]);

$register = function () {
    $this->validate();

    // Find the referrer if a valid code was entered
    $referrerId = null;
    if (!empty($this->referred_by_code)) {
        $referrer = User::where('referral_code', strtoupper($this->referred_by_code))->first();
        if ($referrer) {
            $referrerId = $referrer->id;
        }
    }

    // Generate a unique referral code for this NEW user
    $namePrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $this->name), 0, 3));
    $newReferralCode = 'KP-' . $namePrefix . strtoupper(Str::random(4));

    $user = User::create([
        'name' => $this->name,
        'email' => $this->email,
        'phone_number' => $this->phone_number,
        'country_code' => $this->country_code,
        'password' => Hash::make($this->password),
        'role' => 'customer',
        'kyc_status' => 'pending',
        'is_active' => true,
        // TIER-1: Save the network data
        'referral_code' => $newReferralCode,
        'referrer_id' => $referrerId,
    ]);

    // Provision default wallets
    Wallet::create(['user_id' => $user->id, 'currency_code' => 'NGN', 'balance' => 0]);
    Wallet::create(['user_id' => $user->id, 'currency_code' => 'XOF', 'balance' => 0]);

    event(new Registered($user));

    Auth::login($user);

    $this->redirect(route('customer.kyc'), navigate: true);
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
                Welcome to <span class="text-[#158987]">KoriePay.</span>
            </h1>
            
            <p class="text-lg font-bold text-slate-500 leading-relaxed mb-12">
                Create your free account to unlock instant cross-border transfers, everyday utility payments, and secure community savings.
            </p>
            
            <div class="space-y-4 hidden md:block">
                <div class="flex items-start gap-4 p-5 rounded-2xl border border-slate-100 bg-slate-50">
                    <div class="w-10 h-10 rounded-full bg-[#158987]/10 flex items-center justify-center text-[#158987] shrink-0 mt-0.5 shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 mb-1">Bank-Grade Security</p>
                        <p class="text-xs font-bold text-slate-500 leading-relaxed">Your data and funds are protected with 256-bit encryption and strict regulatory compliance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full lg:w-[55%] flex items-center justify-center p-6 sm:p-12 relative z-10 overflow-y-auto no-scrollbar">
        <div class="w-full max-w-xl bg-white border border-slate-200 rounded-[2.5rem] shadow-xl shadow-slate-200/50 p-8 sm:p-12 relative">
            
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-1.5 bg-gradient-to-r from-[#158987] to-[#29B475] rounded-b-xl"></div>

            <div class="mb-8 text-center sm:text-left">
                <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">Create Account</h2>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-2">Get started in under 2 minutes</p>
            </div>

            <form wire:submit="register" class="space-y-5">
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Legal Full Name</label>
                    <input wire:model="name" type="text" required autofocus
                           class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] transition-all shadow-inner placeholder:text-slate-400" 
                           placeholder="First and Last Name">
                    <x-input-error :messages="$errors->get('name')" class="mt-2 text-[10px] text-red-500 font-bold uppercase pl-1" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Email Address</label>
                        <input wire:model="email" type="email" required
                               class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] transition-all shadow-inner placeholder:text-slate-400" 
                               placeholder="you@email.com">
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-[10px] text-red-500 font-bold uppercase pl-1" />
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Phone Number</label>
                        <input wire:model="phone_number" type="tel" required
                               class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] transition-all shadow-inner placeholder:text-slate-400" 
                               placeholder="+234...">
                        <x-input-error :messages="$errors->get('phone_number')" class="mt-2 text-[10px] text-red-500 font-bold uppercase pl-1" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Primary Region</label>
                        <div class="relative">
                            <select wire:model="country_code" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] transition-all shadow-inner appearance-none cursor-pointer">
                                <option value="NGA">🇳🇬 Nigeria (NGN)</option>
                                <option value="NER">🇳🇪 Niger (XOF)</option>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Invite Code <span class="text-slate-300 normal-case tracking-normal ml-1">(Optional)</span></label>
                        <input wire:model="referred_by_code" type="text"
                               class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-mono font-black text-[#29B475] uppercase focus:bg-white focus:ring-2 focus:ring-[#29B475]/20 focus:border-[#29B475] transition-all shadow-inner placeholder:text-slate-300 placeholder:font-sans placeholder:normal-case placeholder:font-bold" 
                               placeholder="e.g. KP-JD123">
                        <x-input-error :messages="$errors->get('referred_by_code')" class="mt-2 text-[10px] text-red-500 font-bold uppercase pl-1" />
                    </div>
                </div>

                <div x-data="{ 
                    pwd: '', showPass: false, showConf: false,
                    get hasLength() { return this.pwd.length >= 8; },
                    get hasNumber() { return /\d/.test(this.pwd); },
                    get hasSpecial() { return /[!@#$%^&*(),.?\':{}|<>\-_+=\/\\\[\]~]/.test(this.pwd); },
                    get isStrong() { return this.hasLength && this.hasNumber && this.hasSpecial; }
                }">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Set Password</label>
                            <div class="relative">
                                <input wire:model="password" x-model="pwd" :type="showPass ? 'text' : 'password'" required
                                       class="w-full px-5 py-4 pr-12 bg-slate-50 border rounded-2xl text-lg font-mono font-black tracking-widest text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] transition-all shadow-inner placeholder:text-slate-300 placeholder:tracking-normal"
                                       :class="isStrong ? 'border-[#29B475]' : 'border-slate-200'" placeholder="••••••••">
                                <button type="button" @click="showPass = !showPass" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#158987] focus:outline-none transition-colors">
                                    <svg x-show="!showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="showPass" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-[10px] text-red-500 font-bold uppercase pl-1" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Confirm Password</label>
                            <div class="relative">
                                <input wire:model="password_confirmation" :type="showConf ? 'text' : 'password'" required
                                       class="w-full px-5 py-4 pr-12 bg-slate-50 border border-slate-200 rounded-2xl text-lg font-mono font-black tracking-widest text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#158987]/20 focus:border-[#158987] transition-all shadow-inner placeholder:text-slate-300 placeholder:tracking-normal" placeholder="••••••••">
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
                    <span wire:loading.remove wire:target="register">Create My Account</span>
                    <span wire:loading wire:target="register" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Setting up...
                    </span>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                    Already have an account? 
                    <a href="{{ route('login') }}" class="text-[#158987] hover:text-[#29B475] font-black ml-1 transition-colors" wire:navigate>Sign In Here</a>
                </p>
            </div>
        </div>
    </div>
</div>