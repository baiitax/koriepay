@extends('layouts.public')

@section('title', 'Careers at KoriePay')

@section('content')
<div class="bg-slate-50 min-h-screen pb-24">
    
    <section class="pt-24 pb-20 bg-slate-900 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-20 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')] [mask-image:linear-gradient(to_bottom,white,transparent)]"></div>
        <div class="container mx-auto px-6 relative z-10 text-center max-w-4xl">
            <div class="inline-flex items-center space-x-2 bg-emerald-success/20 px-4 py-2 rounded-full mb-8 border border-korie-green/30">
                <span class="w-2.5 h-2.5 bg-korie-green rounded-full animate-pulse"></span>
                <span class="text-xs font-bold text-emerald-100 uppercase tracking-widest">We are hiring</span>
            </div>
            <h1 class="text-5xl md:text-6xl font-extrabold mb-6 tracking-tight">Build the Future of African Finance.</h1>
            <p class="text-xl text-slate-400 leading-relaxed max-w-2xl mx-auto">
                Join a team of elite engineers, cryptographers, and financial experts solving the hardest liquidity challenges across the West African corridor.
            </p>
        </div>
    </section>

    <section class="py-20 bg-white border-b border-slate-200">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-3 gap-12 text-center">
                <div>
                    <div class="w-16 h-16 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-center mx-auto mb-6 text-korie-teal">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Work from Anywhere</h3>
                    <p class="text-slate-500 leading-relaxed">We are a remote-first company. Whether you are in Lagos, Niamey, or London, you can build with us.</p>
                </div>
                <div>
                    <div class="w-16 h-16 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-center mx-auto mb-6 text-korie-teal">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Premium Health Coverage</h3>
                    <p class="text-slate-500 leading-relaxed">Comprehensive medical, dental, and vision insurance for you and your dependents.</p>
                </div>
                <div>
                    <div class="w-16 h-16 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-center mx-auto mb-6 text-korie-teal">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">High-Impact Engineering</h3>
                    <p class="text-slate-500 leading-relaxed">No red tape. Ship code that moves millions of dollars daily and directly impacts regional economies.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20">
        <div class="container mx-auto px-6 max-w-4xl">
            <h2 class="text-3xl font-extrabold text-slate-900 mb-10 text-center">Open Positions</h2>

            <div class="mb-12">
                <h3 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">Engineering & Product</h3>
                <div class="space-y-4">
                    <a href="#" class="group block bg-white p-6 rounded-2xl border border-slate-200 hover:border-korie-green hover:shadow-lg transition-all duration-300">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="text-lg font-bold text-slate-900 group-hover:text-korie-teal transition-colors">Senior Backend Engineer (Laravel/PHP)</h4>
                                <div class="text-slate-500 text-sm mt-1 flex items-center space-x-3">
                                    <span>📍 Remote (EMEA)</span>
                                    <span>•</span>
                                    <span>Full-time</span>
                                </div>
                            </div>
                            <div class="hidden sm:block text-korie-green font-bold">Apply &rarr;</div>
                        </div>
                    </a>
                    
                    <a href="#" class="group block bg-white p-6 rounded-2xl border border-slate-200 hover:border-korie-green hover:shadow-lg transition-all duration-300">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="text-lg font-bold text-slate-900 group-hover:text-korie-teal transition-colors">DevOps / SRE Engineer</h4>
                                <div class="text-slate-500 text-sm mt-1 flex items-center space-x-3">
                                    <span>📍 Remote (Nigeria/Niger)</span>
                                    <span>•</span>
                                    <span>Full-time</span>
                                </div>
                            </div>
                            <div class="hidden sm:block text-korie-green font-bold">Apply &rarr;</div>
                        </div>
                    </a>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">Compliance & Operations</h3>
                <div class="space-y-4">
                    <a href="#" class="group block bg-white p-6 rounded-2xl border border-slate-200 hover:border-korie-green hover:shadow-lg transition-all duration-300">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="text-lg font-bold text-slate-900 group-hover:text-korie-teal transition-colors">Regional Compliance Officer (WAEMU)</h4>
                                <div class="text-slate-500 text-sm mt-1 flex items-center space-x-3">
                                    <span>📍 Niamey, Niger (Hybrid)</span>
                                    <span>•</span>
                                    <span>Full-time</span>
                                </div>
                            </div>
                            <div class="hidden sm:block text-korie-green font-bold">Apply &rarr;</div>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </section>

</div>
@endsection