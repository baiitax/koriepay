<div class="p-8 bg-slate-50 min-h-screen">
    <div class="flex justify-between items-end mb-10">
        <div>
            <h1 class="text-3xl font-black text-slate-900">System Overview</h1>
            <p class="text-slate-500 font-medium">SahelPay Global Command Center</p>
        </div>
        <div class="flex gap-3">
            <button class="bg-white border border-slate-200 px-4 py-2 rounded-xl font-bold text-slate-600 shadow-sm">Export Reports</button>
            <button class="bg-emerald-600 text-white px-4 py-2 rounded-xl font-bold shadow-lg shadow-emerald-200">+ System Alert</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Niger Liquidity (XOF)</p>
            <h2 class="text-3xl font-black text-slate-900 mt-2">{{ number_format($total_xof) }}</h2>
            <div class="mt-4 flex items-center text-emerald-500 text-xs font-bold">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                12% Increase
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Nigeria Liquidity (NGN)</p>
            <h2 class="text-3xl font-black text-slate-900 mt-2">₦ {{ number_format($total_ngn) }}</h2>
            <div class="mt-4 flex items-center text-orange-500 text-xs font-bold">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 14l-7 7m0 0l-7-7m7-7v18"></path></svg>
                3% Decrease
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">KYC Approvals</p>
            <h2 class="text-3xl font-black text-slate-900 mt-2">{{ $pending_kyc }}</h2>
            <p class="mt-4 text-xs font-bold text-slate-400">Applications pending</p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Network Size</p>
            <h2 class="text-3xl font-black text-slate-900 mt-2">{{ $total_users }}</h2>
            <p class="mt-4 text-xs font-bold text-slate-400">Across 2 countries</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                <h3 class="font-black text-slate-900">Live Transaction Stream</h3>
                <span class="animate-pulse flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </div>
            <div class="p-0">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-bold">
                        <tr>
                            <th class="px-6 py-3">Reference</th>
                            <th class="px-6 py-3">User</th>
                            <th class="px-6 py-3">Amount</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($recent_transactions as $trx)
                        <tr class="text-sm hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-400">{{ $trx->reference }}</td>
                            <td class="px-6 py-4 font-bold text-slate-700">{{ $trx->user?->name ?? 'System' }}</td>
                            <td class="px-6 py-4 font-black">
                                {{ number_format($trx->amount) }} {{ $trx->currency }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-md bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase">
                                    {{ $trx->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-slate-900 rounded-3xl p-8 text-white">
            <h3 class="font-black mb-6 text-xl">Region Performance</h3>
            <div class="space-y-6">
                <div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="font-bold">Nigeria (NGA)</span>
                        <span class="text-emerald-400">65%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: 65%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="font-bold">Niger (NER)</span>
                        <span class="text-emerald-400">35%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: 35%"></div>
                    </div>
                </div>
            </div>
            
            <div class="mt-10 p-6 bg-slate-800 rounded-2xl border border-slate-700">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-2">System Status</p>
                <div class="flex items-center text-emerald-400 font-black text-sm">
                    <div class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-ping"></div>
                    ALL SYSTEMS OPERATIONAL
                </div>
            </div>
        </div>
    </div>
</div>