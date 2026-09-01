<div class="space-y-8" x-data="{
    initChart() {
        let options = {
            series: [{
                name: 'Processed Volume',
                data: @js($chartVolumes)
            }],
            chart: { height: 320, type: 'area', fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false } },
            colors: ['#29B475'],
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            xaxis: { categories: @js($chartDays), axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { formatter: function (val) { return '₦' + (val/1000).toFixed(0) + 'k'; } } },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4, yaxis: { lines: { show: true } } }
        };

        let chart = new ApexCharts(document.querySelector('#volumeChart'), options);
        chart.render();

        // Listen for Livewire Pulse Updates
        window.addEventListener('update-chart', event => {
            chart.updateSeries([{ data: event.detail.series }]);
            chart.updateOptions({ xaxis: { categories: event.detail.categories } });
        });
    }
}" x-init="initChart()">

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight uppercase italic">Sovereign Telemetry</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1 flex items-center gap-2">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#29B475] opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-[#29B475]"></span>
                </span>
                Live Grid Active
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button class="px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 hover:border-slate-300 transition-all shadow-sm">Export Report</button>
            <button class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-[#158987] transition-all shadow-lg">Network Map</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform"><svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Global Volume</p>
            <h3 class="text-2xl font-black text-slate-900 font-mono tracking-tighter">₦{{ number_format($totalVolume) }}</h3>
            <p class="text-[10px] font-bold text-[#29B475] mt-2">+12.5% vs Last Wk</p>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform"><svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Active Liquidity Pools</p>
            <h3 class="text-2xl font-black text-slate-900 font-mono tracking-tighter">₦{{ number_format($activeLiquidity) }}</h3>
            <p class="text-[10px] font-bold text-slate-400 mt-2">Across All Nodes</p>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform"><svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Realized Profit (USD)</p>
            <h3 class="text-2xl font-black text-[#158987] font-mono tracking-tighter">${{ number_format($totalRevenue, 2) }}</h3>
            <p class="text-[10px] font-bold text-[#29B475] mt-2">Spread & Fee Harvest</p>
        </div>

        <div class="bg-slate-900 p-6 rounded-[2rem] shadow-xl relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent"></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 relative z-10">Network Health</p>
            <h3 class="text-2xl font-black text-white font-mono tracking-tighter relative z-10">{{ $successRate }}%</h3>
            <p class="text-[10px] font-bold text-[#29B475] mt-2 relative z-10">Settlement Success Rate</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 bg-white rounded-[2.5rem] border border-slate-200 shadow-sm p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest italic">Volume Trajectory</h3>
                    <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">7-Day Aggregation</p>
                </div>
                <div class="px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-lg text-[9px] font-black text-slate-500 uppercase tracking-widest">Base: NGN</div>
            </div>
            <div id="volumeChart" class="w-full" wire:ignore></div>
        </div>

        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm flex flex-col h-full overflow-hidden">
            <div class="p-8 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest italic">Live Pulse</h3>
                <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">Network Ingress</p>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4">
                <div class="space-y-2">
                    @forelse($recentTransactions as $tx)
                    <div class="p-4 rounded-2xl border border-slate-100 bg-white hover:bg-slate-50 hover:border-slate-200 transition-all group animate-in slide-in-from-top-2 duration-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-[#29B475]/10 text-[#29B475] flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-black text-slate-900">{{ $tx->receiver_name }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ $tx->source_currency }} → {{ $tx->destination_currency }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-black text-slate-900 font-mono">{{ number_format($tx->source_amount) }}</p>
                                <p class="text-[8px] font-black text-slate-400 uppercase mt-1">{{ $tx->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Awaiting Grid Activity</p>
                    </div>
                    @endforelse
                </div>
            </div>
            
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                <a href="{{ route('admin.transactions') }}" class="block w-full text-center py-3 bg-white border border-slate-200 rounded-xl text-[10px] font-black text-slate-600 uppercase tracking-widest hover:border-slate-300 hover:text-slate-900 transition-all shadow-sm">View Command Center</a>
            </div>
        </div>

    </div>
</div>