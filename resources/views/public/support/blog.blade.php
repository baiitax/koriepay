@extends('layouts.public')
@section('title', 'Blog & Insights')

@section('content')
<div class="bg-slate-50 min-h-screen pb-24">
    <section class="pt-24 pb-16 text-center">
        <div class="container mx-auto px-6 max-w-3xl">
            <h1 class="text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">Insights & Updates.</h1>
            <p class="text-xl text-slate-500">Product updates, engineering deep-dives, and financial inclusion stories from the KoriePay team.</p>
        </div>
    </section>

    <div class="container mx-auto px-6 grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <a href="#" class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-xl transition group">
            <div class="h-48 bg-slate-900 flex items-center justify-center p-6 relative overflow-hidden">
                <div class="absolute inset-0 opacity-20 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')]"></div>
                <h3 class="text-2xl font-bold text-white relative z-10 text-center">Scaling NGN/XOF Liquidity</h3>
            </div>
            <div class="p-6">
                <div class="text-xs font-bold text-korie-green uppercase tracking-widest mb-3">Engineering</div>
                <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-korie-teal transition">How we achieved millisecond cross-border settlement.</h3>
                <p class="text-slate-500 text-sm">A deep dive into our distributed ledger and VPC routing architecture...</p>
            </div>
        </a>

        <a href="#" class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-xl transition group">
            <div class="h-48 bg-korie-teal flex items-center justify-center p-6">
                <h3 class="text-2xl font-bold text-white text-center">The Future of Adashi</h3>
            </div>
            <div class="p-6">
                <div class="text-xs font-bold text-korie-green uppercase tracking-widest mb-3">Product</div>
                <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-korie-teal transition">Digitizing community trust across West Africa.</h3>
                <p class="text-slate-500 text-sm">How smart contracts are protecting communal savings rings...</p>
            </div>
        </a>
    </div>
</div>
@endsection