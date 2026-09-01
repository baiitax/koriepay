<?php $__env->startSection('title', 'Borderless Liquidity Grid'); ?>

<?php $__env->startSection('content'); ?>

    <section class="relative pt-20 pb-24 lg:pt-32 lg:pb-32 overflow-hidden bg-slate-50">
        <div class="container mx-auto px-6 grid lg:grid-cols-2 gap-12 items-center relative z-10">
            <div class="max-w-2xl">
                <div class="inline-flex items-center space-x-2 bg-emerald-success px-4 py-2 rounded-full mb-6 border border-korie-green/20 shadow-sm">
                    <span class="w-2.5 h-2.5 bg-korie-green rounded-full animate-pulse"></span>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider" x-show="lang === 'en'">Live in Niger & Nigeria</span>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider" x-show="lang === 'fr'" x-cloak>En direct au Niger et au Nigéria</span>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider" x-show="lang === 'ha'" x-cloak>Kai tsaye a Nijar da Najeriya</span>
                </div>
                
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold text-slate-900 mb-6 leading-[1.1] tracking-tight">
                    <span x-show="lang === 'en'">Banking Without Barriers, Powered by Trust.</span>
                    <span x-show="lang === 'fr'" x-cloak>Banque Sans Frontières, Alimentée par la Confiance.</span>
                    <span x-show="lang === 'ha'" x-cloak>Banki Ba Tare Da Shinge Ba, Mai Ƙarfi Ta Amana.</span>
                </h1>
                
                <p class="text-lg text-slate-500 mb-10 leading-relaxed font-medium">
                    <span x-show="lang === 'en'">Send money across Niger and Nigeria instantly. Join Adashi savings pools, get Shariah-compliant financing, and build your enterprise agent network.</span>
                    <span x-show="lang === 'fr'" x-cloak>Envoyez de l'argent instantanément. Rejoignez des pools d'épargne Adashi, obtenez un financement conforme à la charia et développez votre réseau d'agents.</span>
                    <span x-show="lang === 'ha'" x-cloak>Aika kuɗi tsakanin Nijar da Najeriya nan take. Shiga rukunin adana kuɗi na Adashi, samun rance mai bin ka'idojin Musulunci.</span>
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 mb-10">
                    <a href="<?php echo e(route('register')); ?>" class="text-center bg-slate-900 text-white px-8 py-4 rounded-xl font-bold hover:bg-slate-800 shadow-xl shadow-slate-900/10 transition-all active:scale-[0.98]">
                        <span x-show="lang === 'en'">Open Account - Free</span>
                        <span x-show="lang === 'fr'" x-cloak>Ouvrir un Compte</span>
                        <span x-show="lang === 'ha'" x-cloak>Buɗe Asusun Kyauta</span>
                    </a>
                    <a href="#solutions" class="text-center bg-white border-2 border-slate-200 text-slate-700 px-8 py-4 rounded-xl font-bold hover:border-korie-green hover:text-korie-teal transition-all active:scale-[0.98]">
                        <span x-show="lang === 'en'">Become an Agent</span>
                        <span x-show="lang === 'fr'" x-cloak>Devenir Agent</span>
                        <span x-show="lang === 'ha'" x-cloak>Zama Wakili</span>
                    </a>
                </div>

                <div class="flex flex-wrap items-center gap-6 text-sm font-bold text-slate-500">
                    <div class="flex items-center space-x-2">
                        <span class="text-korie-green text-lg">✔</span>
                        <span x-show="lang === 'en'">CBN Licensed</span>
                        <span x-show="lang === 'fr'" x-cloak>Licence CBN</span>
                        <span x-show="lang === 'ha'" x-cloak>Lasisi na CBN</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-korie-green text-lg">✔</span>
                        <span x-show="lang === 'en'">BCEAO Approved</span>
                        <span x-show="lang === 'fr'" x-cloak>Approuvé BCEAO</span>
                        <span x-show="lang === 'ha'" x-cloak>Amincewar BCEAO</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-korie-green text-lg">✔</span>
                        <span x-show="lang === 'en'">NDIC Insured</span>
                        <span x-show="lang === 'fr'" x-cloak>Assuré NDIC</span>
                        <span x-show="lang === 'ha'" x-cloak>Inshorar NDIC</span>
                    </div>
                </div>
            </div>
            
            <div class="relative mt-12 lg:mt-0 lg:ml-10">
                <div class="absolute inset-0 bg-gradient-to-tr from-korie-green/20 to-korie-teal/20 blur-3xl -z-10 rounded-[3rem]"></div>
                
                <div class="bg-white rounded-[2rem] shadow-2xl p-8 border border-slate-200/60 relative z-10 text-center min-h-[400px] flex flex-col justify-center items-center">
                    <div class="w-20 h-20 bg-emerald-success rounded-2xl flex items-center justify-center shadow-inner mb-6 border border-korie-green/20 text-korie-teal">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">KoriePay Terminal</h3>
                    <p class="text-slate-500 font-medium mt-2">
                        <span x-show="lang === 'en'">Connect to the grid to view live settlements.</span>
                        <span x-show="lang === 'fr'" x-cloak>Connectez-vous au réseau pour voir les règlements en direct.</span>
                        <span x-show="lang === 'ha'" x-cloak>Haɗa kan cibiyar don ganin yadda ake biyan kuɗi.</span>
                    </p>
                </div>
                
                <div class="absolute -bottom-6 -left-8 bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-6 border border-slate-100 z-20">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                        <span x-show="lang === 'en'">Total Processed</span>
                        <span x-show="lang === 'fr'" x-cloak>Total Traité</span>
                        <span x-show="lang === 'ha'" x-cloak>Jimlar Abin Da Aka Sarrafa</span>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight font-mono">₦2.4B <span class="text-slate-300 font-normal">/</span> 48M XOF</div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white relative">
        <div class="container mx-auto px-6">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">
                    <span x-show="lang === 'en'">Instant Cross-Border Transfers</span>
                    <span x-show="lang === 'fr'" x-cloak>Transferts Transfrontaliers Instantanés</span>
                    <span x-show="lang === 'ha'" x-cloak>Canja Wurin Kuɗi Na Ƙetare Nan Take</span>
                </h2>
                <p class="text-xl text-slate-500">
                    <span x-show="lang === 'en'">Move liquidity between Nigeria (₦) and Niger (XOF) with zero latency.</span>
                    <span x-show="lang === 'fr'" x-cloak>Déplacez des liquidités entre le Nigéria (₦) et le Niger (XOF) sans latence.</span>
                    <span x-show="lang === 'ha'" x-cloak>Aika kuɗi tsakanin Najeriya (₦) da Nijar (XOF) ba tare da jinkiri ba.</span>
                </p>
            </div>
            
            <div class="max-w-4xl mx-auto bg-slate-50 rounded-[2rem] p-8 md:p-10 border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-korie-green/10 rounded-full blur-2xl"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between mb-8 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm gap-4">
                    <div class="w-full text-center md:text-left">
                        <div class="text-sm font-bold text-slate-400 mb-1 uppercase tracking-wider">
                            <span x-show="lang === 'en'">From Nigeria</span>
                            <span x-show="lang === 'fr'" x-cloak>Depuis le Nigéria</span>
                            <span x-show="lang === 'ha'" x-cloak>Daga Najeriya</span>
                        </div>
                        <div class="text-4xl font-black text-slate-900 font-mono tracking-tighter">₦100,000</div>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-korie-green to-korie-teal rounded-full flex items-center justify-center shadow-lg shadow-korie-green/20 flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <div class="w-full text-center md:text-right">
                        <div class="text-sm font-bold text-slate-400 mb-1 uppercase tracking-wider">
                            <span x-show="lang === 'en'">To Niger</span>
                            <span x-show="lang === 'fr'" x-cloak>Vers le Niger</span>
                            <span x-show="lang === 'ha'" x-cloak>Zuwa Nijar</span>
                        </div>
                        <div class="text-4xl font-black text-slate-900 font-mono tracking-tighter">48,500 <span class="text-xl">XOF</span></div>
                    </div>
                </div>
                
                <div class="space-y-4 text-sm bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative z-10">
                    <div class="flex justify-between items-center py-2 border-b border-slate-50">
                        <span class="text-slate-500 font-medium" x-show="lang === 'en'">Mid-Market Rate</span>
                        <span class="text-slate-500 font-medium" x-show="lang === 'fr'" x-cloak>Taux Moyen</span>
                        <span class="text-slate-500 font-medium" x-show="lang === 'ha'" x-cloak>Darajar Canji</span>
                        <span class="font-bold text-slate-900 bg-slate-100 px-3 py-1 rounded-md">1 NGN = 0.485 XOF</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-50">
                        <span class="text-slate-500 font-medium" x-show="lang === 'en'">Transfer Fee</span>
                        <span class="text-slate-500 font-medium" x-show="lang === 'fr'" x-cloak>Frais de Transfert</span>
                        <span class="text-slate-500 font-medium" x-show="lang === 'ha'" x-cloak>Kudin Aika</span>
                        <span class="font-bold text-korie-green bg-emerald-success/50 px-3 py-1 rounded-md">
                            <span x-show="lang === 'en'">₦0 (Free)</span>
                            <span x-show="lang === 'fr'" x-cloak>₦0 (Gratuit)</span>
                            <span x-show="lang === 'ha'" x-cloak>₦0 (Kyauta)</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-slate-500 font-medium" x-show="lang === 'en'">Settlement Time</span>
                        <span class="text-slate-500 font-medium" x-show="lang === 'fr'" x-cloak>Temps de Règlement</span>
                        <span class="text-slate-500 font-medium" x-show="lang === 'ha'" x-cloak>Lokacin Biyan Kuɗi</span>
                        <span class="font-bold text-slate-900 flex items-center">
                            <span class="w-2 h-2 rounded-full bg-amber-warning mr-2"></span> 
                            <span x-show="lang === 'en'">Instant</span>
                            <span x-show="lang === 'fr'" x-cloak>Instantané</span>
                            <span x-show="lang === 'ha'" x-cloak>Nan Take</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="solutions" class="py-24 bg-slate-50 relative border-t border-slate-200">
        <div class="container mx-auto px-6">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">
                    <span x-show="lang === 'en'">Built For Your Specific Needs</span>
                    <span x-show="lang === 'fr'" x-cloak>Conçu Pour Vos Besoins Spécifiques</span>
                    <span x-show="lang === 'ha'" x-cloak>An Gina Don Buƙatunku</span>
                </h2>
                <p class="text-lg font-medium text-slate-500">
                    <span x-show="lang === 'en'">Whether you are managing personal finances, running a local shop, or operating a regional liquidity network.</span>
                    <span x-show="lang === 'fr'" x-cloak>Que vous gériez vos finances personnelles, un commerce local ou un réseau de liquidités régional.</span>
                    <span x-show="lang === 'ha'" x-cloak>Ko kuna kula da kuɗin ku, gudanar da shago, ko cibiyar kuɗi.</span>
                </p>
            </div>
            
            <div class="grid lg:grid-cols-3 gap-8 items-stretch">
                <div class="bg-white rounded-[2rem] p-8 border border-slate-200 hover:shadow-xl transition-all flex flex-col group">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-8 border border-slate-100 text-korie-teal">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-6 tracking-tight">
                        <span x-show="lang === 'en'">For Individuals</span>
                        <span x-show="lang === 'fr'" x-cloak>Pour les Particuliers</span>
                        <span x-show="lang === 'ha'" x-cloak>Don Mutane</span>
                    </h3>
                    <ul class="space-y-4 mb-10 flex-grow">
                        <li class="flex items-start space-x-3 font-medium text-slate-600">
                            <span class="text-korie-teal">✔</span> 
                            <span x-show="lang === 'en'">Free P2P transfers across borders</span>
                            <span x-show="lang === 'fr'" x-cloak>Transferts P2P gratuits</span>
                            <span x-show="lang === 'ha'" x-cloak>Aika kuɗi kyauta a kan iyakoki</span>
                        </li>
                        <li class="flex items-start space-x-3 font-medium text-slate-600">
                            <span class="text-korie-teal">✔</span> 
                            <span x-show="lang === 'en'">Join automated Adashi savings pools</span>
                            <span x-show="lang === 'fr'" x-cloak>Rejoignez des pools d'épargne Adashi</span>
                            <span x-show="lang === 'ha'" x-cloak>Shiga rukunin adana kuɗi na Adashi</span>
                        </li>
                        <li class="flex items-start space-x-3 font-medium text-slate-600">
                            <span class="text-korie-teal">✔</span> 
                            <span x-show="lang === 'en'">Access Shariah-compliant financing</span>
                            <span x-show="lang === 'fr'" x-cloak>Financement conforme à la Charia</span>
                            <span x-show="lang === 'ha'" x-cloak>Samun rance mai bin ka'idar Musulunci</span>
                        </li>
                    </ul>
                    <a href="<?php echo e(route('register')); ?>" class="block w-full text-center bg-white border-2 border-slate-200 text-slate-700 px-6 py-4 rounded-xl font-bold mt-auto hover:border-korie-green hover:text-korie-teal transition-colors">
                        <span x-show="lang === 'en'">Open Personal Account</span>
                        <span x-show="lang === 'fr'" x-cloak>Ouvrir un Compte Personnel</span>
                        <span x-show="lang === 'ha'" x-cloak>Buɗe Asusun Mutum</span>
                    </a>
                </div>
                
                <div class="bg-slate-900 rounded-[2rem] p-8 shadow-2xl relative overflow-hidden flex flex-col transform lg:-translate-y-4">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-korie-green/10 rounded-full blur-3xl -mr-20 -mt-20"></div>
                    <div class="absolute top-6 right-6 bg-amber-warning text-slate-900 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                        <span x-show="lang === 'en'">Popular</span>
                        <span x-show="lang === 'fr'" x-cloak>Populaire</span>
                        <span x-show="lang === 'ha'" x-cloak>Mafi Farin Jini</span>
                    </div>
                    
                    <div class="w-16 h-16 bg-gradient-to-br from-korie-green to-korie-teal rounded-2xl flex items-center justify-center mb-8 text-white relative z-10">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                    <h3 class="text-2xl font-black text-white mb-6 relative z-10">
                        <span x-show="lang === 'en'">For Agents</span>
                        <span x-show="lang === 'fr'" x-cloak>Pour les Agents</span>
                        <span x-show="lang === 'ha'" x-cloak>Don Wakilai</span>
                    </h3>
                    <ul class="space-y-4 mb-10 flex-grow relative z-10">
                        <li class="flex items-start space-x-3 font-medium text-slate-300">
                            <span class="text-korie-green">✔</span> 
                            <span x-show="lang === 'en'">Earn up to 1.5% per transaction</span>
                            <span x-show="lang === 'fr'" x-cloak>Gagnez jusqu'à 1,5% par transaction</span>
                            <span x-show="lang === 'ha'" x-cloak>Sami kusan 1.5% a kowane ciniki</span>
                        </li>
                        <li class="flex items-start space-x-3 font-medium text-slate-300">
                            <span class="text-korie-green">✔</span> 
                            <span x-show="lang === 'en'">Instant commission settlement</span>
                            <span x-show="lang === 'fr'" x-cloak>Règlement instantané des commissions</span>
                            <span x-show="lang === 'ha'" x-cloak>Samun kuɗin fito nan take</span>
                        </li>
                        <li class="flex items-start space-x-3 font-medium text-slate-300">
                            <span class="text-korie-green">✔</span> 
                            <span x-show="lang === 'en'">Earn ₦150k - ₦2M monthly</span>
                            <span x-show="lang === 'fr'" x-cloak>Gagnez 150k à 2M ₦ par mois</span>
                            <span x-show="lang === 'ha'" x-cloak>Sami ₦150k zuwa ₦2M duk wata</span>
                        </li>
                    </ul>
                    <a href="<?php echo e(route('register')); ?>" class="block w-full text-center bg-gradient-to-r from-korie-green to-korie-teal text-white px-6 py-4 rounded-xl font-bold mt-auto relative z-10 hover:shadow-lg hover:shadow-korie-green/20 transition-all active:scale-[0.98]">
                        <span x-show="lang === 'en'">Become an Agent</span>
                        <span x-show="lang === 'fr'" x-cloak>Devenir Agent</span>
                        <span x-show="lang === 'ha'" x-cloak>Zama Wakili</span>
                    </a>
                </div>
                
                <div class="bg-white rounded-[2rem] p-8 border border-slate-200 hover:shadow-xl transition-all flex flex-col group">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-8 border border-slate-100 text-korie-teal">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-6 tracking-tight">
                        <span x-show="lang === 'en'">For Aggregators</span>
                        <span x-show="lang === 'fr'" x-cloak>Pour les Agrégateurs</span>
                        <span x-show="lang === 'ha'" x-cloak>Don Masu Haɗawa</span>
                    </h3>
                    <ul class="space-y-4 mb-10 flex-grow">
                        <li class="flex items-start space-x-3 font-medium text-slate-600">
                            <span class="text-korie-teal">✔</span> 
                            <span x-show="lang === 'en'">Manage vast agent networks</span>
                            <span x-show="lang === 'fr'" x-cloak>Gérez de vastes réseaux d'agents</span>
                            <span x-show="lang === 'ha'" x-cloak>Kula da manyan cibiyoyin wakilai</span>
                        </li>
                        <li class="flex items-start space-x-3 font-medium text-slate-600">
                            <span class="text-korie-teal">✔</span> 
                            <span x-show="lang === 'en'">Earn 0.3%-0.7% network override</span>
                            <span x-show="lang === 'fr'" x-cloak>Gagnez 0,3% à 0,7% sur le réseau</span>
                            <span x-show="lang === 'ha'" x-cloak>Sami 0.3%-0.7% na cibiyar sadarwa</span>
                        </li>
                        <li class="flex items-start space-x-3 font-medium text-slate-600">
                            <span class="text-korie-teal">✔</span> 
                            <span x-show="lang === 'en'">Direct KYC approval authority</span>
                            <span x-show="lang === 'fr'" x-cloak>Autorité directe d'approbation KYC</span>
                            <span x-show="lang === 'ha'" x-cloak>Ikon amincewa da KYC</span>
                        </li>
                    </ul>
                    <a href="<?php echo e(route('support.contact')); ?>" class="block w-full text-center bg-white border-2 border-slate-200 text-slate-700 px-6 py-4 rounded-xl font-bold mt-auto hover:border-korie-green hover:text-korie-teal transition-colors">
                        <span x-show="lang === 'en'">Contact Enterprise Sales</span>
                        <span x-show="lang === 'fr'" x-cloak>Contacter les Ventes</span>
                        <span x-show="lang === 'ha'" x-cloak>Tuntuɓi Masu Siyarwa</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-slate-900 relative overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-korie-teal/20 blur-[100px] rounded-full pointer-events-none"></div>

        <div class="container mx-auto px-6 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-extrabold text-white mb-6 tracking-tight">
                    <span x-show="lang === 'en'">Trusted across West Africa</span>
                    <span x-show="lang === 'fr'" x-cloak>De confiance en Afrique de l'Ouest</span>
                    <span x-show="lang === 'ha'" x-cloak>Amintacce a fadin Afirka ta Yamma</span>
                </h2>
                <p class="text-slate-400 text-lg">
                    <span x-show="lang === 'en'">Live network telemetry from the KoriePay Core.</span>
                    <span x-show="lang === 'fr'" x-cloak>Télémétrie en direct du réseau KoriePay.</span>
                    <span x-show="lang === 'ha'" x-cloak>Bayanan cibiyar sadarwa na KoriePay.</span>
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-slate-800/80 rounded-2xl p-8 border border-slate-700 backdrop-blur-md">
                    <div class="text-slate-400 font-bold text-xs mb-3 uppercase tracking-wider">
                        <span x-show="lang === 'en'">Transactions Today</span>
                        <span x-show="lang === 'fr'" x-cloak>Transactions Aujourd'hui</span>
                        <span x-show="lang === 'ha'" x-cloak>Hada-Hadar Yau</span>
                    </div>
                    <div class="text-4xl font-black text-white font-mono tracking-tighter mb-2">47,293</div>
                    <div class="text-korie-green text-sm font-bold bg-emerald-success/10 w-fit px-2 py-1 rounded">↑ 12%</div>
                </div>
                
                <div class="bg-slate-800/80 rounded-2xl p-8 border border-slate-700 backdrop-blur-md">
                    <div class="text-slate-400 font-bold text-xs mb-3 uppercase tracking-wider">
                        <span x-show="lang === 'en'">Volume Processed</span>
                        <span x-show="lang === 'fr'" x-cloak>Volume Traité</span>
                        <span x-show="lang === 'ha'" x-cloak>Jimlar Kuɗin</span>
                    </div>
                    <div class="text-4xl font-black text-white font-mono tracking-tighter mb-2">₦2.4B</div>
                    <div class="text-slate-400 text-sm font-bold font-mono">/ 48.5M XOF</div>
                </div>
                
                <div class="bg-slate-800/80 rounded-2xl p-8 border border-slate-700 backdrop-blur-md">
                    <div class="text-slate-400 font-bold text-xs mb-3 uppercase tracking-wider">
                        <span x-show="lang === 'en'">Active Agents</span>
                        <span x-show="lang === 'fr'" x-cloak>Agents Actifs</span>
                        <span x-show="lang === 'ha'" x-cloak>Wakilai Masu Aiki</span>
                    </div>
                    <div class="text-4xl font-black text-white font-mono tracking-tighter mb-2">14,837</div>
                    <div class="text-korie-green text-sm font-bold bg-emerald-success/10 w-fit px-2 py-1 rounded">↑ 247</div>
                </div>
                
                <div class="bg-slate-800/80 rounded-2xl p-8 border border-slate-700 backdrop-blur-md">
                    <div class="text-slate-400 font-bold text-xs mb-3 uppercase tracking-wider">
                        <span x-show="lang === 'en'">Average Response</span>
                        <span x-show="lang === 'fr'" x-cloak>Réponse Moyenne</span>
                        <span x-show="lang === 'ha'" x-cloak>Matsakaicin Amsa</span>
                    </div>
                    <div class="text-4xl font-black text-korie-green font-mono tracking-tighter mb-2">0.3s</div>
                    <div class="text-slate-400 text-sm font-bold">99.99% Uptime</div>
                </div>
            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/koriepay_rebuild/resources/views/public/home.blade.php ENDPATH**/ ?>