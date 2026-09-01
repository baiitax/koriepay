@extends('layouts.public')

@section('title', 'Help Center')

@section('content')
<div class="bg-slate-50 min-h-screen pb-24">
    
    <section class="bg-slate-900 pt-24 pb-32 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-20 bg-[linear-gradient(rgba(41,180,117,0.3)_1px,transparent_1px),linear-gradient(90deg,rgba(41,180,117,0.3)_1px,transparent_1px)] bg-[size:30px_30px]"></div>
        
        <div class="container mx-auto px-6 relative z-10 text-center max-w-3xl">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-6 tracking-tight">How can we help you?</h1>
            
            <div class="relative max-w-2xl mx-auto mt-8">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" class="w-full bg-white text-slate-900 rounded-2xl pl-12 pr-4 py-5 outline-none focus:ring-4 focus:ring-korie-green/30 border-0 shadow-2xl transition-all text-lg font-medium" placeholder="Search for articles, API errors, or setup guides...">
            </div>
        </div>
    </section>

    <div class="container mx-auto px-6 -mt-16 relative z-20 mb-20">
        <div class="grid md:grid-cols-3 gap-6">
            <a href="#" class="bg-white rounded-2xl p-8 border border-slate-200 shadow-xl shadow-slate-200/50 hover:border-korie-green transition group">
                <div class="w-12 h-12 bg-emerald-success text-korie-teal rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Account Setup</h3>
                <p class="text-slate-500 text-sm">KYC verification, login issues, and profile management.</p>
            </a>
            
            <a href="#" class="bg-white rounded-2xl p-8 border border-slate-200 shadow-xl shadow-slate-200/50 hover:border-korie-green transition group">
                <div class="w-12 h-12 bg-emerald-success text-korie-teal rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Agency Banking</h3>
                <p class="text-slate-500 text-sm">POS troubleshooting, commission tracking, and aggregator tools.</p>
            </a>

            <a href="{{ route('developers.docs') }}" class="bg-white rounded-2xl p-8 border border-slate-200 shadow-xl shadow-slate-200/50 hover:border-korie-green transition group">
                <div class="w-12 h-12 bg-emerald-success text-korie-teal rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">API & Integrations</h3>
                <p class="text-slate-500 text-sm">Developer docs, webhook configurations, and API keys.</p>
            </a>
        </div>
    </div>

    <div class="container mx-auto px-6 max-w-4xl" x-data="{ active: 1 }">
        <h2 class="text-3xl font-extrabold text-slate-900 mb-8 text-center">Frequently Asked Questions</h2>
        
        <div class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden transition-all duration-300 shadow-sm" :class="active === 1 ? 'ring-2 ring-korie-green/50 shadow-md' : ''">
                <button @click="active = active === 1 ? null : 1" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                    <span class="font-bold text-slate-900 text-lg">How long do cross-border transfers take?</span>
                    <svg class="w-5 h-5 text-korie-teal transform transition-transform duration-300" :class="active === 1 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="active === 1" x-collapse x-cloak>
                    <div class="p-6 pt-0 text-slate-500 leading-relaxed border-t border-slate-50 mt-2">
                        Transfers between the NGN (Nigeria) and XOF (Niger/WAEMU) corridors via the KoriePay grid are settled instantly. As soon as the transaction is confirmed on your dashboard, the equivalent value is immediately available in the recipient's wallet or bank account.
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden transition-all duration-300 shadow-sm" :class="active === 2 ? 'ring-2 ring-korie-green/50 shadow-md' : ''">
                <button @click="active = active === 2 ? null : 2" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                    <span class="font-bold text-slate-900 text-lg">What documents are required to become a Tier-2 Agent?</span>
                    <svg class="w-5 h-5 text-korie-teal transform transition-transform duration-300" :class="active === 2 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="active === 2" x-collapse x-cloak>
                    <div class="p-6 pt-0 text-slate-500 leading-relaxed border-t border-slate-50 mt-2">
                        To upgrade to Tier-2, you must provide a government-issued photo ID (NIN Slip, International Passport, or National ID) and a recent utility bill confirming your physical business address. Regional managers approve these within 24 hours.
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden transition-all duration-300 shadow-sm" :class="active === 3 ? 'ring-2 ring-korie-green/50 shadow-md' : ''">
                <button @click="active = active === 3 ? null : 3" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                    <span class="font-bold text-slate-900 text-lg">How are the exchange rates determined?</span>
                    <svg class="w-5 h-5 text-korie-teal transform transition-transform duration-300" :class="active === 3 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="active === 3" x-collapse x-cloak>
                    <div class="p-6 pt-0 text-slate-500 leading-relaxed border-t border-slate-50 mt-2">
                        We utilize live, mid-market institutional oracle rates updated every 60 seconds. There are no hidden spreads; what you see on the dashboard calculation is exactly what settles.
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection