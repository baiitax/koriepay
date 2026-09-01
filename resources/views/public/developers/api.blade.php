@extends('layouts.public')
@section('title', 'API Reference')

@section('content')
<div class="bg-white min-h-screen">
    <div class="flex flex-col lg:flex-row items-start relative">
        
        <aside class="w-full lg:w-64 flex-shrink-0 pt-10 pb-20 lg:sticky lg:top-[80px] lg:h-[calc(100vh-80px)] overflow-y-auto border-r border-slate-200 px-6 hidden lg:block bg-slate-50">
            <h3 class="font-black text-slate-900 uppercase tracking-widest text-xs mb-4">Core Endpoints</h3>
            <ul class="space-y-3 text-sm font-medium">
                <li><a href="#auth" class="text-slate-500 hover:text-korie-teal">Authentication</a></li>
                <li><a href="#balance" class="text-slate-500 hover:text-korie-teal">Check Balance</a></li>
                <li><a href="#transfer" class="text-korie-green font-bold">Create Transfer</a></li>
                <li><a href="#verify" class="text-slate-500 hover:text-korie-teal">Verify Account</a></li>
                <li><a href="#kyc" class="text-slate-500 hover:text-korie-teal">Submit KYC</a></li>
            </ul>
        </aside>

        <main class="flex-1 w-full grid xl:grid-cols-2 gap-0 border-t border-slate-200 lg:border-t-0">
            
            <div class="p-8 lg:p-12 xl:pr-16">
                <h1 class="text-3xl font-extrabold text-slate-900 mb-4 tracking-tight" id="transfer">Create Transfer</h1>
                <p class="text-slate-500 mb-8 leading-relaxed">
                    Creates a new cross-border transfer. This endpoint deducts from your NGN or XOF balance and instantly routes the equivalent liquidity to the destination account.
                </p>

                <div class="inline-flex items-center space-x-3 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 font-mono text-sm mb-8">
                    <span class="bg-korie-green text-white px-2 py-0.5 rounded font-bold text-xs">POST</span>
                    <span class="text-slate-700">/v1/transfers/create</span>
                </div>

                <h3 class="font-bold text-slate-900 text-lg mb-4 border-b border-slate-200 pb-2">Body Parameters</h3>
                
                <ul class="space-y-6 text-sm">
                    <li>
                        <div class="flex items-center space-x-2 mb-1">
                            <code class="font-bold text-slate-900 text-base">amount</code>
                            <span class="text-xs text-slate-400">integer</span>
                            <span class="text-[10px] uppercase font-bold text-red-critical bg-red-50 px-1.5 py-0.5 rounded">Required</span>
                        </div>
                        <p class="text-slate-500">The amount to send in the lowest denomination (e.g., Kobo for NGN, Centimes for XOF).</p>
                    </li>
                    <li>
                        <div class="flex items-center space-x-2 mb-1">
                            <code class="font-bold text-slate-900 text-base">currency</code>
                            <span class="text-xs text-slate-400">string</span>
                            <span class="text-[10px] uppercase font-bold text-red-critical bg-red-50 px-1.5 py-0.5 rounded">Required</span>
                        </div>
                        <p class="text-slate-500">The currency you are sending. Valid options are <code class="bg-slate-100 px-1 rounded">NGN</code> or <code class="bg-slate-100 px-1 rounded">XOF</code>.</p>
                    </li>
                    <li>
                        <div class="flex items-center space-x-2 mb-1">
                            <code class="font-bold text-slate-900 text-base">recipient</code>
                            <span class="text-xs text-slate-400">string</span>
                            <span class="text-[10px] uppercase font-bold text-red-critical bg-red-50 px-1.5 py-0.5 rounded">Required</span>
                        </div>
                        <p class="text-slate-500">The KoriePay Agent ID, Phone Number, or mapped Bank Account of the receiver.</p>
                    </li>
                </ul>
            </div>

            <div class="bg-slate-900 p-8 lg:p-12 xl:sticky xl:top-[80px] xl:h-[calc(100vh-80px)] overflow-y-auto" x-data="{ lang: 'curl' }">
                
                <h3 class="font-bold text-white text-sm mb-4 uppercase tracking-widest">Example Request</h3>
                
                <div class="flex space-x-2 mb-4 border-b border-slate-700/50 pb-2">
                    <button @click="lang = 'curl'" :class="lang === 'curl' ? 'text-korie-green' : 'text-slate-400 hover:text-slate-300'" class="text-xs font-bold font-mono">cURL</button>
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'text-korie-green' : 'text-slate-400 hover:text-slate-300'" class="text-xs font-bold font-mono">PHP</button>
                    <button @click="lang = 'node'" :class="lang === 'node' ? 'text-korie-green' : 'text-slate-400 hover:text-slate-300'" class="text-xs font-bold font-mono">Node.js</button>
                </div>

                <div class="bg-slate-800 rounded-xl p-4 font-mono text-sm text-slate-300 mb-8 overflow-x-auto shadow-inner border border-slate-700">
                    <pre x-show="lang === 'curl'" x-cloak><code><span class="text-pink-400">curl</span> https://api.koriepay.com/v1/transfers/create \
  -H <span class="text-amber-300">"Authorization: Bearer sk_test_..."</span> \
  -H <span class="text-amber-300">"Content-Type: application/json"</span> \
  -d <span class="text-amber-300">'{
    "amount": 5000000,
    "currency": "NGN",
    "recipient": "22790123456"
  }'</span></code></pre>
                </div>

                <h3 class="font-bold text-white text-sm mb-4 uppercase tracking-widest flex items-center justify-between">
                    <span>Example Response</span>
                    <span class="text-korie-green text-xs font-mono lowercase">200 ok</span>
                </h3>
                <div class="bg-slate-800 rounded-xl p-4 font-mono text-sm text-slate-300 overflow-x-auto shadow-inner border border-slate-700">
<pre><code>{
  <span class="text-sky-300">"status"</span>: <span class="text-korie-green">true</span>,
  <span class="text-sky-300">"message"</span>: <span class="text-amber-300">"Transfer queued successfully"</span>,
  <span class="text-sky-300">"data"</span>: {
    <span class="text-sky-300">"id"</span>: <span class="text-purple-400">928374</span>,
    <span class="text-sky-300">"status"</span>: <span class="text-amber-300">"processing"</span>,
    <span class="text-sky-300">"exchange_rate"</span>: <span class="text-purple-400">0.485</span>
  }
}</code></pre>
                </div>

            </div>
        </main>
    </div>
</div>
@endsection