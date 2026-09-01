@extends('layouts.public')

@section('title', 'API Documentation')

@section('content')
<div class="bg-white">
    <div class="container mx-auto px-6 flex flex-col md:flex-row items-start relative">

        <aside class="w-full md:w-64 flex-shrink-0 pt-10 pb-20 md:sticky md:top-[80px] md:h-[calc(100vh-80px)] overflow-y-auto border-r border-slate-200 pr-6 hidden md:block">
            <div class="mb-8">
                <a href="#" class="inline-flex items-center space-x-2 text-sm font-bold text-korie-teal bg-emerald-success/30 px-3 py-1.5 rounded-lg border border-korie-green/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span>Developer Sandbox</span>
                </a>
            </div>

            <nav class="space-y-8 text-sm">
                <div>
                    <h3 class="font-black text-slate-900 uppercase tracking-widest text-xs mb-3">Getting Started</h3>
                    <ul class="space-y-2 text-slate-500 font-medium">
                        <li><a href="#" class="text-korie-green font-bold">Introduction</a></li>
                        <li><a href="#" class="hover:text-korie-teal transition">Authentication</a></li>
                        <li><a href="#" class="hover:text-korie-teal transition">Errors</a></li>
                        <li><a href="#" class="hover:text-korie-teal transition">Webhooks</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-black text-slate-900 uppercase tracking-widest text-xs mb-3">Transfers</h3>
                    <ul class="space-y-2 text-slate-500 font-medium">
                        <li><a href="#" class="hover:text-korie-teal transition">Initiate Transfer</a></li>
                        <li><a href="#" class="hover:text-korie-teal transition">Verify Transfer</a></li>
                        <li><a href="#" class="hover:text-korie-teal transition">Fetch Transfer</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-black text-slate-900 uppercase tracking-widest text-xs mb-3">Agency</h3>
                    <ul class="space-y-2 text-slate-500 font-medium">
                        <li><a href="#" class="hover:text-korie-teal transition">Create Agent</a></li>
                        <li><a href="#" class="hover:text-korie-teal transition">Submit KYC</a></li>
                        <li><a href="#" class="hover:text-korie-teal transition">Agent Balances</a></li>
                    </ul>
                </div>
            </nav>
        </aside>

        <main class="flex-1 min-w-0 pt-10 pb-24 md:pl-12 lg:pl-16">

            <div class="max-w-3xl">
                <h1 class="text-4xl font-extrabold text-slate-900 mb-4 tracking-tight">KoriePay API Reference</h1>
                <p class="text-lg text-slate-500 mb-10 leading-relaxed">
                    The KoriePay API is organized around REST. Our API has predictable resource-oriented URLs, accepts form-encoded request bodies, returns JSON-encoded responses, and uses standard HTTP response codes, authentication, and verbs.
                </p>

                <hr class="border-slate-200 mb-10">

                <h2 class="text-2xl font-bold text-slate-900 mb-4" id="authentication">Authentication</h2>
                <p class="text-slate-500 mb-6 leading-relaxed">
                    Authenticate your API calls by including your secret key in the Authorization header of every request. You can manage your API keys in the Developer Dashboard.
                </p>

                <div class="bg-amber-50 border-l-4 border-amber-warning p-4 rounded-r-xl mb-10">
                    <p class="text-sm text-slate-700 font-medium">
                        <strong class="text-amber-warning font-bold">Keep it secret:</strong> Your API keys carry many privileges, so be sure to keep them secure! Do not share your secret API keys in publicly accessible areas such as GitHub, client-side code, and so forth.
                    </p>
                </div>

                <p class="text-slate-500 mb-4 leading-relaxed">All API requests must be made over HTTPS. Calls made over plain HTTP will fail. API requests without authentication will also fail.</p>

                <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl font-mono text-sm text-slate-700 mb-10 overflow-x-auto">
                    Authorization: Bearer <span class="text-korie-teal font-bold">{{ env('PAYSTACK_SECRET_KEY') }}</span>
                </div>

                <hr class="border-slate-200 mb-10">

                <div class="flex flex-col lg:flex-row gap-10 items-start">

                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-slate-900 mb-4" id="initiate-transfer">Initiate Cross-Border Transfer</h2>
                        <p class="text-slate-500 mb-6 leading-relaxed">
                            This endpoint allows you to move liquidity instantly from an NGN wallet to an XOF destination. The system will auto-calculate the exchange rate based on the real-time institutional oracle.
                        </p>

                        <h4 class="font-bold text-slate-900 uppercase tracking-widest text-xs mb-3">Endpoint</h4>
                        <div class="inline-flex items-center space-x-3 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 font-mono text-sm mb-6">
                            <span class="bg-korie-green text-white px-2 py-0.5 rounded font-bold text-xs">POST</span>
                            <span class="text-slate-700">https://api.koriepay.com/v1/transfers</span>
                        </div>

                        <h4 class="font-bold text-slate-900 uppercase tracking-widest text-xs mb-3">Parameters</h4>
                        <ul class="space-y-4 text-sm">
                            <li class="border-b border-slate-100 pb-4">
                                <div class="flex items-center space-x-2 mb-1">
                                    <code class="font-bold text-slate-900">amount</code>
                                    <span class="text-xs text-slate-400">integer</span>
                                    <span class="text-[10px] uppercase font-bold text-red-critical bg-red-50 px-1.5 py-0.5 rounded">Required</span>
                                </div>
                                <p class="text-slate-500">Amount to transfer in the lowest denomination (e.g., kobo or centimes).</p>
                            </li>
                            <li class="border-b border-slate-100 pb-4">
                                <div class="flex items-center space-x-2 mb-1">
                                    <code class="font-bold text-slate-900">recipient_currency</code>
                                    <span class="text-xs text-slate-400">string</span>
                                    <span class="text-[10px] uppercase font-bold text-red-critical bg-red-50 px-1.5 py-0.5 rounded">Required</span>
                                </div>
                                <p class="text-slate-500">The 3-letter ISO code for the destination currency (e.g., <code class="bg-slate-100 px-1 rounded">XOF</code>).</p>
                            </li>
                        </ul>
                    </div>

                    <div class="w-full lg:w-[450px] flex-shrink-0" x-data="{ tab: 'curl' }">
                        <div class="bg-slate-900 rounded-t-2xl border border-slate-800 overflow-hidden shadow-2xl">
                            <div class="flex border-b border-slate-700/50 bg-slate-800/30 px-2 pt-2">
                                <button @click="tab = 'curl'" :class="tab === 'curl' ? 'text-korie-green border-korie-green' : 'text-slate-400 border-transparent hover:text-slate-300'" class="px-4 py-2 text-xs font-bold font-mono border-b-2 transition-colors">cURL</button>
                                <button @click="tab = 'php'" :class="tab === 'php' ? 'text-korie-green border-korie-green' : 'text-slate-400 border-transparent hover:text-slate-300'" class="px-4 py-2 text-xs font-bold font-mono border-b-2 transition-colors">PHP</button>
                                <button @click="tab = 'node'" :class="tab === 'node' ? 'text-korie-green border-korie-green' : 'text-slate-400 border-transparent hover:text-slate-300'" class="px-4 py-2 text-xs font-bold font-mono border-b-2 transition-colors">Node.js</button>
                            </div>

                            <div class="p-4 overflow-x-auto text-sm font-mono text-slate-300">
                                <pre x-show="tab === 'curl'" x-cloak><code><span class="text-pink-400">curl</span> https://api.koriepay.com/v1/transfers \
-H <span class="text-amber-300">"Authorization: Bearer sk_test_4eC39Hq..."</span> \
-H <span class="text-amber-300">"Content-Type: application/json"</span> \
-d <span class="text-amber-300">'{
  "amount": 10000000,
  "recipient_currency": "XOF",
  "recipient_account": "22790123456"
}'</span></code></pre>
                                <pre x-show="tab === 'php'" x-cloak><code><span class="text-slate-400">&lt;?php</span>
<span class="text-pink-400">$korie</span> = <span class="text-pink-400">new</span> \KoriePay\Client(<span class="text-amber-300">'sk_test_4eC...'</span>);

<span class="text-pink-400">$transfer</span> = <span class="text-pink-400">$korie</span>->transfers->create([
    <span class="text-amber-300">'amount'</span> => <span class="text-purple-400">10000000</span>,
    <span class="text-amber-300">'recipient_currency'</span> => <span class="text-amber-300">'XOF'</span>,
    <span class="text-amber-300">'recipient_account'</span> => <span class="text-amber-300">'22790123456'</span>
]);</code></pre>
                                <pre x-show="tab === 'node'" x-cloak><code><span class="text-pink-400">const</span> koriepay = <span class="text-sky-300">require</span>(<span class="text-amber-300">'koriepay'</span>)(<span class="text-amber-300">'sk_test_4eC...'</span>);

<span class="text-pink-400">const</span> transfer = <span class="text-pink-400">await</span> koriepay.transfers.<span class="text-sky-300">create</span>({
  amount: <span class="text-purple-400">10000000</span>,
  recipient_currency: <span class="text-amber-300">'XOF'</span>,
  recipient_account: <span class="text-amber-300">'22790123456'</span>
});</code></pre>
                            </div>
                        </div>

                        <div class="mt-4 bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
                            <div class="flex items-center px-4 py-2 border-b border-slate-700/50 bg-slate-800/30">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Response</span>
                                <span class="ml-auto flex items-center space-x-1.5"><span class="w-2 h-2 rounded-full bg-korie-green"></span><span class="text-[10px] text-korie-green font-bold font-mono">200 OK</span></span>
                            </div>
                            <div class="p-4 overflow-x-auto text-sm font-mono text-slate-300">
<pre><code>{
  <span class="text-sky-300">"status"</span>: <span class="text-korie-green">true</span>,
  <span class="text-sky-300">"message"</span>: <span class="text-amber-300">"Transfer initiated"</span>,
  <span class="text-sky-300">"data"</span>: {
    <span class="text-sky-300">"reference"</span>: <span class="text-amber-300">"trx_983hf9f..."</span>,
    <span class="text-sky-300">"amount"</span>: <span class="text-purple-400">10000000</span>,
    <span class="text-sky-300">"settlement_amount"</span>: <span class="text-purple-400">4850000</span>,
    <span class="text-sky-300">"currency"</span>: <span class="text-amber-300">"XOF"</span>,
    <span class="text-sky-300">"status"</span>: <span class="text-amber-300">"processing"</span>
  }
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
