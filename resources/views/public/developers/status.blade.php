@extends('layouts.public')
@section('title', 'System Status')

@section('content')
<div class="bg-white min-h-screen pb-24">
    <section class="pt-24 pb-12">
        <div class="container mx-auto px-6 max-w-4xl">
            <div class="bg-emerald-success border border-korie-green/30 p-6 rounded-2xl flex items-center justify-between mb-12">
                <div class="flex items-center space-x-4">
                    <span class="w-4 h-4 bg-korie-green rounded-full shadow-[0_0_10px_#29B475] animate-pulse"></span>
                    <span class="text-xl font-bold text-slate-900">All Systems Operational</span>
                </div>
                <span class="text-korie-teal font-bold font-mono">Uptime: 99.99%</span>
            </div>

            <div class="space-y-6">
                <div class="border border-slate-200 rounded-xl p-6 flex justify-between items-center">
                    <div>
                        <div class="font-bold text-slate-900 text-lg">Core API</div>
                        <div class="text-sm text-slate-500">api.koriepay.com</div>
                    </div>
                    <div class="text-korie-green font-bold">Operational</div>
                </div>
                <div class="border border-slate-200 rounded-xl p-6 flex justify-between items-center">
                    <div>
                        <div class="font-bold text-slate-900 text-lg">NGN Settlement Gateway (Nigeria)</div>
                        <div class="text-sm text-slate-500">NIBSS / CBN Connect</div>
                    </div>
                    <div class="text-korie-green font-bold">Operational</div>
                </div>
                <div class="border border-slate-200 rounded-xl p-6 flex justify-between items-center">
                    <div>
                        <div class="font-bold text-slate-900 text-lg">XOF Settlement Gateway (WAEMU)</div>
                        <div class="text-sm text-slate-500">BCEAO GIM-UEMOA</div>
                    </div>
                    <div class="text-korie-green font-bold">Operational</div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection