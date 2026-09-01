<div class="p-6 lg:p-12 space-y-8 bg-[#f8fafc] min-h-screen font-sans">
    
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center bg-white p-10 rounded-[3rem] border border-slate-200 shadow-sm gap-6">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">System Operational</span>
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight italic uppercase leading-none">Node Directory</h1>
            <p class="text-xs font-bold text-slate-400 mt-2 uppercase tracking-widest">Territory Management // {{ Auth::user()->country_code }} Grid</p>
        </div>
        
        <div class="flex gap-4 w-full lg:w-auto">
            <div class="relative flex-1 lg:w-80">
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Search Node ID/Identity..." 
                    class="w-full bg-slate-50 border-none rounded-2xl py-4 pl-12 text-sm font-bold focus:ring-2 focus:ring-emerald-500 transition-all">
                <svg class="absolute left-4 top-4.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>
            <button wire:click="$set('showDeployModal', true)" class="bg-slate-900 text-white px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-2xl hover:bg-emerald-600 transition-all active:scale-95">
                Deploy Terminal
            </button>
        </div>
    </div>

    <div class="relative">
        <div wire:loading.flex wire:target="search" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-20 items-center justify-center rounded-[3.5rem]">
            <div class="flex flex-col items-center gap-4">
                <div class="w-12 h-12 border-4 border-slate-200 border-t-emerald-500 rounded-full animate-spin"></div>
                <p class="text-[10px] font-black text-slate-900 uppercase tracking-widest">Syncing Grid...</p>
            </div>
        </div>

        <div class="bg-white rounded-[3.5rem] border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100 text-[9px] font-black text-slate-400 uppercase tracking-[0.25em]">
                    <tr>
                        <th class="px-10 py-7">Operator Node</th>
                        <th class="px-10 py-7 text-center">Protocol Status</th>
                        <th class="px-10 py-7 text-right">Liquidity Base</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($agents as $agent)
                        @php 
                            $primaryWallet = $agent->wallets->first();
                            $balance = $primaryWallet->balance ?? 0;
                            $isCritical = $balance < $lowBalanceThreshold;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-all group">
                            <td class="px-10 py-7">
                                <div class="flex items-center gap-5">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center font-black text-slate-400 group-hover:bg-slate-900 group-hover:text-white transition-all">
                                        {{ substr($agent->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-900 text-sm uppercase tracking-tight">{{ $agent->name }}</p>
                                        <p class="text-[10px] font-bold text-slate-400 font-mono mt-1">ID: #{{ str_pad($agent->id, 5, '0', STR_PAD_LEFT) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-10 py-7">
                                <div class="flex items-center justify-center gap-4">
                                    @if($isCritical)
                                        <div class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-xl border border-red-100 animate-pulse">
                                            <span class="text-[9px] font-black uppercase tracking-widest">Critical Liquidity</span>
                                        </div>
                                        <button wire:click="openFunding({{ $agent->id }})" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase hover:bg-emerald-600 transition-all">
                                            Inject
                                        </button>
                                    @else
                                        <span class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-xl border border-emerald-100 text-[9px] font-black uppercase tracking-widest">
                                            Operational
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-10 py-7 text-right">
                                <p class="text-xl font-black font-mono tracking-tighter {{ $isCritical ? 'text-red-600' : 'text-slate-900' }}">
                                    {{ number_format($balance, 2) }}
                                </p>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">{{ $primaryWallet->currency_code ?? '' }} Assets</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-10 py-32 text-center">
                                <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.5em]">No nodes detected in active sweep</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
            @if($agents->hasPages())
                <div class="px-10 py-6 bg-slate-50/50 border-t border-slate-100">
                    {{ $agents->links() }}
                </div>
            @endif
        </div>
    </div>

    <div x-data="{ open: @entangle('showDeployModal') }" x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-6">
        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-950/90 backdrop-blur-xl"></div>
        <div x-show="open" x-transition.scale class="bg-white rounded-[4rem] w-full max-w-xl p-14 shadow-2xl relative z-10 border border-slate-200">
            <h2 class="text-3xl font-black text-slate-900 italic uppercase mb-10 tracking-tight">Provision Terminal</h2>
            <form wire:submit.prevent="deployNode" class="space-y-6">
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Full Legal Identity</label>
                    <input wire:model="name" type="text" class="w-full bg-slate-50 border-none rounded-2xl p-5 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Secure Email</label>
                        <input wire:model="email" type="email" class="w-full bg-slate-50 border-none rounded-2xl p-5 font-bold focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Phone Gateway</label>
                        <input wire:model="phone" type="text" class="w-full bg-slate-50 border-none rounded-2xl p-5 font-bold focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
                <div class="flex gap-4 pt-10">
                    <button type="button" @click="open = false" class="flex-1 py-5 text-slate-400 font-black text-[10px] uppercase tracking-widest">Abort</button>
                    <button type="submit" class="flex-[2] py-5 bg-slate-900 text-white rounded-3xl font-black text-[10px] uppercase tracking-[0.2em] shadow-2xl shadow-slate-900/40">Initialize Deployment</button>
                </div>
            </form>
        </div>
    </div>

    <div x-data="{ open: @entangle('showSuccessModal') }" x-show="open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-6">
        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/95 backdrop-blur-2xl"></div>
        <div x-show="open" x-transition.scale class="bg-slate-900 border border-emerald-500/20 rounded-[4rem] w-full max-w-md p-14 text-center shadow-2xl">
            <div class="w-24 h-24 bg-emerald-500/10 text-emerald-500 rounded-3xl flex items-center justify-center mx-auto mb-8">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Terminal Ready</h3>
            <p class="text-slate-500 text-[10px] font-bold uppercase tracking-[0.3em] mb-12">Security Handshake Complete</p>
            
            <div class="bg-slate-800/50 p-6 rounded-3xl border border-slate-700 text-left mb-10">
                <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-2">Node Passkey</p>
                <p class="text-emerald-400 font-mono font-black text-2xl tracking-widest">{{ $deploymentResult['key'] ?? 'N/A' }}</p>
            </div>
            
            <button @click="open = false" class="w-full py-6 bg-emerald-600 text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.3em] shadow-xl shadow-emerald-500/20">Finalize Link</button>
        </div>
    </div>
</div>