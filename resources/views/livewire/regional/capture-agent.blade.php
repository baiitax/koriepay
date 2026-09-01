<div class="min-h-screen bg-slate-50 font-sans selection:bg-[#29B475] selection:text-white p-6 lg:p-8 pb-24">
    
    <div class="max-w-4xl mx-auto mb-8">
        <a href="{{ route('regional.dashboard') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-900 transition-colors mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Territory Overview
        </a>
        <h1 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">Capture New Agent.</h1>
        <p class="text-sm font-medium text-slate-500 mt-1">Register a new liquidity operator into your regional network.</p>
    </div>

    <form wire:submit="capture" class="max-w-4xl mx-auto space-y-6">
        
        <div class="bg-white border border-slate-200 rounded-[1.5rem] shadow-sm p-6 lg:p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-black text-sm">1</div>
                <h2 class="text-lg font-black text-slate-900">Operator Details</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Legal First Name</label>
                    <input wire:model="first_name" type="text" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#29B475]/20 focus:border-[#29B475] transition-all placeholder:text-slate-400" placeholder="e.g. Chinedu">
                    @error('first_name') <span class="text-[11px] text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Legal Last Name</label>
                    <input wire:model="last_name" type="text" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#29B475]/20 focus:border-[#29B475] transition-all placeholder:text-slate-400" placeholder="e.g. Okafor">
                    @error('last_name') <span class="text-[11px] text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Primary Email</label>
                    <input wire:model="email" type="email" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#29B475]/20 focus:border-[#29B475] transition-all placeholder:text-slate-400" placeholder="agent@example.com">
                    @error('email') <span class="text-[11px] text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Phone Number (BVN Linked)</label>
                    <input wire:model="phone" type="tel" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#29B475]/20 focus:border-[#29B475] transition-all placeholder:text-slate-400" placeholder="+234...">
                    @error('phone') <span class="text-[11px] text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-[1.5rem] shadow-sm p-6 lg:p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-black text-sm">2</div>
                <h2 class="text-lg font-black text-slate-900">Business Structure</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Registered Business Name (or Trading Name)</label>
                    <input wire:model="business_name" type="text" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#29B475]/20 focus:border-[#29B475] transition-all placeholder:text-slate-400" placeholder="e.g. Okafor Global Ventures">
                    @error('business_name') <span class="text-[11px] text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Operating State</label>
                    <select wire:model="state_location" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#29B475]/20 focus:border-[#29B475] transition-all">
                        <option value="">Select Region...</option>
                        <option value="Lagos">Lagos</option>
                        <option value="Abuja">Abuja (FCT)</option>
                        <option value="Kano">Kano</option>
                        <option value="Rivers">Rivers</option>
                    </select>
                    @error('state_location') <span class="text-[11px] text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Agent Tier Level</label>
                    <select wire:model="agent_tier" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#29B475]/20 focus:border-[#29B475] transition-all">
                        <option value="tier_1">Tier 1 (Standard Outpost)</option>
                        <option value="tier_2">Tier 2 (High Volume Node)</option>
                        <option value="tier_3">Tier 3 (Master Aggregator)</option>
                    </select>
                    @error('agent_tier') <span class="text-[11px] text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl flex gap-3">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <h4 class="text-sm font-bold text-amber-800">KYC Requirement Notice</h4>
                    <p class="text-xs font-medium text-amber-700 mt-0.5">Capturing the agent will lock their status as <strong>Pending</strong>. They will not be able to process transactions until you upload their physical ID and utility bill in the KYC Pipeline.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4 pt-4">
            <button type="button" wire:click="reset" class="px-6 py-3.5 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-bold shadow-sm hover:bg-slate-50 transition-all">
                Clear Form
            </button>
            <button type="submit" class="px-8 py-3.5 bg-slate-900 text-white rounded-xl text-sm font-bold shadow-lg shadow-slate-900/20 hover:bg-slate-800 transition-all flex items-center justify-center gap-2 group active:scale-[0.99] min-w-[200px]">
                <span wire:loading.remove wire:target="capture">Authorize & Capture Agent</span>
                <span wire:loading wire:target="capture" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Processing...
                </span>
                <svg wire:loading.remove wire:target="capture" class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </div>

    </form>
</div>