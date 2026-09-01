<div class="max-w-2xl mx-auto py-8 px-4 space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 hover:scale-105 transition-all">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Settings</h1>
            <p class="text-[10px] font-black text-[#158987] uppercase tracking-[0.2em] mt-1">Profile & Security</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="bg-[#e8f6f0] border border-[#29B475]/30 text-[#29B475] px-4 py-3 rounded-2xl text-xs font-black shadow-sm flex items-center gap-3 animate-in slide-in-from-top-2">
            <div class="w-6 h-6 rounded-full bg-[#29B475] text-white flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden">
        <div class="p-6 sm:p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center shrink-0 shadow-inner">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <div>
                    <h3 class="font-black text-slate-900 uppercase tracking-tight">Personal Details</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">How you appear to others</p>
                </div>
            </div>

            <form wire:submit.prevent="updateProfile" class="space-y-6">
                <div class="flex items-center gap-6">
                    <div class="relative group cursor-pointer">
                        <div class="w-20 h-20 rounded-[1.5rem] bg-slate-100 border-2 border-slate-200 overflow-hidden shrink-0 shadow-inner relative">
                            @if ($photo) 
                                <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                            @elseif(Auth::user()->profile_photo_path)
                                <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#158987] to-[#29B475] text-white font-black text-2xl">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <label class="absolute -bottom-2 -right-2 w-8 h-8 bg-[#020617] rounded-full border-4 border-white flex items-center justify-center text-white cursor-pointer hover:scale-110 transition-transform shadow-lg">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <input type="file" wire:model.live="photo" class="hidden" accept="image/*">
                        </label>
                    </div>

                    <div class="flex-1 space-y-1">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest pl-1">KoriePay Tag</label>
                        <div class="flex items-center gap-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus-within:ring-2 focus-within:ring-[#158987]/20 focus-within:border-[#158987] transition-all shadow-inner">
                            <span class="text-[#158987] font-black italic text-lg leading-none">@</span>
                            <input wire:model="username" type="text" class="bg-transparent border-none p-0 text-sm font-black text-slate-800 focus:ring-0 w-full" placeholder="username">
                        </div>
                        <x-input-error :messages="$errors->get('username')" class="mt-1 pl-1 text-[9px] text-red-500 font-bold uppercase" />
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="flex justify-between items-end mb-1 pl-1">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest">Full Legal Name</label>
                        @if($kyc_status === 'verified')
                            <span class="text-[8px] font-black text-[#29B475] bg-[#29B475]/10 px-2 py-0.5 rounded uppercase flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg> Locked by ID
                            </span>
                        @endif
                    </div>
                    <input wire:model="name" type="text" {{ $kyc_status === 'verified' ? 'disabled' : '' }} class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-black text-slate-800 focus:ring-[#158987] transition-all shadow-inner disabled:opacity-50 disabled:cursor-not-allowed">
                    <x-input-error :messages="$errors->get('name')" class="mt-1 pl-1 text-[9px] text-red-500 font-bold uppercase" />
                </div>

                <button type="submit" wire:loading.attr="disabled" class="w-full py-4 bg-[#020617] text-white rounded-xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="updateProfile">Save Changes</span>
                    <span wire:loading wire:target="updateProfile">Saving...</span>
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden p-6 sm:p-8">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-10 h-10 rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center text-orange-500 shrink-0 shadow-inner">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div>
                <h3 class="font-black text-slate-900 uppercase tracking-tight">Security</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Protect your money</p>
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-slate-200 transition-colors">
                <div>
                    <p class="text-sm font-black text-slate-900">Hide Balance</p>
                    <p class="text-[10px] font-bold text-slate-500 mt-0.5">Blur balance on login automatically</p>
                </div>
                <button wire:click="togglePreference('hide_balance')" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $hide_balance ? 'bg-[#29B475]' : 'bg-slate-300' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $hide_balance ? 'translate-x-6 shadow-sm' : 'translate-x-1 shadow-md' }}"></span>
                </button>
            </div>

            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-slate-200 transition-colors">
                <div>
                    <p class="text-sm font-black text-slate-900">FaceID / Fingerprint</p>
                    <p class="text-[10px] font-bold text-slate-500 mt-0.5">Use biometrics to open app</p>
                </div>
                <button wire:click="togglePreference('enable_biometrics')" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $enable_biometrics ? 'bg-[#29B475]' : 'bg-slate-300' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $enable_biometrics ? 'translate-x-6 shadow-sm' : 'translate-x-1 shadow-md' }}"></span>
                </button>
            </div>

            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-slate-200 transition-colors mb-4">
                <div>
                    <p class="text-sm font-black text-slate-900">Two-Factor Auth (2FA)</p>
                    <p class="text-[10px] font-bold text-slate-500 mt-0.5">Require code on new devices</p>
                </div>
                <button wire:click="togglePreference('enable_2fa')" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $enable_2fa ? 'bg-[#29B475]' : 'bg-slate-300' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $enable_2fa ? 'translate-x-6 shadow-sm' : 'translate-x-1 shadow-md' }}"></span>
                </button>
            </div>

            <div class="pt-4 border-t border-slate-100 space-y-2">
                <button wire:click="$set('showPinModal', true)" class="w-full flex items-center justify-between p-4 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 transition-colors active:scale-[0.98]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#158987]/10 text-[#158987] flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <span class="text-sm font-black text-slate-900">{{ $hasPin ? 'Change Transaction PIN' : 'Create Transaction PIN' }}</span>
                    </div>
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>

                <a href="#" class="w-full flex items-center justify-between p-4 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 transition-colors active:scale-[0.98]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        </div>
                        <span class="text-sm font-black text-slate-900">Change Password</span>
                    </div>
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>

    <div class="bg-red-50 rounded-[2.5rem] border border-red-100 overflow-hidden p-6 sm:p-8 relative">
        <div class="absolute -right-10 -bottom-10 opacity-5 pointer-events-none">
            <svg class="w-48 h-48 text-red-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 relative z-10">
            <div class="space-y-1">
                <h3 class="font-black text-red-600 uppercase tracking-tight">Emergency Account Freeze</h3>
                <p class="text-[10px] font-bold text-red-500 max-w-[250px] leading-relaxed uppercase tracking-widest">
                    Lock your account instantly if you lose your phone or suspect fraud.
                </p>
            </div>
            
            <button 
                wire:click="toggleAccountLock"
                wire:confirm="Are you sure you want to freeze your account? You will be logged out."
                class="w-full sm:w-auto px-6 py-4 {{ $is_locked ? 'bg-emerald-600 shadow-emerald-600/30' : 'bg-red-600 shadow-red-600/30' }} text-white rounded-xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl active:scale-95 transition-all whitespace-nowrap">
                {{ $is_locked ? 'Unfreeze Account' : 'Freeze Account' }}
            </button>
        </div>
    </div>

    @if($showPinModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" wire:click="$set('showPinModal', false)"></div>
            <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-sm relative z-10 overflow-hidden animate-in zoom-in-95 duration-200">
                <div class="p-8 text-center border-b border-slate-100 bg-slate-50">
                    <button @click="$wire.set('showPinModal', false)" class="absolute top-6 right-6 w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <div class="w-16 h-16 bg-[#158987]/10 text-[#158987] rounded-full flex items-center justify-center mx-auto mb-4 border border-[#158987]/20">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-900 tracking-tight">{{ $hasPin ? 'Change PIN' : 'Create PIN' }}</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">For secure transfers</p>
                </div>

                <div class="p-8">
                    <form wire:submit.prevent="setPin" class="space-y-6">
                        <div>
                            <input wire:model="pin" type="password" inputmode="numeric" maxlength="4" autofocus
                                   class="w-full text-center px-4 py-4 bg-slate-50 border border-slate-200 rounded-xl text-3xl font-black font-mono tracking-[0.5em] text-[#158987] focus:ring-[#158987] shadow-inner placeholder-slate-300" placeholder="••••">
                            <x-input-error :messages="$errors->get('pin')" class="mt-2 text-[10px] text-red-500 font-bold uppercase text-center" />
                        </div>
                        <div>
                            <input wire:model="pin_confirmation" type="password" inputmode="numeric" maxlength="4"
                                   class="w-full text-center px-4 py-4 bg-slate-50 border border-slate-200 rounded-xl text-3xl font-black font-mono tracking-[0.5em] text-[#158987] focus:ring-[#158987] shadow-inner placeholder-slate-300" placeholder="Confirm ••••">
                        </div>
                        <button type="submit" wire:loading.attr="disabled" class="w-full py-4 bg-[#158987] text-white rounded-xl text-[10px] font-black uppercase tracking-[0.2em] shadow-lg shadow-[#158987]/30 hover:bg-[#10706e] transition-all">
                            <span wire:loading.remove wire:target="setPin">Save PIN</span>
                            <span wire:loading wire:target="setPin">Saving...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>