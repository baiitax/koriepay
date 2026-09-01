<div class="mx-auto max-w-7xl space-y-6">
    @if ($notProvisioned)
        <x-kp.empty-state icon="identification" title="No aggregator profile"
            description="This account has the aggregator role but no aggregator record is provisioned. Contact KoriePay admin to link your network." />
    @else
        @php $p = $payload; @endphp

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-ink">Settlement center</h1>
                <p class="mt-0.5 text-sm text-muted">Commission payout batches — gross / fees / commission / adjustments / net, with expected-vs-actual reconciliation (§66–67).</p>
            </div>
            <button type="button" wire:click="createBatch"
                    class="rounded-xl bg-brand px-4 py-2.5 text-sm font-black text-white transition hover:bg-brand/90">
                Create batch from accrued commissions
            </button>
        </div>

        {{-- Status summary --}}
        <section class="grid grid-cols-3 gap-3 sm:grid-cols-5">
            @foreach (['pending' => 'Pending', 'processing' => 'Processing', 'settled' => 'Settled', 'failed' => 'Failed', 'under_review' => 'Under review'] as $key => $label)
                <button type="button" wire:click="setStatus('{{ $key }}')"
                        class="panel p-4 text-left transition {{ $status === $key ? 'ring-2 ring-brand/40' : '' }}">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-muted">{{ $label }}</p>
                    <p class="mt-1 font-mono text-xl font-black text-ink">{{ $p['summary'][$key] }}</p>
                </button>
            @endforeach
        </section>

        {{-- Batches --}}
        <section class="panel overflow-hidden">
            @if (count($p['rows']) === 0)
                <x-kp.empty-state icon="banknotes" title="No settlement batches" description="No batches match the current filter. Create one from accrued commissions." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[980px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-line text-[10px] font-black uppercase tracking-[0.14em] text-muted">
                                <th class="px-4 py-3">Batch</th>
                                <th class="px-4 py-3 text-right">Gross</th>
                                <th class="px-4 py-3 text-right">Fees</th>
                                <th class="px-4 py-3 text-right">Commission</th>
                                <th class="px-4 py-3 text-right">Adjustments</th>
                                <th class="px-4 py-3 text-right">Net</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Reconciliation</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($p['rows'] as $batch)
                                @php $rec = $batch['reconciliation']; @endphp
                                <tr class="hover:bg-panel-2/60">
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-xs font-bold text-ink">{{ $batch['reference'] }}</span>
                                        <span class="block text-[11px] text-muted">
                                            {{ $batch['currency_code'] }} · {{ optional($batch['period_start'])->format('M j') }} – {{ optional($batch['period_end'])->format('M j') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-ink">{{ number_format((float) $batch['gross_amount'], 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-muted">{{ number_format((float) $batch['fees'], 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-muted">{{ number_format((float) $batch['commission_amount'], 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-bold {{ (float) $batch['adjustments'] < 0 ? 'text-crit' : 'text-muted' }}">{{ number_format((float) $batch['adjustments'], 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-black text-brand">{{ number_format((float) $batch['net_amount'], 2) }}</td>
                                    <td class="px-4 py-3"><x-kp.status-badge :status="$batch['status']" /></td>
                                    <td class="px-4 py-3">
                                        <x-kp.status-badge :status="$rec['status']" :label="$rec['label']" />
                                        @if ($rec['status'] === 'difference')
                                            <span class="block text-[10px] font-bold text-crit">delta {{ number_format((float) $rec['delta'], 2) }} {{ $batch['currency_code'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-1.5">
                                            @if (in_array($batch['status'], ['pending', 'processing'], true))
                                                <button type="button" wire:click="settle({{ $batch['id'] }})" class="rounded-lg bg-brand px-2.5 py-1.5 text-[11px] font-black text-white transition hover:bg-brand/90">Settle</button>
                                            @endif
                                            @if ($batch['status'] === 'pending')
                                                <button type="button" wire:click="process({{ $batch['id'] }})" class="rounded-lg border border-line px-2.5 py-1.5 text-[11px] font-black text-muted transition hover:bg-panel-2 hover:text-ink">Process</button>
                                            @endif
                                            @if (in_array($batch['status'], ['pending', 'processing', 'under_review'], true))
                                                <button type="button" wire:click="fail({{ $batch['id'] }})" class="rounded-lg border border-crit/30 px-2.5 py-1.5 text-[11px] font-black text-crit transition hover:bg-crit/10">Fail</button>
                                            @endif
                                            @if (in_array($batch['status'], ['pending', 'processing'], true))
                                                <button type="button" wire:click="review({{ $batch['id'] }})" class="rounded-lg border border-line px-2.5 py-1.5 text-[11px] font-black text-muted transition hover:bg-panel-2 hover:text-ink">Review</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <p class="text-[11px] text-muted">Settling posts the payout to the aggregator float on the ledger (DR Settlement Expense / CR Aggregator Float) and marks the period's accrued commission entries as paid. Reconciliation compares the batch's expected amount (Σ accrued entries) against the actual amount paid.</p>
    @endif
</div>
