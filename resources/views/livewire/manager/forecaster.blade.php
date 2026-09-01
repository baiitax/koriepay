<div class="p-4 lg:p-8 max-w-[1600px] mx-auto space-y-8">
    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
        <h1 class="text-3xl font-black text-slate-900">Regional Revenue Forecaster</h1>
        <p class="text-slate-500 font-bold text-sm mt-1 uppercase tracking-widest text-[10px]">Projected growth based on current regional velocity</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12">
            <div class="bg-slate-900 p-8 rounded-[2rem] text-white relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-32 h-32 bg-emerald-500 rounded-full blur-[60px] opacity-20"></div>
                <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Estimated Next Month Revenue</p>
                <h2 class="text-5xl font-black mt-4 tracking-tighter">
                    ₦{{ number_format($projectedRevenue, 2) }}
                </h2>
                <div class="mt-8 flex items-center gap-2">
                    <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg text-xs font-black">+{{ number_format($growthRate, 1) }}% Velocity</span>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Actual Revenue (MTD)</p>
                    <h2 class="text-4xl font-black text-slate-900 mt-2">₦{{ number_format($currentRevenue, 2) }}</h2>
                </div>
                <div class="pt-6 border-t border-slate-50">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Confidence Level</p>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500" style="width: 85%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>