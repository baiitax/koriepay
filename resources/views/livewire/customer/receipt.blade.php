@extends('layouts.customer')

@section('title', 'KoriePay Transaction Receipt')

@section('content')
<div class="max-w-xl mx-auto py-10 px-4">
    
    <div class="flex justify-between items-center mb-8 print:hidden">
        <a href="{{ route('customer.dashboard') }}" wire:navigate class="text-sm font-bold text-slate-500 hover:text-slate-900 flex items-center transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg> 
            Back to Dashboard
        </a>
        <button onclick="window.print()" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl text-sm font-bold shadow-sm hover:bg-slate-50 transition-all flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Proof
        </button>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden relative">
        
        <div class="bg-[#020617] p-8 text-center relative overflow-hidden">
            <div class="absolute inset-0 opacity-20 bg-[linear-gradient(rgba(41,180,117,0.5)_1px,transparent_1px),linear-gradient(90deg,rgba(41,180,117,0.5)_1px,transparent_1px)] bg-[size:15px_15px]"></div>
            
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white rounded-2xl p-1.5 mx-auto mb-4 shadow-lg">
                    <div class="w-full h-full bg-gradient-to-br from-[#29B475] to-[#158987] rounded-xl flex items-center justify-center">
                        <span class="text-white font-black text-2xl italic">K</span>
                    </div>
                </div>
                <h2 class="text-white font-black text-xl tracking-tight italic">KoriePay Proof</h2>
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-[0.2em] mt-1">Institutional Liquidity Grid</p>
            </div>
        </div>

        <div class="p-8 text-center border-b border-dashed border-slate-100">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Transaction Value</div>
            <div class="text-5xl font-black text-slate-900 font-mono tracking-tighter">
                {{ number_format($transaction->source_amount, 2) }} 
                <span class="text-xl text-slate-400">{{ $transaction->source_currency }}</span>
            </div>
        </div>

        <div class="p-8 space-y-6">
            <div class="grid grid-cols-2 gap-y-6">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-[#29B475] border border-emerald-100">
                        {{ strtoupper($transaction->status) }}
                    </span>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Reference</p>
                    <p class="text-sm font-black text-slate-900 font-mono">{{ $transaction->reference }}</p>
                </div>

                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sender</p>
                    <p class="text-sm font-bold text-slate-900">{{ $transaction->sender->name ?? 'KoriePay User' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Recipient</p>
                    <p class="text-sm font-bold text-slate-900">{{ $transaction->receiver_name ?? 'KoriePay User' }}</p>
                </div>

                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Date</p>
                    <p class="text-sm font-bold text-slate-900">{{ $transaction->created_at->format('M d, Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Time</p>
                    <p class="text-sm font-bold text-slate-900">{{ $transaction->created_at->format('g:i A') }}</p>
                </div>
            </div>

            <hr class="border-slate-100">

            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-400 font-bold">Network Fee</span>
                <span class="text-slate-900 font-black font-mono">
                    {{ number_format($transaction->fee_charged, 2) }} {{ $transaction->source_currency }}
                </span>
            </div>

            @if($transaction->source_currency !== $transaction->destination_currency)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-400 font-bold">Exchange Rate</span>
                    <span class="text-slate-900 font-black font-mono">1 {{ $transaction->source_currency }} = {{ $transaction->exchange_rate }} {{ $transaction->destination_currency }}</span>
                </div>
            @endif

            <div class="mt-10 pt-6 border-t border-slate-50 text-center">
                <p class="text-[10px] text-slate-400 leading-relaxed max-w-[240px] mx-auto uppercase font-bold tracking-widest">
                    Verified via KoriePay Grid<br>
                    Authentic Transaction Proof
                </p>
            </div>
        </div>
    </div>

    <p class="mt-8 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
        &copy; {{ date('Y') }} KoriePay Liquidity Systems • Niamey • Lagos
    </p>
</div>
@endsection