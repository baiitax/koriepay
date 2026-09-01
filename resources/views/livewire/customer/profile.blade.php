<div class="max-w-md mx-auto space-y-6 pb-32 animate-in fade-in slide-in-from-bottom-4 duration-500">

    <div class="flex items-center gap-4 mb-4 px-1">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight leading-none">Node Profile</h1>
    </div>

    <div class="bg-[#020617] rounded-[2.5rem] p-6 sm:p-8 relative overflow-hidden shadow-2xl text-white border border-slate-800">
        <div class="absolute -right-10 -top-10 w-48 h-48 bg-[#158987]/30 rounded-full blur-[60px] pointer-events-none"></div>
        <div class="absolute -bottom-10 -left-10 w-48 h-48 bg-[#29B475]/20 rounded-full blur-[60px] pointer-events-none"></div>

        <div class="relative z-10 flex items-center gap-5 mb-6">
            
            <div class="flex-col items-center flex shrink-0 group">
                <label for="photo" class="block relative cursor-pointer active:scale-95 transition-transform">
                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full border-4 border-white/20 shadow-xl overflow-hidden relative flex items-center justify-center">
                        
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                        @elseif ($user->profile_photo_path)
                            <img src="{{ asset('storage/' . $user->profile_photo_path) }}" class="w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 bg-slate-900 text-white flex items-center justify-center text-3xl font-black">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        @endif

                        <div class="absolute inset-0 bg-slate-950/60 flex items-center justify-center text-white text-[10px] font-black uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">
                            Change
                        </div>
                    </div>

                    <div class="absolute bottom-1.5 right-1.5 w-6 h-6 rounded-full border-4 border-[#020617] 
                        {{ $user->kyc_status === 'verified' ? 'bg-[#29B475]' : ($user->kyc_status === 'pending' ? 'bg-[#FCDB1A]' : 'bg-[#F88D25]') }}">
                    </div>
                </label>

                <input wire:model="photo" type="file" id="photo" class="hidden" accept="image/png, image/jpeg, image/jpg">
                
                @error('photo') 
                    <span class="text-[9px] text-red-400 font-bold uppercase tracking-widest pt-2 block">{{ $message }}</span> 
                @enderror
                
                @if ($photo)
                    <div class="pt-3 flex gap-2">
                        <button wire:click="savePhoto" wire:loading.attr="disabled" class="px-3 py-1 bg-[#29B475] text-white rounded-xl text-[9px] font-black uppercase tracking-widest shadow-lg flex justify-center gap-1.5 active:scale-95 disabled:opacity-70">
                            <span wire:loading.remove wire:target="savePhoto">Save photo</span>
                            <span wire:loading wire:target="savePhoto">Uploading...</span>
                            <svg wire:loading.remove wire:target="savePhoto" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        <button wire:click="$set('photo', null)" class="px-3 py-1 bg-white/10 text-slate-300 rounded-xl text-[9px] font-black uppercase tracking-widest active:scale-95">Cancel</button>
                    </div>
                @endif
            </div>
            
            <div class="flex-1 overflow-hidden">
                <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight leading-tight truncate">{{ $user->name }}</h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1.5 truncate">{{ $user->email }}</p>
                <div class="inline-flex items-center gap-2 mt-3 bg-white/10 px-3 py-1.5 rounded-xl border border-white/5 backdrop-blur-sm">
                    <span class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">Phone:</span>
                    <span class="text-xs font-mono font-black text-[#FCDB1A]">{{ $user->phone_number }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white/5 border border-white/10 rounded-3xl p-5 backdrop-blur-md">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1">Authorization Tier</p>
                    <h3 class="text-lg font-black tracking-tight flex items-center gap-2" style="color: {{ $tierInfo['color'] }};">
                        {{ $tierInfo['level'] }}
                        @if($user->kyc_status === 'verified')
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </h3>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1">Daily Limit</p>
                    <p class="text-sm font-mono font-black text-white">₦{{ number_format((float) $tierInfo['daily_limit']) }}</p>
                </div>
            </div>

            <a href="{{ $tierInfo['route'] !== '#' ? route($tierInfo['route']) : '#' }}" wire:navigate class="w-full py-3.5 bg-white/10 hover:bg-white/20 border border-white/10 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition-all active:scale-[0.98] flex items-center justify-center gap-2 group">
                <span class="text-white group-hover:text-[#29B475] transition-colors">{{ $tierInfo['action_text'] }}</span>
                <svg class="w-4 h-4 text-white group-hover:translate-x-1 group-hover:text-[#29B475] transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>

    <div>
        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 px-2">Account Configurations</h3>
        
        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
            <div class="divide-y divide-slate-100">
                
                <a href="{{ route('customer.security') }}" wire:navigate class="flex items-center justify-between p-4 sm:p-5 hover:bg-slate-50 transition-colors group active:bg-slate-100 block">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-[1rem] bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 group-hover:bg-[#158987] group-hover:text-white group-hover:border-[#158987] group-hover:shadow-[0_8px_20px_rgba(21,137,135,0.2)] transition-all">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Security & Access</p>
                            <p class="text-[10px] font-bold text-slate-400 mt-0.5 uppercase tracking-widest">Update PIN, Password & 2FA</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-300 group-hover:text-[#158987] transition-colors group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>

                <a href="{{ route('customer.vaults') }}" wire:navigate class="flex items-center justify-between p-4 sm:p-5 hover:bg-slate-50 transition-colors group active:bg-slate-100 block">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-[1rem] bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 group-hover:bg-[#29B475] group-hover:text-white group-hover:border-[#29B475] group-hover:shadow-[0_8px_20px_rgba(41,180,117,0.2)] transition-all">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Linked Vaults</p>
                            <p class="text-[10px] font-bold text-slate-400 mt-0.5 uppercase tracking-widest">Manage external bank accounts</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-300 group-hover:text-[#29B475] transition-colors group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>

                <a href="{{ route('customer.support') }}" wire:navigate class="flex items-center justify-between p-4 sm:p-5 hover:bg-slate-50 transition-colors group active:bg-slate-100 block">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-[1rem] bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 group-hover:bg-[#F88D25] group-hover:text-white group-hover:border-[#F88D25] group-hover:shadow-[0_8px_20px_rgba(248,141,37,0.2)] transition-all">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Agent Support</p>
                            <p class="text-[10px] font-bold text-slate-400 mt-0.5 uppercase tracking-widest">Contact KoriePay field agents</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-300 group-hover:text-[#F88D25] transition-colors group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>

            </div>
        </div>
    </div>

    <div class="hidden md:block pt-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full py-4 bg-white border border-red-200 text-red-500 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-red-50 hover:border-red-300 transition-all active:scale-[0.98] flex items-center justify-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Disconnect Node
            </button>
        </form>
    </div>

</div>