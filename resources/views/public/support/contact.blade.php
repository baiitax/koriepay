@extends('layouts.public')

@section('title', 'Contact Support')

@section('content')
<div class="bg-white min-h-screen">
    
    <div class="container mx-auto px-6 py-24 grid lg:grid-cols-2 gap-16">
        
        <div>
            <div class="inline-flex items-center space-x-2 bg-emerald-success px-4 py-2 rounded-full mb-6 border border-korie-green/20">
                <span class="w-2.5 h-2.5 bg-korie-green rounded-full animate-pulse"></span>
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Available 24/7</span>
            </div>
            
            <h1 class="text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">Get in touch with our team.</h1>
            <p class="text-lg text-slate-500 mb-12 leading-relaxed">
                Whether you're integrating our APIs, setting up a master aggregator network, or just have a question about a transfer, our regional teams are ready to help.
            </p>

            <div class="space-y-8">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-center flex-shrink-0 text-korie-teal">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-lg">Enterprise Sales</h3>
                        <p class="text-slate-500 text-sm mb-1">For aggregator setups and volume pricing.</p>
                        <a href="mailto:sales@koriepay.com" class="text-korie-green font-bold text-sm hover:underline">sales@koriepay.com</a>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-center flex-shrink-0 text-korie-teal">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-lg">Developer Support</h3>
                        <p class="text-slate-500 text-sm mb-1">Technical assistance with API integrations.</p>
                        <a href="mailto:api-support@koriepay.com" class="text-korie-green font-bold text-sm hover:underline">api-support@koriepay.com</a>
                    </div>
                </div>
            </div>

            <div class="mt-16 pt-12 border-t border-slate-200">
                <h3 class="font-bold text-slate-900 uppercase tracking-widest text-xs mb-6">Regional Command Centers</h3>
                <div class="grid sm:grid-cols-2 gap-6">
                    <div>
                        <div class="font-bold text-slate-900 mb-1">🇳🇬 Lagos, Nigeria</div>
                        <div class="text-slate-500 text-sm leading-relaxed">KoriePay Tower, Victoria Island,<br>Lagos State.</div>
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 mb-1">🇳🇪 Niamey, Niger</div>
                        <div class="text-slate-500 text-sm leading-relaxed">Avenue de la Présidence,<br>Plateau, Niamey.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 rounded-[2rem] p-8 md:p-10 border border-slate-200 shadow-sm relative">
            <h3 class="text-2xl font-bold text-slate-900 mb-6">Send us a message</h3>
            
            <form action="#" method="POST" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Full Name</label>
                        <input type="text" class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:border-korie-green focus:ring-1 focus:ring-korie-green transition-colors" placeholder="Jane Doe" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Work Email</label>
                        <input type="email" class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:border-korie-green focus:ring-1 focus:ring-korie-green transition-colors" placeholder="jane@company.com" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">How can we help?</label>
                    <div class="relative">
                        <select class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 appearance-none focus:outline-none focus:border-korie-green focus:ring-1 focus:ring-korie-green transition-colors cursor-pointer" required>
                            <option value="" disabled selected>Select a topic...</option>
                            <option value="api">API / Technical Integration</option>
                            <option value="aggregator">Master Aggregator Setup</option>
                            <option value="agency">Agency Banking Support</option>
                            <option value="general">General Inquiry</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Message</label>
                    <textarea rows="5" class="w-full bg-white border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:border-korie-green focus:ring-1 focus:ring-korie-green transition-colors resize-none" placeholder="Tell us about your project or issue..." required></textarea>
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white font-bold text-lg py-4 rounded-xl hover:bg-slate-800 shadow-lg shadow-slate-900/10 transition-all active:scale-[0.98]">
                    Send Message
                </button>
                <p class="text-xs text-slate-400 text-center mt-4">By submitting this form, you agree to our <a href="{{ route('trust.privacy') }}" class="text-korie-teal hover:underline">Privacy Policy</a>.</p>
            </form>
        </div>

    </div>
</div>
@endsection