<div class="max-w-md mx-auto space-y-6 pb-24 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Support <span class="text-[#158987]">Node</span></h1>
        <span class="px-3 py-1 bg-[#158987]/10 text-[#158987] rounded-full text-[9px] font-black uppercase tracking-widest">24/7 Active</span>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden p-8 space-y-6">
        <div class="space-y-4">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Issue Category</label>
                <div class="flex gap-2 overflow-x-auto pb-2 no-scrollbar">
                    @foreach(['General', 'Transaction', 'KYC', 'Security'] as $cat)
                        <button wire:click="$set('category', '{{ $cat }}')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shrink-0 border {{ $category === $cat ? 'bg-[#020617] text-white border-[#020617]' : 'bg-slate-50 text-slate-500 border-slate-100' }}">
                            {{ $cat }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <input wire:model="subject" type="text" placeholder="Subject of your request..." class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold shadow-inner focus:ring-[#158987]">
            </div>

            <div class="relative">
                <textarea wire:model.live.debounce.300ms="message" rows="4" placeholder="Describe the issue in detail..." class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold shadow-inner focus:ring-[#158987]"></textarea>
                
                @if($showAiSuggestion)
                    <div class="mt-3 p-4 bg-[#158987]/10 border border-[#158987]/20 rounded-2xl animate-in zoom-in-95">
                        <p class="text-[11px] font-bold text-[#158987] leading-relaxed">{{ $suggestionText }}</p>
                        @if(str_contains($suggestionText, 'Security'))
                            <a href="{{ route('customer.security') }}" wire:navigate class="mt-2 inline-block text-[9px] font-black uppercase tracking-widest bg-[#158987] text-white px-3 py-1.5 rounded-lg">Fix Now</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <button wire:click="createTicket" class="w-full py-4 bg-[#020617] text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-slate-800 transition-all flex justify-center items-center gap-2">
            Deploy Support Ticket
        </button>
    </div>

    <div class="space-y-4">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] pl-1">Your Support History</h3>
        
        @forelse($myTickets as $ticket)
            <div class="bg-white p-5 rounded-[2rem] border border-slate-200 shadow-sm flex items-center justify-between group hover:border-[#158987]/30 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-[#158987] border border-slate-100 group-hover:bg-[#158987] group-hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-black text-slate-900">{{ $ticket->subject }}</p>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $ticket->ticket_id }} • {{ $ticket->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest border 
                    {{ $ticket->status === 'open' ? 'bg-orange-50 text-orange-600 border-orange-200' : 'bg-green-50 text-green-600 border-green-200' }}">
                    {{ $ticket->status }}
                </span>
            </div>
        @empty
            <div class="text-center py-10 bg-slate-50 rounded-[2rem] border-2 border-dashed border-slate-200">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No active tickets</p>
            </div>
        @endforelse
    </div>
</div>