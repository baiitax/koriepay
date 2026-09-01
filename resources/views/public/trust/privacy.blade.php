@extends('layouts.public')

@section('title', 'Global Privacy Policy')

@section('content')
<div class="bg-white min-h-screen pb-24">
    
    <section class="pt-24 pb-16 bg-slate-50 border-b border-slate-200">
        <div class="container mx-auto px-6 max-w-4xl">
            <div class="inline-flex items-center space-x-2 bg-emerald-success/30 px-4 py-2 rounded-full mb-6 border border-korie-green/20">
                <span class="text-xs font-bold text-korie-teal uppercase tracking-widest">Legal & Compliance</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">Privacy Policy</h1>
            <p class="text-lg text-slate-500">
                Effective Date: April 3, 2026 <br>
                Last Updated: April 3, 2026
            </p>
        </div>
    </section>

    <div class="container mx-auto px-6 py-16 max-w-4xl">
        <div class="prose prose-lg prose-slate max-w-none text-slate-600">
            
            <p class="lead text-xl text-slate-800 font-medium mb-10">
                KoriePay Liquidity Systems ("KoriePay", "we", "our", or "us") is committed to protecting your privacy and ensuring that your personal and financial data is handled in compliance with the Nigeria Data Protection Act (NDPA) and WAEMU regional data privacy frameworks.
            </p>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">1. Information We Collect</h2>
            <p>To operate the KoriePay Liquidity Grid and comply with Central Bank of Nigeria (CBN) and BCEAO regulations, we collect the following categories of information:</p>
            <ul class="space-y-2 mb-8 list-disc pl-6">
                <li><strong class="text-slate-900">Identification Data (KYC):</strong> Full name, date of birth, biometric data (facial scans), Bank Verification Number (BVN), National Identity Number (NIN), and copies of government-issued IDs.</li>
                <li><strong class="text-slate-900">Financial Data:</strong> Bank account numbers, mobile money wallet details, transaction history, and settlement records.</li>
                <li><strong class="text-slate-900">Corporate Data:</strong> For Agents and Aggregators, we collect Corporate Affairs Commission (CAC) certificates, RCCM documents, and Director profiles.</li>
                <li><strong class="text-slate-900">Telemetry & Device Data:</strong> IP addresses, MAC addresses, GPS locations during high-value transactions, and device fingerprints for fraud prevention.</li>
            </ul>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">2. How We Use Your Information</h2>
            <p>We process your data strictly to provide and secure our financial services:</p>
            <ul class="space-y-2 mb-8 list-disc pl-6">
                <li>To execute real-time cross-border settlements between NGN and XOF.</li>
                <li>To comply with Anti-Money Laundering (AML) and Combating the Financing of Terrorism (CFT) laws.</li>
                <li>To mitigate fraud using AI-driven transaction monitoring.</li>
                <li>To calculate risk for Shariah-compliant financing and Adashi pool limits.</li>
            </ul>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">3. Information Sharing & Disclosure</h2>
            <p>KoriePay does not sell your data. We only share information with third parties under strict conditions:</p>
            <ul class="space-y-2 mb-8 list-disc pl-6">
                <li><strong class="text-slate-900">Regulatory Authorities:</strong> We are legally mandated to share suspicious transaction reports (STRs) with the NFIU (Nigeria) and CENTIF (Niger).</li>
                <li><strong class="text-slate-900">Banking Partners:</strong> For the purpose of holding fiat currency reserves and clearing settlements.</li>
                <li><strong class="text-slate-900">Master Aggregators:</strong> Tier-1 agents share limited transaction metadata with their managing Aggregators for commission calculations.</li>
            </ul>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">4. Data Security & Retention</h2>
            <p>We implement enterprise-grade security, including AES-256 encryption for data at rest and TLS 1.3 for data in transit. In accordance with AML directives from the CBN and BCEAO, KoriePay is required by law to retain your KYC and transaction data for a minimum of <strong>five (5) years</strong> even after your account is closed.</p>

            <h2 class="text-2xl font-bold text-slate-900 mt-12 mb-4">5. Cross-Border Data Transfers</h2>
            <p>As a cross-border liquidity provider, your data may be processed in secure data centers located in Nigeria, Niger, or AWS EU regions. We ensure all cross-border transfers utilize standard contractual clauses and comply with the respective data protection commissions.</p>

            <div class="mt-16 p-8 bg-slate-50 border border-slate-200 rounded-2xl">
                <h3 class="text-xl font-bold text-slate-900 mb-2">Contact the Data Protection Officer (DPO)</h3>
                <p class="text-slate-600 mb-4">If you have questions regarding your data rights or wish to exercise your right to access or correct your data, please contact our privacy team:</p>
                <a href="mailto:privacy@koriepay.com" class="font-bold text-korie-teal hover:underline">privacy@koriepay.com</a>
            </div>

        </div>
    </div>
</div>
@endsection