@extends('layouts.public')
@section('title', 'Adashi Savings Pools')

@section('content')
<div class="bg-slate-50 min-h-screen">
    <section class="py-24">
        <div class="container mx-auto px-6 text-center max-w-4xl mb-20">
            <div class="text-korie-teal font-bold uppercase tracking-widest text-xs mb-4">Community Finance</div>
            <h1 class="text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">Modernizing the Adashi.</h1>
            <p class="text-xl text-slate-500 leading-relaxed">
                We brought the traditional African rotating savings circle (Ajo/Esusu/Adashi) to the blockchain era. Automate collections, guarantee payouts, and build community wealth transparently.
            </p>
        </div>

        <div class="container mx-auto px-6 grid md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="text-3xl mb-4">🤝</div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Create & Invite</h3>
                <p class="text-slate-500 text-sm">Set the contribution amount, frequency (weekly/monthly), and invite your trusted circle via phone number.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="text-3xl mb-4">⚙️</div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Auto-Debits</h3>
                <p class="text-slate-500 text-sm">No more chasing members for money. KoriePay automatically debits linked wallets on contribution day.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="text-3xl mb-4">💰</div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Guaranteed Payouts</h3>
                <p class="text-slate-500 text-sm">When it's your turn, the total pool is instantly settled into your wallet. Governed by KoriePay escrow smart contracts.</p>
            </div>
        </div>
    </section>
</div>
@endsection