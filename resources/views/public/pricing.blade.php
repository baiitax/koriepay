@extends('layouts.public')
@section('title', 'Pricing & Fees')

@section('content')
<div class="bg-slate-50 min-h-screen pb-24">
    <section class="pt-24 pb-20 bg-slate-900 text-white text-center">
        <div class="container mx-auto px-6 max-w-3xl">
            <h1 class="text-5xl font-extrabold mb-6 tracking-tight">Simple, Transparent Pricing.</h1>
            <p class="text-xl text-slate-400">No hidden spreads. No setup fees. You only pay for successful transactions.</p>
        </div>
    </section>

    <div class="container mx-auto px-6 -mt-10 relative z-10">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-xl shadow-slate-200/50">
                <h3 class="text-2xl font-bold text-slate-900 mb-2">Personal (P2P)</h3>
                <p class="text-slate-500 text-sm mb-6">For everyday cross-border sends.</p>
                <div class="text-4xl font-black text-slate-900 mb-6">Free<span class="text-lg text-slate-400 font-normal"> / KoriePay users</span></div>
                <ul class="space-y-4 mb-8">
                    <li class="flex items-center text-slate-600"><span class="text-korie-green mr-3">✔</span> Zero fees on NGN/XOF swaps</li>
                    <li class="flex items-center text-slate-600"><span class="text-korie-green mr-3">✔</span> Free Adashi pool creation</li>
                    <li class="flex items-center text-slate-600"><span class="text-korie-green mr-3">✔</span> ₦50 flat fee to external banks</li>
                </ul>
                <a href="{{ route('register') }}" class="block text-center w-full bg-slate-100 text-slate-900 font-bold py-3 rounded-xl hover:bg-slate-200 transition">Open Account</a>
            </div>

            <div class="bg-slate-900 rounded-2xl p-8 border border-slate-800 shadow-2xl relative overflow-hidden transform md:-translate-y-4">
                <div class="absolute top-0 right-0 w-32 h-32 bg-korie-green/20 blur-[50px] rounded-full"></div>
                <h3 class="text-2xl font-bold text-white mb-2 relative z-10">Agency POS</h3>
                <p class="text-slate-400 text-sm mb-6 relative z-10">For retail agents and aggregators.</p>
                <div class="text-4xl font-black text-white mb-6 relative z-10">0.5%<span class="text-lg text-slate-400 font-normal"> / transaction</span></div>
                <ul class="space-y-4 mb-8 relative z-10">
                    <li class="flex items-center text-slate-300"><span class="text-korie-green mr-3">✔</span> Capped at ₦1,000 max fee</li>
                    <li class="flex items-center text-slate-300"><span class="text-korie-green mr-3">✔</span> Instant commission payouts</li>
                    <li class="flex items-center text-slate-300"><span class="text-korie-green mr-3">✔</span> Aggregator overrides included</li>
                </ul>
                <a href="{{ route('register') }}" class="block text-center w-full bg-korie-green text-white font-bold py-3 rounded-xl hover:bg-emerald-500 transition shadow-lg shadow-korie-green/20 relative z-10">Become an Agent</a>
            </div>

            <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-xl shadow-slate-200/50">
                <h3 class="text-2xl font-bold text-slate-900 mb-2">API / Enterprise</h3>
                <p class="text-slate-500 text-sm mb-6">For high-volume treasuries.</p>
                <div class="text-4xl font-black text-slate-900 mb-6">Bespoke</div>
                <ul class="space-y-4 mb-8">
                    <li class="flex items-center text-slate-600"><span class="text-korie-green mr-3">✔</span> Volume-based sliding scale</li>
                    <li class="flex items-center text-slate-600"><span class="text-korie-green mr-3">✔</span> Dedicated Liquidity Vaults</li>
                    <li class="flex items-center text-slate-600"><span class="text-korie-green mr-3">✔</span> Priority Technical Support</li>
                </ul>
                <a href="{{ route('support.contact') }}" class="block text-center w-full bg-slate-100 text-slate-900 font-bold py-3 rounded-xl hover:bg-slate-200 transition">Contact Sales</a>
            </div>
        </div>
    </div>
</div>
@endsection