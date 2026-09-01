<div wire:poll.10s class="p-6 lg:p-12 space-y-8 bg-slate-50 min-h-screen">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-8 rounded-[3rem] border border-slate-200 shadow-sm gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em]">Live Feed Active</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">Regional Activity</h1>
        </div>
        
        <div class="bg-slate-900 px-6 py-3 rounded-2xl flex items-center gap-4 border border-slate-800">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Region Sweep</p>
            <p class="text-white font-mono font-bold text-sm">{{ $countryCode }} / GRID-01</p>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($activities as $activity)
            <div class="bg-white border border-slate-200 p-5 rounded-[2rem] shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row items-center gap-6 group">
                
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $activity->direction === 'in' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600' }}">
                    @if($activity->direction === 'in')
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                    @else
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                    @endif
                </div>

                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $activity->type }}</span>
                        <span class="text-[10px] text-slate-300">•</span>
                        <span class="text-[10px] font-mono font-bold text-slate-500">{{ $activity->reference }}</span>
                    </div>
                    <p class="text-sm font-black text-slate-900 mt-0.5">
                        {{ $activity->user->name ?? 'System Node' }} 
                        <span class="text-slate-400 font-medium">processed a {{ $activity->direction === 'in' ? 'deposit' : 'withdrawal' }}</span>
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-lg font-black font-mono tracking-tighter {{ $activity->direction === 'in' ? 'text-emerald-600' : 'text-slate-900' }}">
                        {{ $activity->direction === 'in' ? '+' : '-' }}{{ number_format($activity->amount, 2) }}
                    </p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $activity->created_at->diffForHumans() }}</p>
                </div>

                <a href="{{ route('manager.agent-detail', $activity->user_id) }}" wire:navigate class="p-3 bg-slate-50 text-slate-400 rounded-xl hover:bg-slate-900 hover:text-white transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </a>
            </div>
        @empty
            <div class="bg-white rounded-[3rem] p-20 border border-dashed border-slate-200 flex flex-col items-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">No Recent Network Traffic</p>
            </div>
        @endforelse
    </div>
</div>