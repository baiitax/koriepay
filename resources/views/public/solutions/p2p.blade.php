@extends('layouts.public')
@section('title', 'Borderless P2P Transfers')

@section('content')
<div class="bg-white">
    <section class="py-24 border-b border-slate-200">
        <div class="container mx-auto px-6 grid md:grid-cols-2 gap-16 items-center">
            <div>
                <div class="text-korie-teal font-bold uppercase tracking-widest text-xs mb-4">P2P Transfers</div>
                <h1 class="text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">Send money home, instantly.</h1>
                <p class="text-xl text-slate-500 mb-8 leading-relaxed">
                    Whether you are paying family in Kano or sending business capital to Niamey, KoriePay P2P settles your NGN and XOF transfers in milliseconds using just a phone number.
                </p>
                <a href="{{ route('register') }}" class="inline-block bg-slate-900 text-white px-8 py-4 rounded-xl font-bold hover:bg-slate-800 transition">Start Sending</a>
            </div>
            <div class="bg-slate-50 rounded-[2rem] p-8 border border-slate-200 shadow-xl relative">
                <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm mb-4 flex justify-between items-center">
                    <div><div class="text-xs text-slate-400 font-bold uppercase">Send (NGN)</div><div class="text-2xl font-black text-slate-900">₦50,000</div></div>
                    <svg class="w-6 h-6 text-korie-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
                <div class="bg-emerald-success/30 p-6 rounded-xl border border-korie-green/20 shadow-sm flex justify-between items-center">
                    <div><div class="text-xs text-korie-teal font-bold uppercase">Receive (XOF)</div><div class="text-2xl font-black text-slate-900">24,250 XOF</div></div>
                    <div class="text-xs font-bold text-korie-green bg-white px-2 py-1 rounded shadow-sm">Instant</div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection