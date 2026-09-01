@extends('layouts.public')

@section('title', 'About KoriePay')

@section('content')
<div class="bg-white min-h-screen">
    
    <section class="pt-24 pb-20 bg-slate-50 border-b border-slate-200">
        <div class="container mx-auto px-6 text-center max-w-4xl">
            <h1 class="text-5xl md:text-6xl font-extrabold text-slate-900 mb-6 tracking-tight">Building the Financial Backbone of West Africa.</h1>
            <p class="text-xl text-slate-500 leading-relaxed mb-10">
                KoriePay is an enterprise liquidity network designed to eliminate the friction, latency, and high costs of cross-border capital movement between Anglophone and Francophone Africa.
            </p>
        </div>
    </section>

    <section class="py-24">
        <div class="container mx-auto px-6 grid md:grid-cols-2 gap-16 items-center">
            <div>
                <h2 class="text-3xl font-extrabold text-slate-900 mb-6">The NGN/XOF Disconnect</h2>
                <p class="text-slate-500 leading-relaxed mb-6 text-lg">
                    Despite sharing a massive, porous border and billions of dollars in informal daily trade, moving money digitally between Nigeria (NGN) and the WAEMU zone (XOF) has historically relied on fragmented, slow, and expensive correspondent banking networks or unregulated black-market brokers.
                </p>
                <p class="text-slate-500 leading-relaxed text-lg">
                    We built KoriePay to fix this. By establishing deeply integrated, regulated liquidity pools in both jurisdictions, we bypass legacy rails. When an enterprise sends Naira from Lagos, our systems instantly settle the equivalent in CFA Francs in Niamey, zero latency required.
                </p>
            </div>
            <div class="relative">
                <div class="aspect-square bg-slate-900 rounded-3xl p-8 relative overflow-hidden shadow-2xl">
                    <div class="absolute inset-0 opacity-20 bg-[linear-gradient(rgba(41,180,117,0.5)_1px,transparent_1px),linear-gradient(90deg,rgba(41,180,117,0.5)_1px,transparent_1px)] bg-[size:20px_20px]"></div>
                    <div class="relative z-10 flex flex-col items-center justify-center h-full">
                        <div class="flex items-center space-x-4 mb-8">
                            <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-2xl shadow-lg z-20">🇳🇬</div>
                            <div class="w-24 h-1 border-t-2 border-dashed border-korie-green relative">
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-3 h-3 bg-korie-green rounded-full shadow-[0_0_15px_#29B475]"></div>
                            </div>
                            <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-2xl shadow-lg z-20">🇳🇪</div>
                        </div>
                        <div class="text-white font-mono font-bold tracking-widest text-sm uppercase">Real-time Liquidity Swap</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-slate-900 text-white">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-extrabold mb-16 text-center">Operating at Institutional Scale</h2>
            <div class="grid md:grid-cols-4 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-slate-700">
                <div class="p-4">
                    <div class="text-5xl font-black text-korie-green mb-2 tracking-tighter">14k+</div>
                    <div class="text-slate-400 font-bold uppercase tracking-widest text-xs">Active Network Nodes</div>
                </div>
                <div class="p-4">
                    <div class="text-5xl font-black text-korie-green mb-2 tracking-tighter">$42M</div>
                    <div class="text-slate-400 font-bold uppercase tracking-widest text-xs">Monthly Volume Processed</div>
                </div>
                <div class="p-4">
                    <div class="text-5xl font-black text-korie-green mb-2 tracking-tighter">&lt;0.5s</div>
                    <div class="text-slate-400 font-bold uppercase tracking-widest text-xs">Average Settlement Time</div>
                </div>
                <div class="p-4">
                    <div class="text-5xl font-black text-korie-green mb-2 tracking-tighter">2</div>
                    <div class="text-slate-400 font-bold uppercase tracking-widest text-xs">Central Bank Approvals</div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white text-center">
        <div class="container mx-auto px-6 max-w-3xl">
            <div class="w-16 h-16 bg-emerald-success text-korie-teal rounded-2xl flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
            </div>
            <h2 class="text-3xl font-extrabold text-slate-900 mb-6">Join the Grid.</h2>
            <p class="text-xl text-slate-500 mb-10 leading-relaxed">
                We are a team of engineers, ex-bankers, and cryptographers obsessed with building flawless financial infrastructure. If you thrive on solving complex, high-stakes technical challenges, we want you.
            </p>
            <a href="{{ route('company.careers') }}" class="inline-flex items-center space-x-2 bg-slate-900 text-white px-8 py-4 rounded-xl font-bold hover:bg-slate-800 transition-colors">
                <span>View Open Positions</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>
    </section>

</div>
@endsection