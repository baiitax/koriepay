<div x-data="{ contactModal: false }" 
     @close-contact-modal.window="contactModal = false"
     class="max-w-md mx-auto space-y-6 pb-24">

    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors active:scale-95">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Saved Contacts</h1>
        </div>
        <button @click="contactModal = true" class="w-10 h-10 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-md shadow-slate-900/20 hover:bg-slate-800 transition-colors active:scale-95">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        </button>
    </div>

    <div class="sticky top-[72px] bg-slate-50 z-20 py-2">
        <div class="relative group/search">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within/search:text-korie-paleblue transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search your beneficiaries..." 
                   class="block w-full pl-11 pr-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-korie-paleblue/20 focus:border-korie-paleblue transition-all placeholder:text-slate-400 shadow-sm">
        </div>
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($contacts as $contact)
                <div class="p-4 sm:p-5 flex items-center justify-between hover:bg-slate-50 transition-colors group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center font-black text-sm shrink-0 shadow-inner
                            {{ $contact->country_code === 'NGA' ? 'bg-korie-paleblue/10 text-korie-paleblue' : 'bg-korie-green/10 text-korie-green' }}">
                            {{ substr($contact->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $contact->name }}</p>
                            <p class="text-[10px] font-mono font-bold text-slate-500 mt-0.5">{{ $contact->tag }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <a href="{{ route('customer.send') }}" wire:navigate class="px-4 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition-all active:scale-95 shadow-sm">
                            Send
                        </a>
                        <button wire:click="removeContact({{ $contact->id }})" wire:confirm="Remove {{ $contact->name }} from contacts?" class="w-8 h-8 rounded-xl bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-100 transition-colors active:scale-95 sm:opacity-0 sm:group-hover:opacity-100">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-10 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-300 mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <p class="text-xs font-bold text-slate-900 tracking-tight">No saved contacts</p>
                    <p class="text-[10px] text-slate-500 mt-1 max-w-[200px]">Add frequent recipients to send money instantly without typing their details.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div x-show="contactModal" x-cloak class="relative z-50">
        <div x-show="contactModal" x-transition.opacity class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center text-center sm:items-center sm:p-0">
                <div x-show="contactModal" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95" 
                     @click.away="contactModal = false"
                     class="relative transform overflow-hidden bg-white text-left shadow-2xl transition-all w-full sm:my-8 sm:max-w-md rounded-t-[2.5rem] sm:rounded-[2.5rem]">
                    
                    <div class="px-6 py-8">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">Add New Contact</h3>
                            <button @click="contactModal = false" class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        @if($errorMessage)
                            <div class="bg-red-50 border border-red-100 text-red-600 text-[10px] font-bold p-3 rounded-xl mb-4 animate-pulse">
                                {{ $errorMessage }}
                            </div>
                        @endif

                        <form wire:submit="resolveAndAddContact" class="space-y-5">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest pl-1 mb-1.5">KorieTag, Email or Phone</label>
                                <input wire:model="newContactTag" type="text" placeholder="@username or +234..." required 
                                       class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-korie-paleblue/20 focus:border-korie-paleblue transition-all">
                                @error('newContactTag') <span class="text-red-500 text-[9px] font-bold block mt-1 pl-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest pl-1 mb-1.5">Nickname (Optional)</label>
                                <input wire:model="newContactNickname" type="text" placeholder="e.g. Lagos Supplier" 
                                       class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-korie-paleblue/20 focus:border-korie-paleblue transition-all">
                            </div>

                            <button type="submit" wire:loading.attr="disabled" class="w-full py-4 mt-2 bg-slate-900 text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] hover:bg-slate-800 transition-all active:scale-[0.98] shadow-lg shadow-slate-900/20 flex items-center justify-center gap-2">
                                <span wire:loading.remove wire:target="resolveAndAddContact">Save Contact</span>
                                <span wire:loading wire:target="resolveAndAddContact">Verifying Node...</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>