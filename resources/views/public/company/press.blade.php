@extends('layouts.public')

@section('title', 'Newsroom & Press')

@section('content')
<div class="bg-white min-h-screen pb-24">
    
    <section class="pt-24 pb-20 bg-slate-50 border-b border-slate-200">
        <div class="container mx-auto px-6 text-center max-w-4xl">
            <h1 class="text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">KoriePay Newsroom</h1>
            <p class="text-xl text-slate-500 leading-relaxed mb-8">
                Official announcements, press releases, and brand assets for media partners and journalists.
            </p>
            <a href="mailto:press@koriepay.com" class="inline-flex items-center text-korie-teal font-bold hover:text-korie-green transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Media Contact: press@koriepay.com
            </a>
        </div>
    </section>

    <div class="container mx-auto px-6 py-20 grid lg:grid-cols-3 gap-16">
        
        <div class="lg:col-span-2">
            <h2 class="text-2xl font-extrabold text-slate-900 mb-8">Latest Announcements</h2>
            
            <div class="space-y-8">
                <article class="border-b border-slate-200 pb-8 group">
                    <div class="text-sm font-bold text-korie-green uppercase tracking-widest mb-2">Company News • Oct 12, 2026</div>
                    <a href="#" class="block">
                        <h3 class="text-2xl font-bold text-slate-900 mb-3 group-hover:text-korie-teal transition-colors">KoriePay Secures CBN Final Approval for PSSP License</h3>
                        <p class="text-slate-500 leading-relaxed mb-4">
                            The Central Bank of Nigeria has officially upgraded KoriePay's operational license, allowing for expanded cross-border settlement capabilities and higher daily throughput for enterprise merchants.
                        </p>
                        <span class="text-slate-900 font-bold text-sm inline-flex items-center group-hover:text-korie-teal transition-colors">
                            Read Full Release <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </span>
                    </a>
                </article>

                <article class="border-b border-slate-200 pb-8 group">
                    <div class="text-sm font-bold text-korie-green uppercase tracking-widest mb-2">Product • Sep 04, 2026</div>
                    <a href="#" class="block">
                        <h3 class="text-2xl font-bold text-slate-900 mb-3 group-hover:text-korie-teal transition-colors">Launch of Instant NGN/XOF Liquidity Swaps</h3>
                        <p class="text-slate-500 leading-relaxed mb-4">
                            KoriePay today announced the successful rollout of its zero-latency settlement engine across the Nigeria and Niger Republic corridor, fundamentally altering how regional trade is processed.
                        </p>
                        <span class="text-slate-900 font-bold text-sm inline-flex items-center group-hover:text-korie-teal transition-colors">
                            Read Full Release <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </span>
                    </a>
                </article>
            </div>
        </div>

        <div>
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-8 sticky top-[100px]">
                <h3 class="text-xl font-bold text-slate-900 mb-6">Brand Assets</h3>
                <p class="text-sm text-slate-500 mb-6">
                    Please adhere to our branding guidelines when using the KoriePay logo in publications or partner sites.
                </p>

                <div class="space-y-4 mb-8">
                    <a href="#" class="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-xl hover:border-korie-green transition group">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-slate-900 rounded-lg flex items-center justify-center text-white font-bold italic">K</div>
                            <span class="font-bold text-slate-700 text-sm">Primary Logo (Dark)</span>
                        </div>
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-korie-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </a>
                    
                    <a href="#" class="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-xl hover:border-korie-green transition group">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-emerald-success text-korie-green rounded-lg flex items-center justify-center font-bold italic border border-korie-green/20">K</div>
                            <span class="font-bold text-slate-700 text-sm">Logo Mark (Light)</span>
                        </div>
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-korie-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </a>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-widest mb-3">Core Colors</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="border border-slate-200 rounded-lg p-2 bg-white">
                            <div class="h-10 bg-korie-green rounded w-full mb-2"></div>
                            <div class="text-[10px] font-mono text-slate-500 text-center">#29B475</div>
                        </div>
                        <div class="border border-slate-200 rounded-lg p-2 bg-white">
                            <div class="h-10 bg-korie-teal rounded w-full mb-2"></div>
                            <div class="text-[10px] font-mono text-slate-500 text-center">#158987</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection