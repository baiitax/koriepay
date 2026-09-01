<div class="p-4 lg:p-8 max-w-[1200px] mx-auto">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-slate-900 leading-tight">{{ __('Compliance & KYC Queue') }}</h1>
            <p class="text-slate-500 font-bold text-sm mt-1">{{ __('Review and verify regional operator applications.') }}</p>
        </div>
        <div class="bg-orange-50 px-4 py-2.5 rounded-xl border border-orange-100 flex items-center gap-3">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-orange-500"></span>
            </span>
            <span class="text-[10px] font-black text-orange-600 uppercase tracking-widest">{{ $pendingAgents->total() }} Pending Reviews</span>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-700 font-bold text-sm shadow-sm transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-700 font-bold text-sm shadow-sm transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse($pendingAgents as $agent)
        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 group">
            
            <div class="flex items-start gap-4 w-full lg:w-1/3">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center font-black text-slate-500 text-lg shadow-inner">
                    {{ substr($agent->name, 0, 1) }}
                </div>
                <div>
                    <h3 class="font-black text-slate-900 text-lg group-hover:text-emerald-600 transition-colors">{{ $agent->name }}</h3>
                    <p class="text-xs font-bold text-slate-400">ID: #{{ str_pad($agent->id, 5, '0', STR_PAD_LEFT) }} • Joined {{ $agent->created_at->diffForHumans() }}</p>
                </div>
            </div>

            <div class="w-full lg:w-1/3 flex flex-col gap-1 border-l-2 border-slate-50 pl-6">
                <p class="text-sm font-bold text-slate-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    {{ $agent->email }}
                </p>
                <p class="text-sm font-bold text-slate-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    {{ $agent->phone_number }}
                </p>
            </div>

            <div class="w-full lg:w-auto flex items-center gap-3 justify-end">
                <button 
                    wire:click="reject({{ $agent->id }})" 
                    wire:loading.attr="disabled"
                    wire:target="reject({{ $agent->id }})"
                    class="px-6 py-3 bg-white border-2 border-red-100 text-red-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-50 hover:border-red-200 transition-all focus:outline-none disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="reject({{ $agent->id }})">Reject</span>
                    <span wire:loading wire:target="reject({{ $agent->id }})">Wait...</span>
                </button>

                <button 
                    wire:click="approve({{ $agent->id }})" 
                    wire:loading.attr="disabled"
                    wire:target="approve({{ $agent->id }})"
                    class="px-6 py-3 bg-emerald-600 border-2 border-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-500 hover:border-emerald-500 shadow-lg shadow-emerald-500/20 transition-all focus:outline-none disabled:opacity-50 flex items-center gap-2"
                >
                    <span wire:loading.remove wire:target="approve({{ $agent->id }})">
                        <svg class="w-3 h-3 inline-block -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> 
                        Approve Agent
                    </span>
                    <span wire:loading wire:target="approve({{ $agent->id }})">Authorizing...</span>
                </button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-[2rem] border border-slate-200 border-dashed p-12 text-center flex flex-col items-center justify-center">
            <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mb-4 border border-emerald-100">
                <svg class="w-10 h-10 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-xl font-black text-slate-900">{{ __('Queue Clear') }}</h3>
            <p class="text-sm font-bold text-slate-500 mt-2 max-w-sm">{{ __('There are no pending agent registrations in your territory at this time.') }}</p>
            <a href="{{ route('manager.dashboard') }}" wire:navigate class="mt-6 px-6 py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition-colors">
                Return to Dashboard
            </a>
        </div>
        @endforelse

        @if($pendingAgents->hasPages())
            <div class="mt-6 p-4">
                {{ $pendingAgents->links() }}
            </div>
        @endif
    </div>
</div>