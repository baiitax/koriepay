<?php

use function Livewire\Volt\layout;
use function Livewire\Volt\state;

layout('layouts.regional'); 

state([
    // Simulated backend data for the view
    'metrics' => [
        'volume_24h' => '42,500,000',
        'volume_trend' => '+12.4%',
        'active_agents' => 142,
        'agents_trend' => '+3 this week',
        'pending_kyc' => 18,
        'regional_revenue' => '845,000',
        'revenue_trend' => '+5.2%',
    ],
    'recent_kyc' => [
        ['name' => 'Adebayo Outpost', 'type' => 'Tier 2 Agent', 'time' => '10 mins ago', 'status' => 'Pending Review'],
        ['name' => 'Kano Central Hub', 'type' => 'Tier 3 Master', 'time' => '1 hour ago', 'status' => 'Pending Review'],
        ['name' => 'Okafor Liquids', 'type' => 'Tier 1 Agent', 'time' => '3 hours ago', 'status' => 'Action Required'],
    ]
]);

?>

<div class="min-h-screen bg-slate-50 font-sans selection:bg-[#29B475] selection:text-white p-6 lg:p-8 space-y-8 pb-24">
    
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-[#29B475] animate-pulse"></span>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Live Regional Feed</span>
            </div>
            <h1 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">Territory Analytics.</h1>
            <p class="text-sm font-medium text-slate-500 mt-1">Financial velocity and network health for your jurisdiction.</p>
        </div>

        <div class="flex items-center gap-3">
            <button class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold shadow-sm hover:bg-slate-50 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Report
            </button>
            <a href="{{ route('regional.capture') }}" class="px-4 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold shadow-lg shadow-slate-900/20 hover:bg-slate-800 transition-all flex items-center gap-2 active:scale-[0.99]">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Agent
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
        
        <div class="bg-white border border-slate-200 rounded-[1.5rem] p-6 shadow-sm hover:shadow-xl hover:shadow-[#29B475]/5 transition-all relative overflow-hidden group">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-slate-50 border border-slate-100 text-slate-700 rounded-xl flex items-center justify-center group-hover:bg-[#e8f6f0] group-hover:text-[#29B475] group-hover:border-[#29B475]/20 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <span class="flex items-center gap-1 text-xs font-bold text-[#29B475] bg-[#e8f6f0] px-2 py-1 rounded-md">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    {{ $metrics['volume_trend'] }}
                </span>
            </div>
            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">24h Liquidity Volume</h3>
            <p class="text-2xl lg:text-3xl font-black text-slate-900 font-mono tracking-tight">₦{{ $metrics['volume_24h'] }}</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-[1.5rem] p-6 shadow-sm hover:shadow-xl hover:shadow-[#158987]/5 transition-all group">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-slate-50 border border-slate-100 text-slate-700 rounded-xl flex items-center justify-center group-hover:bg-[#158987]/10 group-hover:text-[#158987] group-hover:border-[#158987]/20 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <span class="flex items-center gap-1 text-xs font-bold text-[#29B475] bg-[#e8f6f0] px-2 py-1 rounded-md">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    {{ $metrics['revenue_trend'] }}
                </span>
            </div>
            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Regional Comm. Rev</h3>
            <p class="text-2xl lg:text-3xl font-black text-slate-900 font-mono tracking-tight">₦{{ $metrics['regional_revenue'] }}</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-[1.5rem] p-6 shadow-sm group">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-slate-50 border border-slate-100 text-slate-700 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <span class="text-xs font-bold text-slate-500">{{ $metrics['agents_trend'] }}</span>
            </div>
            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Active Network Agents</h3>
            <p class="text-2xl lg:text-3xl font-black text-slate-900">{{ $metrics['active_agents'] }}</p>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-[1.5rem] p-6 shadow-xl shadow-slate-900/20 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-[#29B475]/10 rounded-full blur-2xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <div class="w-10 h-10 bg-white/10 border border-white/10 text-white rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <span class="flex items-center gap-1.5 text-xs font-bold text-amber-400 bg-amber-400/10 border border-amber-400/20 px-2 py-1 rounded-md">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span> Requires Action
                </span>
            </div>
            <h3 class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1 relative z-10">Pending KYC Checks</h3>
            <div class="flex items-end gap-3 relative z-10">
                <p class="text-2xl lg:text-3xl font-black text-white">{{ $metrics['pending_kyc'] }}</p>
                <a href="{{ route('regional.kyc') }}" class="text-sm font-bold text-[#29B475] hover:text-white transition-colors mb-1">Process Now &rarr;</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-[1.5rem] shadow-sm p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-lg font-black text-slate-900">Network Liquidity Flow</h2>
                    <p class="text-xs font-medium text-slate-500 mt-0.5">7-Day NGN Settlement Volume</p>
                </div>
                <select class="text-xs font-bold text-slate-700 bg-slate-50 border-slate-200 rounded-lg focus:ring-[#29B475] focus:border-[#29B475]">
                    <option>Past 7 Days</option>
                    <option>Past 30 Days</option>
                </select>
            </div>
            
            <div class="h-64 w-full flex items-end gap-2 sm:gap-4 pt-4 border-b border-slate-100 relative">
                <div class="absolute inset-0 flex flex-col justify-between pb-6 pointer-events-none">
                    <div class="w-full border-t border-dashed border-slate-200"></div>
                    <div class="w-full border-t border-dashed border-slate-200"></div>
                    <div class="w-full border-t border-dashed border-slate-200"></div>
                    <div class="w-full border-t border-dashed border-slate-200"></div>
                </div>
                
                <div class="flex-1 bg-slate-100 hover:bg-[#158987]/20 rounded-t-lg relative group transition-colors" style="height: 40%">
                    <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-[10px] font-bold py-1 px-2 rounded">₦12M</div>
                </div>
                <div class="flex-1 bg-slate-100 hover:bg-[#158987]/20 rounded-t-lg relative group transition-colors" style="height: 55%">
                    <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-[10px] font-bold py-1 px-2 rounded">₦18M</div>
                </div>
                <div class="flex-1 bg-[#158987]/40 hover:bg-[#158987]/60 rounded-t-lg relative group transition-colors" style="height: 30%">
                    <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-[10px] font-bold py-1 px-2 rounded">₦9M</div>
                </div>
                <div class="flex-1 bg-slate-100 hover:bg-[#158987]/20 rounded-t-lg relative group transition-colors" style="height: 65%">
                    <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-[10px] font-bold py-1 px-2 rounded">₦22M</div>
                </div>
                <div class="flex-1 bg-slate-100 hover:bg-[#158987]/20 rounded-t-lg relative group transition-colors" style="height: 80%">
                    <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-[10px] font-bold py-1 px-2 rounded">₦28M</div>
                </div>
                <div class="flex-1 bg-[#29B475] shadow-[0_0_15px_rgba(41,180,117,0.4)] rounded-t-lg relative group transition-all" style="height: 95%">
                    <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-[10px] font-bold py-1 px-2 rounded z-10">₦42.5M</div>
                </div>
                <div class="flex-1 bg-slate-100 hover:bg-[#158987]/20 rounded-t-lg relative group transition-colors" style="height: 50%">
                    <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-[10px] font-bold py-1 px-2 rounded">₦16M</div>
                </div>
            </div>
            <div class="flex justify-between text-[10px] font-bold text-slate-400 mt-3 px-2">
                <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span class="text-[#29B475]">Sat (Peak)</span><span>Sun</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-[1.5rem] shadow-sm p-6 flex flex-col">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-black text-slate-900">Priority KYC Queue</h2>
                <a href="{{ route('regional.kyc') }}" class="text-xs font-bold text-[#158987] hover:text-[#29B475] transition-colors">View All</a>
            </div>
            
            <div class="flex-1 space-y-4">
                @forelse($recent_kyc as $kyc)
                <div class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-100 transition-all cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 font-bold flex items-center justify-center text-sm group-hover:bg-white group-hover:shadow-sm group-hover:text-[#158987] transition-all">
                            {{ substr($kyc['name'], 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $kyc['name'] }}</p>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ $kyc['type'] }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="block text-xs font-bold {{ $kyc['status'] == 'Action Required' ? 'text-amber-500' : 'text-slate-400' }}">{{ $kyc['status'] }}</span>
                        <span class="block text-[10px] font-medium text-slate-400 mt-0.5">{{ $kyc['time'] }}</span>
                    </div>
                </div>

                @empty
                    <div class="py-8 text-center text-slate-400 italic text-xs">
                        Queue is empty.
                    </div>
                @endforelse
            </div>

            <a href="{{ route('regional.kyc') }}" class="mt-4 w-full py-3 bg-slate-50 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-100 transition-colors text-center block">
                Process 18 Pending Items
            </a>
        </div>

    </div>

</div>