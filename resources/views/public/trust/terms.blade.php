@extends('layouts.public')

@section('title', 'Terms of Service')

@section('content')
<div class="bg-white min-h-screen pb-24">
    
    <section class="pt-24 pb-16 bg-slate-900 border-b border-slate-800">
        <div class="container mx-auto px-6 max-w-4xl">
            <div class="inline-flex items-center space-x-2 bg-slate-800 px-4 py-2 rounded-full mb-6 border border-slate-700">
                <span class="text-xs font-bold text-slate-300 uppercase tracking-widest">Legal Agreement</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-6 tracking-tight">Terms of Service</h1>
            <p class="text-lg text-slate-400">
                Effective Date: April 3, 2026
            </p>
        </div>
    </section>

    <div class="container mx-auto px-6 py-16 max-w-4xl">
        <div class="prose prose-lg prose-slate max-w-none text-slate-600">
            
            <div class="bg-amber-50 border-l-4 border-amber-warning p-6 rounded-r-xl mb-12">
                <p class="text-sm text-slate-800 font-medium m-0">
                    <strong class="font-bold">PLEASE READ CAREFULLY:</strong> By accessing or using the KoriePay Grid, APIs, or Agency Terminals, you agree to be bound by these Terms. If you do not agree, you may not use our services. These terms contain mandatory arbitration provisions and class action waivers.
                </p>
            </div>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">1. The KoriePay Liquidity Grid</h2>
            <p>KoriePay provides a technological infrastructure (the "Grid") enabling instant liquidity swaps, peer-to-peer transfers, and agency banking services. We act as a financial technology provider in partnership with licensed commercial banks and MMOs in Nigeria and the WAEMU zone.</p>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">2. Account Registration & KYC Tiers</h2>
            <p>To use our services, you must register for an account and pass our Know Your Customer (KYC) pipeline. Your transaction limits are determined by your KYC Tier:</p>
            <ul class="space-y-2 mb-8 list-disc pl-6">
                <li><strong class="text-slate-900">Tier 1 (Basic):</strong> Requires BVN/NIN verification. Subject to maximum daily limits as defined by the CBN/BCEAO.</li>
                <li><strong class="text-slate-900">Tier 2 (Agent):</strong> Requires physical address verification and government ID. Required for operating a KoriePay POS terminal.</li>
                <li><strong class="text-slate-900">Tier 3 (Enterprise/Aggregator):</strong> Requires full corporate documentation, board resolutions, and Director KYC. Unlimited transaction throughput.</li>
            </ul>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">3. Agency Banking & Aggregator Obligations</h2>
            <p>If you operate as a KoriePay Agent or Master Aggregator:</p>
            <ul class="space-y-2 mb-8 list-disc pl-6">
                <li>You agree to maintain sufficient liquidity (float) in your KoriePay wallet to service customer cash-out requests.</li>
                <li>You are strictly prohibited from charging customers fees higher than the approved KoriePay grid commission rates.</li>
                <li>Master Aggregators bear primary responsibility for the initial vetting of sub-agents in their network.</li>
            </ul>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">4. Adashi Pools & Islamic Finance</h2>
            <p>When participating in Adashi (Ajo/Esusu) savings pools or applying for Shariah-compliant financing via the KoriePay platform:</p>
            <p>KoriePay acts solely as the escrow and technological facilitator. While we automate contributions and payouts, KoriePay does not guarantee against default by group members in user-created Adashi pools. Islamic financing products are executed under strictly non-interest (Murabaha/Qard) agreements vetted by our Shariah advisory board.</p>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">5. Prohibited Activities & AML Compliance</h2>
            <p>You may not use KoriePay for any of the following:</p>
            <ul class="space-y-2 mb-8 list-disc pl-6">
                <li>Money laundering, terrorism financing, or evading financial sanctions.</li>
                <li>Operating unregistered foreign exchange (FX) brokerages or crypto-currency arbitrage off-grid.</li>
                <li>Processing transactions for illicit goods, gambling, or adult entertainment.</li>
            </ul>
            <p>Any violation of this section will result in immediate account termination, freezing of grid funds, and reporting to the NFIU/CENTIF.</p>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">6. Settlement & Latency</h2>
            <p>While KoriePay strives for millisecond settlement across the NGN/XOF corridor, settlement times may be affected by downtime in partner banking networks or central bank clearing systems. KoriePay is not liable for indirect damages resulting from external network delays.</p>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">7. Governing Law & Dispute Resolution</h2>
            <p>For users registered with Nigerian KYC, these Terms are governed by the laws of the Federal Republic of Nigeria. For users registered with Nigerien/WAEMU KYC, these terms are governed by the laws of the Republic of Niger and BCEAO regulations. Any disputes shall be resolved by binding arbitration in Lagos or Niamey, respectively.</p>

            <div class="mt-16 pt-8 border-t border-slate-200">
                <p class="text-sm text-slate-500">For legal inquiries or subpoenas, please contact our legal department at <a href="mailto:legal@koriepay.com" class="font-bold text-korie-teal hover:underline">legal@koriepay.com</a>.</p>
            </div>

        </div>
    </div>
</div>
@endsection