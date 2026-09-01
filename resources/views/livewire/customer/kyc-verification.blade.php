<div class="max-w-2xl mx-auto py-8 px-4 animate-in fade-in zoom-in-95 duration-500" x-data="{ step: 'form' }">
    
    <div class="text-center mb-10">
        <div class="w-20 h-20 bg-gradient-to-br from-[#29B475] to-[#158987] rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-xl shadow-[#29B475]/30 border-4 border-white rotate-3">
            <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.744c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
        </div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Identity Node Activation</h1>
        <p class="text-sm font-bold text-slate-500 mt-3 max-w-md mx-auto leading-relaxed">
            Verify your identity to lift transaction limits and enable cross-border settlements in <span class="text-[#158987]">NGN</span> and <span class="text-[#29B475]">XOF</span>.
        </p>
    </div>

    <div class="bg-white rounded-[3rem] shadow-2xl border border-slate-100 p-8 sm:p-12 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-[#158987] via-[#29B475] to-[#FCCB1A]"></div>

        <form wire:submit.prevent="submitKyc" class="space-y-10">
            
            <div class="space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-[#020617] text-white flex items-center justify-center text-xs font-black shadow-lg shadow-slate-900/20">01</span>
                        <h3 class="font-black text-slate-900 uppercase tracking-tight">Government Credentials</h3>
                    </div>
                    <span class="text-[9px] font-black text-[#158987] bg-[#158987]/10 px-2 py-1 rounded-lg uppercase tracking-widest">{{ Auth::user()->country_code }} Region</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] pl-1">Document Type</label>
                        <select wire:model="document_type" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-800 focus:ring-[#158987] focus:border-[#158987] transition-all">
                            @if(Auth::user()->country_code === 'NGA')
                                <option value="nin">National ID (NIN)</option>
                                <option value="bvn">BVN Verification</option>
                                <option value="voters_card">Voter's Card</option>
                            @else
                                <option value="national_id">Carte d'identité nationale (Niger)</option>
                                <option value="passport">Passeport International</option>
                            @endif
                            <option value="drivers_license">Driver's License</option>
                        </select>
                        <x-input-error :messages="$errors->get('document_type')" class="mt-1 pl-1" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] pl-1">ID Serial Number</label>
                        <input wire:model="document_number" type="text" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-black font-mono focus:ring-[#158987] shadow-inner uppercase" placeholder="ABC123456789">
                        <x-input-error :messages="$errors->get('document_number')" class="mt-1 pl-1" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] pl-1">Front of ID Document</label>
                    <div class="relative group h-48 border-2 border-dashed border-slate-200 rounded-[2rem] flex flex-col items-center justify-center bg-slate-50 hover:bg-slate-100/50 hover:border-[#158987] transition-all cursor-pointer overflow-hidden">
                        <input wire:model="id_image" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        
                        @if($id_image)
                            <img src="{{ $id_image->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover z-0">
                            <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px] flex items-center justify-center z-20">
                                <span class="bg-white/20 border border-white/40 px-4 py-2 rounded-xl text-white text-[10px] font-black uppercase tracking-widest">Tap to Replace</span>
                            </div>
                        @else
                            <div class="text-center space-y-2">
                                <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center mx-auto text-slate-400 group-hover:text-[#158987] transition-colors">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/></svg>
                                </div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Click to scan document</p>
                            </div>
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('id_image')" class="mt-1 pl-1" />
                </div>
            </div>

            <div class="space-y-6">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                    <span class="w-8 h-8 rounded-xl bg-[#020617] text-white flex items-center justify-center text-xs font-black shadow-lg shadow-slate-900/20">02</span>
                    <h3 class="font-black text-slate-900 uppercase tracking-tight">Biometric Liveness</h3>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-8 bg-slate-50 p-6 rounded-[2rem] border border-slate-100">
                    <div class="relative">
                        <div class="w-32 h-32 rounded-full border-4 border-white shadow-xl overflow-hidden bg-slate-200">
                            @if($passport_photo)
                                <img src="{{ $passport_photo->temporaryUrl() }}" class="w-full h-full object-cover scale-110">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-400 animate-pulse">
                                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </div>
                            @endif
                        </div>
                        <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-[#29B475] rounded-full border-4 border-white flex items-center justify-center text-white shadow-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/></svg>
                        </div>
                    </div>

                    <div class="flex-1 space-y-4 text-center sm:text-left">
                        <div class="space-y-1">
                            <h4 class="font-black text-slate-900 tracking-tight">SmartSelfie™ Check</h4>
                            <p class="text-[10px] font-bold text-slate-500 leading-relaxed uppercase tracking-widest">Position your face within the frame. Ensure good lighting and remove glasses/hats.</p>
                        </div>
                        <div class="relative inline-block">
                            <button type="button" class="px-6 py-3 bg-white border-2 border-slate-200 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] text-slate-700 hover:border-[#158987] hover:text-[#158987] transition-all active:scale-95 shadow-sm">
                                Open Secure Camera
                            </button>
                            <input wire:model="passport_photo" type="file" accept="image/*" capture="user" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        </div>
                        <x-input-error :messages="$errors->get('passport_photo')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="pt-8 border-t border-slate-100">
                <div class="flex items-center gap-3 justify-center text-[#158987] mb-8">
                    <div class="w-8 h-8 rounded-full bg-[#158987]/10 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em]">Smile ID Secure Verification</span>
                </div>

                <button type="submit" wire:loading.attr="disabled" class="w-full py-5 bg-[#020617] text-white rounded-[2rem] text-[11px] font-black uppercase tracking-[0.3em] hover:bg-slate-800 transition-all shadow-2xl shadow-slate-900/40 flex justify-center items-center gap-4 group">
                    <span wire:loading.remove wire:target="submitKyc">Initialize Grid Identity</span>
                    <span wire:loading wire:target="submitKyc" class="flex items-center gap-3">
                        <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Pinging Smile ID...
                    </span>
                    <svg wire:loading.remove wire:target="submitKyc" class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </button>
                
                <div class="text-center mt-6">
                    <a href="{{ route('customer.dashboard') }}" wire:navigate class="text-[9px] font-black text-slate-400 hover:text-slate-900 uppercase tracking-[0.2em] transition-colors">
                        Register Later <span class="opacity-40 font-bold">(Restricted Limits Apply)</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="mt-10 flex items-center justify-center gap-6 opacity-30 grayscale hover:grayscale-0 hover:opacity-100 transition-all duration-700">
        <img src="https://smileidentity.com/wp-content/uploads/2020/12/smile-logo.png" class="h-4 sm:h-6 object-contain" alt="Smile ID">
        <div class="h-4 w-[1px] bg-slate-300"></div>
        <p class="text-[8px] font-black uppercase tracking-widest text-slate-500">Regulated by CBN & BCEAO</p>
    </div>
</div>