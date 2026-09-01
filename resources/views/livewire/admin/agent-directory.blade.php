<div class="space-y-6" wire:poll.60s="render">
    <x-kp.section-header
        eyebrow="Network"
        title="Agent & Aggregator Directory"
        description="Every agent and aggregator on the KoriePay network. Country-aware and permission-gated — identities here are the same users powering agency banking."
    />

    {{-- Summary strip --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-kp.glass-card class="!p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-muted">Total Agents</p>
            <p class="mt-1 text-2xl font-black font-mono text-ink">{{ number_format($totalAgents) }}</p>
        </x-kp.glass-card>
        <x-kp.glass-card class="!p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-muted">Total Aggregators</p>
            <p class="mt-1 text-2xl font-black font-mono text-ink">{{ number_format($totalAggregators) }}</p>
        </x-kp.glass-card>
        <x-kp.glass-card class="!p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-muted">Active</p>
            <p class="mt-1 text-2xl font-black font-mono text-ok">{{ number_format($totalActive) }}</p>
        </x-kp.glass-card>
    </div>

    {{-- Filters --}}
    <div class="panel !p-4 flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
            <x-kp.icon name="magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-faint" stroke="2" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, email or phone…"
                   class="w-full pl-9 pr-3 py-2 rounded-xl glass-inset text-sm font-semibold text-ink placeholder:text-faint focus:ring-0" />
        </div>
        <select wire:model.live="role" class="rounded-xl glass-inset px-3 py-2 text-sm font-semibold text-ink focus:ring-0">
            <option value="">All roles</option>
            <option value="agent">Agents</option>
            <option value="aggregator">Aggregators</option>
        </select>
        <select wire:model.live="country" class="rounded-xl glass-inset px-3 py-2 text-sm font-semibold text-ink focus:ring-0">
            <option value="">All countries</option>
            @foreach($countries as $countryRow)
                <option value="{{ $countryRow->iso3 }}">{{ $countryRow->name }} ({{ $countryRow->currency_code }})</option>
            @endforeach
        </select>
    </div>

    {{-- Directory table --}}
    <div class="panel overflow-hidden">
        <div class="overflow-x-auto cc-scrollbar">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-[10px] font-black uppercase tracking-widest text-faint">
                        <th class="px-5 py-3">Entity</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Country</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $agent)
                        <tr class="border-b border-line/60 hover:bg-panel-2/60 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-brand to-brand-2 text-white font-black text-[10px] flex items-center justify-center shrink-0">
                                        {{ strtoupper(Str::limit($agent->name ?? '?', 2, '')) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-bold text-ink truncate">{{ $agent->name }}</p>
                                        <p class="text-[11px] font-medium text-muted truncate">{{ $agent->email ?? $agent->phone_number }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <x-kp.badge :tone="$agent->role === 'aggregator' ? 'info' : 'brand'">{{ ucfirst($agent->role) }}</x-kp.badge>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="text-xs font-semibold text-muted">
                                    {{ $agent->country_code ?? '—' }}
                                    <span class="text-faint">· {{ \App\Models\Country::where('iso3', $agent->country_code)->value('currency_code') ?? '' }}</span>
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <x-kp.status-badge :status="$agent->is_active ? 'active' : 'suspended'" />
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="text-xs font-medium text-muted">{{ optional($agent->created_at)->format('d M Y') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10">
                                <x-kp.empty-state
                                    icon="user-group"
                                    title="No agents or aggregators yet"
                                    description="Directory is empty for the current filters. Entities appear here as users with the agent or aggregator role."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($agents->hasPages())
            <div class="px-5 py-3 border-t border-line">
                {{ $agents->links() }}
            </div>
        @endif
    </div>
</div>
