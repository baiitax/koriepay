@extends('layouts.public')

@section('title', 'Security Posture & Infrastructure')

@section('content')
<div class="bg-slate-50 min-h-screen pb-24">
    <section class="bg-slate-900 text-white pt-24 pb-32 relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')] [mask-image:linear-gradient(to_bottom,white,transparent)]"></div>
        <div class="container mx-auto px-6 relative z-10 text-center max-w-4xl">
            <div class="inline-flex items-center space-x-2 bg-slate-800 px-4 py-2 rounded-full mb-8 border border-slate-700">
                <svg class="w-4 h-4 text-korie-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span class="text-xs font-bold text-slate-300 uppercase tracking-widest">Enterprise Security</span>
            </div>
            <h1 class="text-5xl md:text-6xl font-extrabold mb-6 tracking-tight">Secured at the Core.</h1>
            <p class="text-xl text-slate-400 leading-relaxed max-w-2xl mx-auto">
                KoriePay’s infrastructure is designed from the ground up to protect institutional liquidity and sensitive data across the West African corridor.
            </p>
        </div>
    </section>

    <div class="container mx-auto px-6 -mt-16 relative z-20 mb-24">
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200 p-8 grid grid-cols-1 md:grid-cols-3 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-slate-100">
            <div class="p-4">
                <div class="text-3xl font-black text-slate-900 mb-2 tracking-tighter">PCI-DSS</div>
                <div class="text-sm font-bold text-korie-teal uppercase tracking-widest mb-3">Level 1 Service Provider</div>
                <p class="text-slate-500 text-sm">The highest level of certification in the payment card industry.</p>
            </div>
            <div class="p-4">
                <div class="text-3xl font-black text-slate-900 mb-2 tracking-tighter">SOC 2</div>
                <div class="text-sm font-bold text-korie-teal uppercase tracking-widest mb-3">Type II Certified</div>
                <p class="text-slate-500 text-sm">Independently audited for security, availability, and confidentiality.</p>
            </div>
            <div class="p-4">
                <div class="text-3xl font-black text-slate-900 mb-2 tracking-tighter">ISO 27001</div>
                <div class="text-sm font-bold text-korie-teal uppercase tracking-widest mb-3">Information Security</div>
                <p class="text-slate-500 text-sm">Global standard for information security management systems.</p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-6">
        <h2 class="text-3xl font-extrabold text-slate-900 mb-12 text-center">Security Architecture</h2>
        
        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white p-8 rounded-2xl border border-slate-200">
                <div class="w-12 h-12 bg-emerald-success rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-korie-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Cloud Infrastructure</h3>
                <p class="text-slate-500 leading-relaxed mb-4">Our services are hosted on Amazon Web Services (AWS) in highly restricted virtual private clouds (VPCs). Database access is strictly siloed from the public internet.</p>
                <ul class="space-y-2 text-sm font-medium text-slate-600">
                    <li class="flex items-center"><span class="text-korie-green mr-2">✔</span> DDoS mitigation via AWS Shield</li>
                    <li class="flex items-center"><span class="text-korie-green mr-2">✔</span> 99.99% Uptime SLA</li>
                </ul>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-slate-200">
                <div class="w-12 h-12 bg-emerald-success rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-korie-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Data Encryption</h3>
                <p class="text-slate-500 leading-relaxed mb-4">All data transmitted between KoriePay and users is protected using TLS 1.3 encryption. Sensitive data at rest is encrypted using AES-256 block-level encryption.</p>
                <ul class="space-y-2 text-sm font-medium text-slate-600">
                    <li class="flex items-center"><span class="text-korie-green mr-2">✔</span> TLS 1.3 across all endpoints</li>
                    <li class="flex items-center"><span class="text-korie-green mr-2">✔</span> KMS-managed rotation keys</li>
                </ul>
            </div>
            
            <div class="md:col-span-2 bg-slate-900 rounded-2xl p-8 border border-slate-800 flex flex-col md:flex-row items-center justify-between text-white">
                <div class="mb-6 md:mb-0 md:mr-8">
                    <h3 class="text-2xl font-bold mb-2">Vulnerability Disclosure Program</h3>
                    <p class="text-slate-400 max-w-xl">We believe in working with the global security community. If you believe you have found a security vulnerability in KoriePay, please report it to our security team.</p>
                </div>
                <a href="mailto:security@koriepay.com" class="bg-white text-slate-900 px-6 py-3 rounded-lg font-bold hover:bg-slate-100 transition whitespace-nowrap">
                    Report a Vulnerability
                </a>
            </div>
        </div>
    </div>
</div>
@endsection