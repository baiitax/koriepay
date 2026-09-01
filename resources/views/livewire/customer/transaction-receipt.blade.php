@extends('layouts.customer')

@section('title', 'KoriePay Transaction Proof')

@section('content')
@php
    // Determine if the logged-in user is receiving or sending this specific transaction
    $isCredit = auth()->id() === $transaction->receiver_id;
@endphp

<div class="max-w-xl mx-auto py-10 px-4 animate-in zoom-in-95 duration-500">
    
    <div class="flex justify-between items-center mb-8 print:hidden">
        <a href="{{ route('customer.dashboard') }}" wire:navigate class="text-sm font-bold text-slate-500 hover:text-slate-900 flex items-center transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg> 
            Back to Dashboard
        </a>
        <button onclick="window.print()" class="bg-white border border-slate-200 text-slate-700 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest shadow-sm hover:bg-slate-50 transition-all flex items-center active:scale-95">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Save PDF / Print
        </button>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden relative print:shadow-none print:border-none">
        
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

        <div class="p-8 text-center border-b border-dashed border-slate-200 bg-slate-50/50">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Transaction Value</div>
            
            <div class="text-5xl font-black font-mono tracking-tighter {{ $isCredit ? 'text-[#29B475]' : 'text-slate-900' }}">
                {{ $isCredit ? '+' : '-' }}{{ number_format($isCredit ? $transaction->destination_amount : $transaction->source_amount, 2) }} 
                <span class="text-xl {{ $isCredit ? 'text-[#29B475]/60' : 'text-slate-400' }}">{{ $isCredit ? $transaction->destination_currency : $transaction->source_currency }}</span>
            </div>

            @if(strtolower($transaction->status) === 'completed')
                <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 bg-[#e8f6f0] text-[#29B475] rounded-lg text-[10px] font-black uppercase tracking-widest border border-[#29B475]/20">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Completed
                </div>
            @else
                <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 bg-yellow-50 text-yellow-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-yellow-200">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    {{ strtoupper($transaction->status) }}
                </div>
            @endif
        </div>

        <div class="p-8 space-y-6">
            <div class="grid grid-cols-2 gap-y-6">
                
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Type</p>
                    <p class="text-xs font-bold text-slate-900 uppercase">
                        {{ str_replace('_', ' ', $transaction->type) }}
                    </p>
                </div>
                
                <div class="text-right">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Reference</p>
                    <p class="text-xs font-black text-slate-900 font-mono">{{ $transaction->reference }}</p>
                </div>

                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Sender</p>
                    <p class="text-sm font-bold text-slate-900">{{ $transaction->sender->name ?? 'External Origin' }}</p>
                </div>

                <div class="text-right">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Recipient</p>
                    <p class="text-sm font-bold text-slate-900">{{ $transaction->receiver_name ?? ($transaction->receiver->name ?? 'External Entity') }}</p>
                </div>

                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Date</p>
                    <p class="text-sm font-bold text-slate-900">{{ $transaction->created_at->format('M d, Y') }}</p>
                </div>

                <div class="text-right">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Time</p>
                    <p class="text-sm font-bold text-slate-900">{{ $transaction->created_at->format('g:i:s A') }}</p>
                </div>
            </div>

            <hr class="border-slate-100">

            <div class="space-y-3">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500 font-bold">Principal Amount</span>
                    <span class="text-slate-900 font-bold font-mono">{{ number_format($transaction->source_amount, 2) }} {{ $transaction->source_currency }}</span>
                </div>

                @if(!$isCredit)
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 font-bold">Network Fee</span>
                        <span class="text-slate-900 font-bold font-mono">{{ number_format($transaction->fee_charged, 2) }} {{ $transaction->source_currency }}</span>
                    </div>
                @endif

                @if($transaction->source_currency !== $transaction->destination_currency)
                    <div class="flex justify-between items-center text-xs bg-[#158987]/5 p-2 rounded-lg border border-[#158987]/10">
                        <span class="text-[#158987] font-black text-[9px] uppercase tracking-widest">Exchange Rate</span>
                        <span class="text-slate-900 font-black font-mono">1 {{ $transaction->source_currency }} = {{ $transaction->exchange_rate }} {{ $transaction->destination_currency }}</span>
                    </div>
                @endif
                
                @if(!$isCredit)
                    <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                        <span class="text-[10px] font-black text-slate-900 uppercase tracking-widest">Total Settled</span>
                        <span class="text-base text-slate-900 font-black font-mono">{{ number_format($transaction->source_amount + $transaction->fee_charged, 2) }} {{ $transaction->source_currency }}</span>
                    </div>
                @endif
            </div>

            @if($transaction->description)
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 mt-4">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Narration</p>
                    <p class="text-xs font-bold text-slate-700 italic">"{{ $transaction->description }}"</p>
                </div>
            @endif

            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <div class="flex justify-center mb-4 text-[#158987] opacity-50">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <p class="text-[9px] font-bold text-slate-400 leading-relaxed max-w-[240px] mx-auto uppercase tracking-widest">
                    Securely verified via KoriePay Grid.<br>
                    Authentic Transaction Proof.
                </p>
            </div>
        </div>
    </div>

    <p class="mt-8 text-center text-[9px] font-bold text-slate-400 uppercase tracking-widest">
        &copy; {{ date('Y') }} KoriePay Liquidity Systems • Niamey • Lagos
    </p>
</div>
@endsection