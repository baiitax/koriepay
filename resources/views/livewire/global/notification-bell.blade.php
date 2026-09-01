<div class="relative" x-data="{ open: false }">
    <button @click="open = !open; if(open) $wire.markAsRead()" class="relative p-2 text-slate-400 hover:text-[#158987] transition-colors">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if($unreadCount > 0)
            <span class="absolute top-1.5 right-1.5 w-4 h-4 bg-red-500 text-white text-[9px] font-black rounded-full flex items-center justify-center border-2 border-white animate-bounce">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" @click.away="open = false" 
         class="absolute right-0 mt-3 w-80 bg-white rounded-[2rem] shadow-2xl border border-slate-100 overflow-hidden z-50 animate-in fade-in slide-in-from-top-2">
        
        <div class="p-5 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
            <h3 class="text-[10px] font-black text-slate-900 uppercase tracking-widest">Activity Grid</h3>
            <span class="text-[9px] font-bold text-slate-400">{{ $unreadCount }} New Alerts</span>
        </div>

        <div class="divide-y divide-slate-50 max-h-96 overflow-y-auto">
            @forelse($notifications as $n)
                <div class="p-4 flex gap-3 {{ $n->read_at ? 'opacity-60' : 'bg-[#158987]/5' }}">
                    <div class="w-8 h-8 rounded-full bg-white border border-slate-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-[#29B475]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <p class="text-[11px] font-black text-slate-900">{{ $n->data['title'] }}</p>
                        <p class="text-[10px] text-slate-500 leading-tight mt-0.5">{{ $n->data['message'] }}</p>
                        <p class="text-[8px] font-bold text-slate-400 uppercase mt-2">{{ $n->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center">
                    <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">No signals found</p>
                </div>
            @endforelse
        </div>

        <a href="#" class="block p-4 text-center text-[10px] font-black text-[#158987] uppercase tracking-widest bg-slate-50 border-t border-slate-100">
            View All Ledger Alerts
        </a>
    </div>
</div>