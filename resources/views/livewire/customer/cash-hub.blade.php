<div class="max-w-md mx-auto space-y-6 pb-24 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('customer.dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-50 transition-colors active:scale-95">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Liquidity Hub</h1>
    </div>

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-sm animate-pulse">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-slate-200/50 p-1.5 rounded-[1.25rem] flex gap-1 shadow-inner">
        <button wire:click="switchTab('deposit')" class="flex-1 py-3 text-[10px] font-black uppercase tracking-[0.2em] rounded-xl transition-all {{ $activeTab === 'deposit' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            Add Funds
        </button>
        <button wire:click="switchTab('withdraw')" class="flex-1 py-3 text-[10px] font-black uppercase tracking-[0.2em] rounded-xl transition-all {{ $activeTab === 'withdraw' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            Withdraw
        </button>
    </div>

    @if($activeTab === 'deposit')
        <div class="space-y-6 animate-in slide-in-from-right-4 duration-300">
            
            <div class="flex gap-3">
                <button wire:click="setDepositMethod('bank')" class="flex-1 py-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2 {{ $depositMethod === 'bank' ? 'border-[#158987] bg-[#158987]/5 text-[#158987]' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300' }}">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <span class="text-[10px] font-black uppercase tracking-widest">Bank Transfer</span>
                </button>
                <button wire:click="setDepositMethod('agent')" class="flex-1 py-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2 {{ $depositMethod === 'agent' ? 'border-[#29B475] bg-[#29B475]/5 text-[#29B475]' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300' }}">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="text-[10px] font-black uppercase tracking-widest">Agent Cash-In</span>
                </button>
            </div>

            @if($depositMethod === 'bank')
                <div class="bg-[#020617] rounded-[2rem] p-8 text-white relative overflow-hidden shadow-2xl border border-slate-800 animate-in fade-in zoom-in-95 duration-300">
                    <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-[#158987]/30 rounded-full blur-[60px] pointer-events-none"></div>
                    
                    @if($virtualAccount)
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-8">
                                <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/20">
                                    <svg class="w-6 h-6 text-[#29B475]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                </div>
                                <span class="bg-[#29B475]/20 text-[#29B475] px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border border-[#29B475]/30 flex items-center gap-1.5">
                                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#29B475] opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-[#29B475]"></span></span>
                                    Live Account
                                </span>
                            </div>

                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1">Bank Name</p>
                            <p class="text-xl font-black tracking-tight mb-6 text-white">{{ $virtualAccount['bank'] }}</p>

                            <div class="flex justify-between items-end bg-white/5 p-4 rounded-2xl border border-white/10 backdrop-blur-sm group cursor-pointer active:scale-95 transition-transform" onclick="navigator.clipboard.writeText('{{ $virtualAccount['number'] }}'); alert('Account Number Copied!');">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1">Account Number</p>
                                    <p class="text-3xl font-mono font-black tracking-[0.1em] text-[#FCDB1A]">{{ $virtualAccount['number'] }}</p>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-[#158987] transition-colors">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </div>
                            </div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-6 mb-1">Account Name</p>
                            <p class="text-sm font-black text-white">{{ $virtualAccount['name'] }}</p>
                        </div>
                    @endif
                </div>
                
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 shadow-inner flex gap-3">
                    <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[10px] font-bold text-slate-500 leading-relaxed uppercase tracking-widest">Transfers from any traditional bank will reflect in your wallet instantly. 1.5% network fee applies.</p>
                </div>
            @endif

            @if($depositMethod === 'agent')
                
                @if(!$activeDepositToken)
                    <form wire:submit="generateDepositToken" class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 space-y-6 animate-in fade-in zoom-in-95 duration-300">
                        <div class="bg-[#158987]/10 p-4 rounded-2xl flex items-start gap-3 border border-[#158987]/20">
                            <svg class="w-5 h-5 text-[#158987] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <div>
                                <p class="text-[10px] font-black text-[#158987] uppercase tracking-widest mb-0.5">Confidential Protocol</p>
                                <p class="text-[10px] font-bold text-slate-600 leading-relaxed">Specify the exact cash amount you are handing to the agent. The token generated will be cryptographically locked to this amount to prevent fraud.</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Cash Deposit Amount</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                    <span class="text-lg font-black text-slate-400">₦</span>
                                </div>
                                <input wire:model="agentDepositAmount" type="number" required min="500" placeholder="0.00" class="w-full pl-10 pr-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-2xl font-mono font-black tracking-tighter text-slate-900 focus:bg-white focus:ring-0 focus:border-[#29B475] transition-all shadow-inner">
                            </div>
                            @error('agentDepositAmount') <span class="text-[10px] text-red-500 font-bold uppercase pl-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" wire:loading.attr="disabled" class="w-full py-4 bg-[#020617] text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-slate-800 transition-all active:scale-[0.98] shadow-xl shadow-slate-900/20 flex justify-center items-center gap-2">
                            <span wire:loading.remove wire:target="generateDepositToken">Generate Secure Token</span>
                            <span wire:loading wire:target="generateDepositToken">Encrypting...</span>
                            <svg wire:loading.remove wire:target="generateDepositToken" class="w-4 h-4 text-[#29B475]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </form>
                @else
                    <div x-data="{ showToken: false }" class="bg-[#020617] rounded-[2.5rem] p-8 text-center text-white relative overflow-hidden shadow-2xl border border-slate-800 animate-in slide-in-from-bottom-4 duration-300">
                        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#29B475 1px, transparent 1px); background-size: 20px 20px;"></div>
                        
                        <div class="relative z-10">
                            @if (session()->has('token_success'))
                                <div class="bg-[#29B475]/20 border border-[#29B475]/30 text-[#29B475] py-2 px-4 rounded-xl text-[9px] font-black uppercase tracking-widest inline-flex items-center gap-2 mb-6 shadow-inner animate-pulse">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    {{ session('token_success') }}
                                </div>
                            @endif

                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1">Locked Amount</p>
                            <h2 class="text-3xl font-mono font-black tracking-tight text-white mb-8">₦{{ number_format($agentDepositAmount) }}</h2>

                            <div class="bg-white/5 p-6 rounded-3xl border border-white/10 backdrop-blur-md inline-block min-w-[200px] relative overflow-hidden shadow-inner cursor-pointer select-none transition-all active:scale-95" 
                                 @mousedown="showToken = true" 
                                 @mouseup="showToken = false" 
                                 @mouseleave="showToken = false"
                                 @touchstart="showToken = true" 
                                 @touchend="showToken = false">
                                
                                <p x-cloak x-show="showToken" class="text-5xl font-mono font-black tracking-[0.2em] text-[#FCDB1A] animate-in fade-in zoom-in-75 duration-200">{{ $activeDepositToken }}</p>
                                
                                <div x-show="!showToken" class="flex flex-col items-center justify-center gap-2 py-2">
                                    <svg class="w-6 h-6 text-[#158987]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-300">Press & Hold to Reveal</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-center gap-2 mt-8">
                                <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">Expires strictly at {{ $tokenExpiryTime }}</p>
                            </div>

                            <button wire:click="revokeDepositToken" class="w-full py-4 mt-8 bg-red-500/10 text-red-500 border border-red-500/20 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-red-500/20 transition-all active:scale-95">
                                Revoke & Cancel Token
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endif

    @if($activeTab === 'withdraw')
        <div class="space-y-6 animate-in slide-in-from-left-4 duration-300">
            
            @if(is_null($user->transaction_pin))
                <div class="bg-red-50 border border-red-200 rounded-[2rem] p-8 text-center shadow-sm">
                    <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 mb-2">Security Hold</h3>
                    <p class="text-xs font-bold text-slate-500 mb-6">You must configure a 4-Digit Transaction PIN before you can push liquidity off-grid.</p>
                    <a href="{{ route('customer.security') }}" wire:navigate class="w-full py-4 bg-[#020617] text-white rounded-xl text-[10px] font-black uppercase tracking-[0.2em] shadow-lg flex items-center justify-center">Setup PIN Now</a>
                </div>
            @else

                <div class="flex gap-3 mb-6">
                    <button wire:click="setWithdrawMethod('bank')" class="flex-1 py-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2 {{ $withdrawMethod === 'bank' ? 'border-[#F88D25] bg-[#F88D25]/5 text-[#F88D25]' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300' }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                        <span class="text-[10px] font-black uppercase tracking-widest">To Bank Vault</span>
                    </button>
                    <button wire:click="setWithdrawMethod('agent')" class="flex-1 py-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2 {{ $withdrawMethod === 'agent' ? 'border-[#158987] bg-[#158987]/5 text-[#158987]' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300' }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="text-[10px] font-black uppercase tracking-widest">Agent Cash-Out</span>
                    </button>
                </div>

                @if(!$agentWithdrawToken)
                    <form wire:submit="processWithdrawal" class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 space-y-6 animate-in fade-in zoom-in-95 duration-300">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Debit From Vault</label>
                                <select wire:model="withdrawCurrency" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:ring-[#F88D25] focus:border-[#F88D25] transition-all shadow-inner">
                                    <option value="NGN">🇳🇬 NGN Wallet (₦)</option>
                                    <option value="XOF">🇳🇪 XOF Wallet (CFA)</option>
                                </select>
                            </div>
                        </div>

                        @if($withdrawMethod === 'bank' && $user->kyc_status !== 'verified')
                            <div class="bg-red-50 border border-red-200 rounded-3xl p-6 text-center shadow-inner animate-in fade-in zoom-in-95 duration-300 mt-4">
                                <div class="w-14 h-14 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-red-200">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <h3 class="text-lg font-black text-slate-900 mb-1 tracking-tight">Tier 3 Authorization Required</h3>
                                <p class="text-[10px] font-bold text-slate-500 mb-6 uppercase tracking-widest leading-relaxed">Direct bank withdrawals require full BVN/NIN verification to comply with Anti-Money Laundering (AML) directives.</p>
                                
                                <a href="{{ route('customer.kyc') }}" wire:navigate class="w-full py-4 bg-red-500 text-white rounded-xl text-[10px] font-black uppercase tracking-[0.2em] shadow-lg shadow-red-500/20 hover:bg-red-600 active:scale-95 transition-all flex items-center justify-center gap-2">
                                    Upgrade Node to Tier 3
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            </div>
                        @else
                            @if($withdrawMethod === 'bank')
                                <div class="animate-in fade-in slide-in-from-top-2 duration-300">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Destination Bank</label>
                                    <select wire:model="linkedBank" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:ring-[#F88D25] focus:border-[#F88D25] transition-all shadow-inner">
                                        <option value="">Select Linked Account...</option>
                                        <option value="gtb_123">Guaranty Trust Bank (0123****890)</option>
                                    </select>
                                    <a href="{{ route('customer.vaults') }}" wire:navigate class="text-[9px] font-bold text-[#158987] mt-2 block pl-1 uppercase tracking-widest hover:text-[#29B475]">+ Manage Linked Banks</a>
                                </div>
                            @endif

                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Withdrawal Amount</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                        <span class="text-lg font-black text-slate-400">₦</span>
                                    </div>
                                    <input wire:model.live.debounce.300ms="withdrawAmount" type="number" required placeholder="0.00" class="w-full pl-10 pr-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-2xl font-mono font-black tracking-tighter text-slate-900 focus:bg-white focus:ring-0 focus:border-[#F88D25] transition-all shadow-inner">
                                </div>
                                @error('withdrawAmount') <span class="text-[10px] text-red-500 font-bold uppercase pl-1 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex justify-between items-center">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Network Fee</span>
                                <span class="text-xs font-mono font-black text-slate-900">
                                    {{ $withdrawMethod === 'bank' ? '₦50.00' : '₦0.00' }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">Authorization PIN</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400 group-focus-within:text-[#F88D25] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    </div>
                                    <input wire:model="transactionPin" type="password" maxlength="4" required placeholder="••••" class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-xl font-mono font-black tracking-[0.3em] text-[#F88D25] focus:bg-white focus:ring-0 focus:border-[#F88D25] transition-all shadow-inner text-center">
                                </div>
                                @error('transactionPin') <span class="text-[10px] text-red-500 font-bold uppercase pl-1 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <button type="submit" wire:loading.attr="disabled" class="w-full py-4 bg-[#020617] text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-slate-800 transition-all active:scale-[0.98] shadow-xl shadow-slate-900/20 flex justify-center items-center gap-2">
                                <span wire:loading.remove wire:target="processWithdrawal">
                                    {{ $withdrawMethod === 'agent' ? 'Freeze Funds & Generate Token' : 'Off-Ramp Liquidity' }}
                                </span>
                                <span wire:loading wire:target="processWithdrawal">Processing...</span>
                                <svg wire:loading.remove wire:target="processWithdrawal" class="w-4 h-4 text-[#F88D25]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </button>
                        @endif
                    </form>
                @else
                    <div x-data="{ showToken: false }" class="bg-[#020617] rounded-[2.5rem] p-8 text-center text-white relative overflow-hidden shadow-2xl border border-slate-800 animate-in slide-in-from-bottom-4 duration-300">
                        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#F88D25 1px, transparent 1px); background-size: 20px 20px;"></div>
                        
                        <div class="relative z-10">
                            @if (session()->has('withdraw_token_success'))
                                <div class="bg-[#F88D25]/20 border border-[#F88D25]/30 text-[#F88D25] py-2 px-4 rounded-xl text-[9px] font-black uppercase tracking-widest inline-flex items-center gap-2 mb-6 shadow-inner animate-pulse">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    {{ session('withdraw_token_success') }}
                                </div>
                            @endif

                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1">Approved Cash-Out Amount</p>
                            <h2 class="text-3xl font-mono font-black tracking-tight text-white mb-8">₦{{ number_format($withdrawAmount) }}</h2>

                            <div class="bg-white/5 p-6 rounded-3xl border border-white/10 backdrop-blur-md inline-block min-w-[200px] relative overflow-hidden shadow-inner cursor-pointer select-none transition-all active:scale-95" 
                                 @mousedown="showToken = true" 
                                 @mouseup="showToken = false" 
                                 @mouseleave="showToken = false"
                                 @touchstart="showToken = true" 
                                 @touchend="showToken = false">
                                
                                <p x-cloak x-show="showToken" class="text-4xl sm:text-5xl font-mono font-black tracking-[0.1em] text-[#F88D25] animate-in fade-in zoom-in-75 duration-200">{{ $agentWithdrawToken }}</p>
                                
                                <div x-show="!showToken" class="flex flex-col items-center justify-center gap-2 py-2">
                                    <svg class="w-6 h-6 text-[#F88D25]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-300">Press & Hold to Reveal</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-center gap-2 mt-8">
                                <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">Token expires at {{ $withdrawExpiryTime }}</p>
                            </div>

                            <button wire:click="revokeWithdrawToken" class="w-full py-4 mt-8 bg-red-500/10 text-red-500 border border-red-500/20 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-red-500/20 transition-all active:scale-95">
                                Cancel & Refund Wallet
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endif
</div>