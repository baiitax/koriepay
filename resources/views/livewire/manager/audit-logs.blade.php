<div class="p-6 lg:p-12 space-y-8 bg-[#f8fafc] min-h-screen">
    <div class="bg-white p-10 rounded-[3rem] border border-slate-200 shadow-sm">
        <h1 class="text-3xl font-black text-slate-900 italic uppercase">System Audit Log</h1>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">Managerial Action History // Non-Repudiation Layer</p>
    </div>

    <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-100 text-[9px] font-black text-slate-400 uppercase tracking-[0.25em]">
                <tr>
                    <th class="px-10 py-6">Timestamp</th>
                    <th class="px-10 py-6 text-center">Protocol Action</th>
                    <th class="px-10 py-6">Target Node</th>
                    <th class="px-10 py-6 text-right">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($logs as $log)
                    <tr class="hover:bg-slate-50/50 transition-all font-mono text-[11px]">
                        <td class="px-10 py-5 text-slate-500">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-10 py-5 text-center">
                            <span class="px-3 py-1 rounded-lg font-black uppercase tracking-tighter
                                {{ $log->action === 'FUNDING' ? 'bg-emerald-50 text-emerald-600' : '' }}
                                {{ $log->action === 'FREEZE' ? 'bg-red-50 text-red-600' : '' }}
                                {{ $log->action === 'UNFREEZE' ? 'bg-blue-50 text-blue-600' : '' }}">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-10 py-5 font-bold text-slate-900">
                            NODE-{{ str_pad($log->target_id, 5, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-10 py-5 text-right text-slate-400">
                            @if($log->action === 'FUNDING')
                                +{{ number_format($log->payload['amount'] ?? 0, 2) }} {{ $log->payload['currency'] ?? '' }}
                            @else
                                ACCESS_STATE_MODIFIED
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="px-10 py-6">
            {{ $logs->links() }}
        </div>
    </div>
</div>