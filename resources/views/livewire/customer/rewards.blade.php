<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.customer')] class extends Component
{
    public $referralCode;
    public $referralLink;
    public $currency;

    public function mount()
    {
        $user = Auth::user();
        $this->currency = $user->country_code === 'NER' ? 'XOF' : 'NGN';
        
        // TIER-1 TIP: If you don't have a referral_code column yet, we dynamically generate a beautiful one.
        $this->referralCode = $user->referral_code ?? 'KP-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $user->name), 0, 3)) . $user->id . 'X';
        
        // Generate the unique link
        $this->referralLink = route('register') . '?ref=' . $this->referralCode;
    }

    public function with(): array
    {
        // ========================================================================
        // MOCKED DATA FOR UI (Replace with your actual database queries later)
        // e.g., User::where('referrer_id', Auth::id())->latest()->get()
        // ========================================================================
        
        $rewardAmount = $this->currency === 'NGN' ? 1000 : 500;

        return [
            'totalEarned' => $rewardAmount * 5,
            'pendingRewards' => $rewardAmount * 2,
            'totalInvited' => 8,
            'rewardAmount' => $rewardAmount,
            'referrals' => [
                (object)['name' => 'Aisha Bello', 'created_at' => now()->subDays(1), 'status' => 'rewarded', 'amount' => $rewardAmount],
                (object)['name' => 'Moussa Oumarou', 'created_at' => now()->subDays(2), 'status' => 'pending_kyc', 'amount' => $rewardAmount],
                (object)['name' => 'Chinedu Okeke', 'created_at' => now()->subDays(4), 'status' => 'rewarded', 'amount' => $rewardAmount],
                (object)['name' => 'Fatima Diallo', 'created_at' => now()->subHours(5), 'status' => 'joined', 'amount' => 0],
            ]
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto space-y-8 pt-8 pb-24 px-4 sm:px-6 lg:px-8 animate-in fade-in slide-in-from-bottom-4 duration-500 font-sans">
    
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors active:scale-95">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Rewards Hub</h1>
                <p class="text-[10px] font-bold text-[#158987] uppercase tracking-widest mt-1">Grow the Grid, Earn Liquidity</p>
            </div>
        </div>
    </div>

    <div class="bg-[#020617] rounded-[2.5rem] border border-slate-800 shadow-2xl overflow-hidden relative">
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-[#29B475]/20 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-[#158987]/20 rounded-full blur-[80px] pointer-events-none"></div>

        <div class="p-8 sm:p-10 relative z-10 flex flex-col md:flex-row items-center gap-8 justify-between">
            
            <div class="w-full md:w-1/2 space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/10 border border-white/10 text-white backdrop-blur-sm">
                    <svg class="w-4 h-4 text-[#29B475]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                    <span class="text-[10px] font-black uppercase tracking-widest">Your Unique Code</span>
                </div>
                
                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                    Invite friends.<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#29B475] to-[#158987]">Earn {{ $currency === 'NGN' ? '₦' : 'CFA' }}{{ number_format($rewardAmount) }}.</span>
                </h2>
                <p class="text-xs font-bold text-slate-400 leading-relaxed max-w-sm">
                    Share your link. When your friend joins KoriePay and completes KYC, you both get a liquidity drop instantly.
                </p>

                <div x-data="{ copied: false }" class="mt-6 flex items-center bg-slate-900/80 border border-slate-700 p-2 rounded-2xl backdrop-blur-md">
                    <div class="px-4 py-2 w-full overflow-hidden">
                        <p class="text-lg font-mono font-black tracking-widest text-[#29B475] truncate">{{ $referralCode }}</p>
                    </div>
                    <button @click="navigator.clipboard.writeText('{{ $referralLink }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                            class="shrink-0 px-6 py-3.5 bg-white text-slate-900 hover:bg-slate-100 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all active:scale-95 shadow-lg flex items-center gap-2">
                        <span x-show="!copied">Copy Link</span>
                        <span x-show="copied" x-cloak class="text-[#158987] flex items-center gap-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Copied</span>
                    </button>
                </div>
            </div>

            <div class="w-full md:w-auto grid grid-cols-2 gap-4 shrink-0">
                <div class="bg-slate-800/40 border border-slate-700/50 p-6 rounded-3xl backdrop-blur-sm text-center">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Earned</p>
                    <p class="text-2xl font-mono font-black text-white">{{ $currency === 'NGN' ? '₦' : 'CFA' }}{{ number_format($totalEarned) }}</p>
                </div>
                <div class="bg-[#29B475]/10 border border-[#29B475]/20 p-6 rounded-3xl backdrop-blur-sm text-center">
                    <p class="text-[9px] font-black text-[#29B475] uppercase tracking-widest mb-2">Pending</p>
                    <p class="text-2xl font-mono font-black text-[#29B475]">{{ $currency === 'NGN' ? '₦' : 'CFA' }}{{ number_format($pendingRewards) }}</p>
                </div>
                <div class="col-span-2 bg-slate-800/40 border border-slate-700/50 p-5 rounded-3xl backdrop-blur-sm flex justify-between items-center">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Network Size</p>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#158987] animate-pulse"></span>
                        <p class="text-sm font-black text-white">{{ $totalInvited }} Users</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col items-center text-center">
            <div class="w-12 h-12 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mb-4 border border-slate-100">
                <span class="font-black text-lg">1</span>
            </div>
            <h3 class="text-sm font-black text-slate-900 mb-2">Share Your Link</h3>
            <p class="text-xs font-bold text-slate-500 leading-relaxed">Send your unique code to friends, family, or business partners.</p>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col items-center text-center">
            <div class="w-12 h-12 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mb-4 border border-slate-100">
                <span class="font-black text-lg">2</span>
            </div>
            <h3 class="text-sm font-black text-slate-900 mb-2">They Complete KYC</h3>
            <p class="text-xs font-bold text-slate-500 leading-relaxed">Your friend provisions their account and completes Tier-1 verification.</p>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-[#29B475]/30 shadow-sm shadow-[#29B475]/5 flex flex-col items-center text-center relative overflow-hidden">
            <div class="absolute top-0 w-full h-1 bg-gradient-to-r from-[#158987] to-[#29B475]"></div>
            <div class="w-12 h-12 bg-[#29B475]/10 text-[#29B475] rounded-full flex items-center justify-center mb-4 border border-[#29B475]/20">
                <span class="font-black text-lg">3</span>
            </div>
            <h3 class="text-sm font-black text-slate-900 mb-2">You Both Earn</h3>
            <p class="text-xs font-bold text-slate-500 leading-relaxed">A {{ $currency === 'NGN' ? '₦' : 'CFA' }}{{ number_format($rewardAmount) }} liquidity drop is credited to both of your KoriePay vaults.</p>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden">
        <div class="p-6 sm:p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-lg font-black text-slate-900 tracking-tight">Referral Ledger</h3>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Track your network growth</p>
            </div>
            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm border border-slate-100 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($referrals as $ref)
                <div class="p-6 flex items-center justify-between hover:bg-slate-50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full border-2 border-white bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-sm font-black text-slate-600 shadow-sm shrink-0">
                            {{ substr($ref->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">{{ $ref->name }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Joined {{ $ref->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    <div class="text-right">
                        @if($ref->status === 'rewarded')
                            <p class="text-sm font-mono font-black text-[#29B475]">+{{ $currency === 'NGN' ? '₦' : 'CFA' }}{{ number_format($ref->amount) }}</p>
                            <span class="inline-flex mt-1 px-2 py-0.5 bg-[#29B475]/10 text-[#29B475] rounded text-[8px] font-black uppercase tracking-widest border border-[#29B475]/20">Credited</span>
                        
                        @elseif($ref->status === 'pending_kyc')
                            <p class="text-sm font-mono font-black text-yellow-600">{{ $currency === 'NGN' ? '₦' : 'CFA' }}{{ number_format($ref->amount) }}</p>
                            <span class="inline-flex mt-1 px-2 py-0.5 bg-yellow-50 text-yellow-600 rounded text-[8px] font-black uppercase tracking-widest border border-yellow-200">Awaiting KYC</span>
                        
                        @else
                            <p class="text-sm font-mono font-black text-slate-400">---</p>
                            <span class="inline-flex mt-1 px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[8px] font-black uppercase tracking-widest border border-slate-200">Registered</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100 text-slate-300">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <p class="text-sm font-black text-slate-900">Your network is currently empty.</p>
                    <p class="text-xs font-bold text-slate-500 mt-2">Copy your link above and start inviting friends to earn rewards.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>