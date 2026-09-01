@extends('layouts.public')

@section('title', 'Agency Banking Solutions')

@section('content')
<div class="bg-white min-h-screen">
    
    <section class="pt-24 pb-20 bg-slate-900 text-white overflow-hidden relative">
        <div class="absolute inset-0 opacity-10 bg-[linear-gradient(rgba(255,255,255,0.1)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.1)_1px,transparent_1px)] bg-[size:40px_40px]"></div>
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-korie-green/20 blur-[120px] rounded-full translate-x-1/3 -translate-y-1/3 pointer-events-none"></div>

        <div class="container mx-auto px-6 relative z-10 grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <div class="inline-flex items-center space-x-2 bg-slate-800/80 backdrop-blur border border-slate-700 px-4 py-2 rounded-full mb-6">
                    <span class="text-korie-green font-bold text-sm">For Agents & Aggregators</span>
                </div>
                <h1 class="text-5xl md:text-6xl font-extrabold mb-6 tracking-tight">Become the Bank of Your Community.</h1>
                <p class="text-xl text-slate-400 mb-10 leading-relaxed max-w-lg">
                    Turn your retail shop into a high-yield financial hub. Process cash-ins, cash-outs, and cross-border transfers while earning industry-leading commissions.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('register') }}" class="text-center bg-korie-green text-white px-8 py-4 rounded-xl font-bold hover:bg-emerald-500 transition-colors shadow-lg shadow-korie-green/20">
                        Register as an Agent
                    </a>
                    <a href="#aggregator" class="text-center bg-slate-800 border border-slate-700 text-white px-8 py-4 rounded-xl font-bold hover:bg-slate-700 transition-colors">
                        Learn about Aggregation
                    </a>
                </div>
            </div>

            <div class="relative hidden lg:block">
                <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 shadow-2xl relative z-10">
                    <div class="flex items-center justify-between mb-6 border-b border-slate-700 pb-4">
                        <div class="text-slate-400 text-sm font-bold uppercase tracking-widest">Today's Revenue</div>
                        <div class="text-korie-green font-mono font-bold">+ ₦42,500</div>
                    </div>
                    <div class="space-y-4">
                        <div class="bg-slate-900 rounded-xl p-4 flex justify-between items-center">
                            <div>
                                <div class="text-white font-bold">Cash Deposit</div>
                                <div class="text-slate-500 text-xs font-mono">TRX-99823</div>
                            </div>
                            <div class="text-right">
                                <div class="text-white font-bold font-mono">₦150,000</div>
                                <div class="text-korie-green text-xs font-bold">+₦450 Comm.</div>
                            </div>
                        </div>
                        <div class="bg-slate-900 rounded-xl p-4 flex justify-between items-center">
                            <div>
                                <div class="text-white font-bold">XOF Transfer</div>
                                <div class="text-slate-500 text-xs font-mono">TRX-99824</div>
                            </div>
                            <div class="text-right">
                                <div class="text-white font-bold font-mono">25,000 XOF</div>
                                <div class="text-korie-green text-xs font-bold">+₦1,200 Comm.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-slate-50 border-b border-slate-200">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition duration-300">
                    <div class="w-12 h-12 bg-emerald-success text-korie-teal rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Instant Settlement</h3>
                    <p class="text-slate-500 leading-relaxed">Commissions are credited to your agent wallet instantly after every successful transaction. No end-of-month waiting.</p>
                </div>
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition duration-300">
                    <div class="w-12 h-12 bg-emerald-success text-korie-teal rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Zero Downtime</h3>
                    <p class="text-slate-500 leading-relaxed">Powered by multiple switching partners. If one bank's network fails, our nodes automatically route through the next available provider.</p>
                </div>
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition duration-300">
                    <div class="w-12 h-12 bg-emerald-success text-korie-teal rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Community Adashi</h3>
                    <p class="text-slate-500 leading-relaxed">Agents can host digital Adashi (Ajo) pools for their customers, earning a management fee on the communal float.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="aggregator" class="py-24 bg-white">
        <div class="container mx-auto px-6 max-w-5xl text-center">
            <h2 class="text-4xl font-extrabold text-slate-900 mb-6">The Master Aggregator Program</h2>
            <p class="text-xl text-slate-500 mb-12 max-w-3xl mx-auto leading-relaxed">
                For established businesses with deep liquidity. Become a regional node, manage hundreds of sub-agents, and earn passive overrides on every transaction processed in your territory.
            </p>
            
            <div class="bg-slate-900 text-left rounded-[2rem] p-8 md:p-12 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-korie-teal/20 blur-[80px] rounded-full"></div>
                <div class="grid md:grid-cols-2 gap-12 relative z-10">
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-6">Aggregator Benefits</h3>
                        <ul class="space-y-4">
                            <li class="flex items-center text-slate-300"><span class="text-korie-green mr-3">✔</span> 0.3% - 0.7% Override on Sub-agent volume</li>
                            <li class="flex items-center text-slate-300"><span class="text-korie-green mr-3">✔</span> Direct access to Regional KYC pipelines</li>
                            <li class="flex items-center text-slate-300"><span class="text-korie-green mr-3">✔</span> Dedicated Account Manager</li>
                            <li class="flex items-center text-slate-300"><span class="text-korie-green mr-3">✔</span> Discounted KoriePay POS Terminals</li>
                        </ul>
                        <div class="mt-8 pt-8 border-t border-slate-800">
                            <a href="{{ route('support.contact') }}" class="text-korie-green font-bold hover:text-white transition-colors flex items-center">
                                Contact Sales for Aggregator Setup <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-2xl p-6">
                        <div class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-4">Required Criteria</div>
                        <ul class="space-y-4 text-sm">
                            <li class="text-slate-300 border-b border-slate-700/50 pb-4"><strong class="text-white block mb-1">Corporate Registration</strong> Must be a registered LLC (CAC in Nigeria, RCCM in Niger).</li>
                            <li class="text-slate-300 border-b border-slate-700/50 pb-4"><strong class="text-white block mb-1">Minimum Float Capital</strong> Capacity to fund sub-agents with at least ₦5,000,000 (or equivalent in XOF).</li>
                            <li class="text-slate-300"><strong class="text-white block mb-1">Physical Office</strong> A verifiable regional operational headquarters.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection