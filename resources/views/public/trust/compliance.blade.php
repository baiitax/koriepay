@extends('layouts.public')

@section('title', 'Regulatory Compliance')

@section('content')
<div class="bg-white min-h-screen pb-24">
    
    <section class="pt-24 pb-20 border-b border-slate-200 bg-slate-50">
        <div class="container mx-auto px-6 text-center max-w-4xl">
            <h1 class="text-5xl md:text-6xl font-extrabold text-slate-900 mb-6 tracking-tight">Operating with Absolute Legal Clarity.</h1>
            <p class="text-xl text-slate-500 leading-relaxed">
                KoriePay is fully licensed, regulated, and insured across our operating corridors. We work directly with Central Banks to ensure total compliance.
            </p>
        </div>
    </section>

    <div class="container mx-auto px-6 py-20">
        <div class="grid lg:grid-cols-2 gap-16">
            
            <div>
                <div class="flex items-center space-x-4 mb-8">
                    <span class="text-4xl">🇳🇬</span>
                    <h2 class="text-3xl font-extrabold text-slate-900">Nigeria Jurisdiction</h2>
                </div>
                
                <div class="space-y-6">
                    <div class="border border-slate-200 rounded-2xl p-6 hover:border-korie-green transition">
                        <h3 class="font-bold text-slate-900 text-lg mb-1">Central Bank of Nigeria (CBN)</h3>
                        <p class="text-slate-500 text-sm mb-4">Licensed to operate as a Payment Solution Service Provider (PSSP) and Mobile Money Operator (MMO).</p>
                        <div class="text-xs font-mono font-bold text-slate-400 bg-slate-100 px-3 py-1.5 rounded inline-block">Licence No: PSSP/19/XXXX</div>
                    </div>

                    <div class="border border-slate-200 rounded-2xl p-6 hover:border-korie-green transition">
                        <h3 class="font-bold text-slate-900 text-lg mb-1">Nigeria Deposit Insurance Corporation (NDIC)</h3>
                        <p class="text-slate-500 text-sm">All pass-through deposits and wallet balances held in Nigerian partner banks are fully insured by the NDIC.</p>
                    </div>

                    <div class="border border-slate-200 rounded-2xl p-6 hover:border-korie-green transition">
                        <h3 class="font-bold text-slate-900 text-lg mb-1">Financial Intelligence Unit (NFIU)</h3>
                        <p class="text-slate-500 text-sm">Full adherence to AML/CFT reporting standards. Automated daily rendering of suspicious transaction reports (STRs).</p>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center space-x-4 mb-8">
                    <span class="text-4xl">🇳🇪</span>
                    <h2 class="text-3xl font-extrabold text-slate-900">Niger / WAEMU Jurisdiction</h2>
                </div>
                
                <div class="space-y-6">
                    <div class="border border-slate-200 rounded-2xl p-6 hover:border-korie-teal transition">
                        <h3 class="font-bold text-slate-900 text-lg mb-1">BCEAO (Central Bank of West African States)</h3>
                        <p class="text-slate-500 text-sm mb-4">Approved to issue electronic money and operate payment networks within the West African Economic and Monetary Union.</p>
                        <div class="text-xs font-mono font-bold text-slate-400 bg-slate-100 px-3 py-1.5 rounded inline-block">Agrément N°: EME/XXXX/NE</div>
                    </div>

                    <div class="border border-slate-200 rounded-2xl p-6 hover:border-korie-teal transition">
                        <h3 class="font-bold text-slate-900 text-lg mb-1">CENTIF (Niger)</h3>
                        <p class="text-slate-500 text-sm">Fully compliant with the National Cell for Processing Financial Information to combat money laundering and terrorism financing.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="container mx-auto px-6">
        <div class="bg-slate-900 rounded-[2rem] p-10 md:p-16 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-korie-teal/20 blur-[80px] rounded-full"></div>
            
            <h2 class="text-3xl font-extrabold mb-6 relative z-10">Institutional KYC Pipeline</h2>
            <p class="text-slate-400 text-lg mb-8 max-w-3xl relative z-10 leading-relaxed">
                KoriePay utilizes an advanced, multi-tiered KYC (Know Your Customer) pipeline. Regional managers must manually verify Tier-2 and Tier-3 agent documents against national ID databases (NIN in Nigeria, NINA in Niger) before grid access is granted.
            </p>
            
            <div class="grid md:grid-cols-3 gap-6 relative z-10">
                <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700">
                    <div class="text-korie-green font-bold mb-2">Tier 1</div>
                    <div class="text-slate-300 text-sm">Basic limits. Requires valid phone number and BVN/NIN verification.</div>
                </div>
                <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700">
                    <div class="text-amber-warning font-bold mb-2">Tier 2</div>
                    <div class="text-slate-300 text-sm">Elevated limits. Requires utility bill and government-issued photo ID.</div>
                </div>
                <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700">
                    <div class="text-red-400 font-bold mb-2">Tier 3 (Enterprise)</div>
                    <div class="text-slate-300 text-sm">Unlimited. Requires Corporate Affairs certificates and Director KYC.</div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection