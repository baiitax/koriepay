@props([
    'groups' => [],
    'currentRoute' => null,
    'forceExpand' => false, // true when rendered inside the mobile drawer
])

@php
    // Collapse reactivity is Alpine-driven (parent scope owns `sidebarCollapsed`).
    // forceExpand renders as a literal so drawer labels never collapse.
    $showLabel = $forceExpand ? 'true' : 'false';
@endphp

<nav class="flex-1 overflow-y-auto cc-scrollbar px-2.5 py-4" aria-label="Command Center sections">
    @foreach($groups as $gIndex => $group)
        <div class="mb-4">
            <p x-show="{{ $showLabel }} || !sidebarCollapsed" x-cloak class="px-3 mb-1.5 text-[9px] font-black uppercase tracking-[0.18em] text-faint">
                {{ $group['label'] }}
            </p>

            <div class="space-y-0.5">
                @foreach($group['items'] as $index => $item)
                    @php
                        $route = $item['route'] ?? null;
                        $isActive = $route !== null && $currentRoute === $route;
                        $hasChildren = ! empty($item['children']);
                        $soon = ! empty($item['soon']);
                        $childActive = $hasChildren && collect($item['children'])->contains(fn ($c) => ($c['route'] ?? null) === $currentRoute);
                        $permitted = ! isset($item['permission']) || auth()->user()->can($item['permission']);
                    @endphp

                    @if(! $permitted)
                        @continue
                    @endif

                    @if($hasChildren)
                        {{-- Accordion group (e.g. Risk Center, KYC) --}}
                        <div x-data="{ open: {{ $childActive ? 'true' : 'false' }} }" class="relative">
                            <button type="button" @click="open = ! open" x-bind:aria-expanded="open"
                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-semibold transition-all
                                           {{ $childActive ? 'bg-brand/10 text-brand' : 'text-muted hover:bg-panel-2 hover:text-ink' }}">
                                <span class="shrink-0 w-[18px] h-[18px] flex items-center justify-center">
                                    <x-kp.icon :name="$item['icon'] ?? 'squares-2x2'" class="w-[18px] h-[18px]" stroke="1.8" />
                                </span>
                                <span x-show="{{ $showLabel }} || !sidebarCollapsed" x-cloak class="flex-1 text-left truncate">{{ $item['label'] }}</span>
                                <x-kp.icon name="chevron-down" x-show="{{ $showLabel }} || !sidebarCollapsed" x-cloak class="w-3.5 h-3.5 transition-transform duration-200" stroke="2.2" x-bind:class="open && 'rotate-180'" />
                            </button>
                            <div x-show="open && ({{ $showLabel }} || !sidebarCollapsed)" x-collapse>
                                <div class="ml-[22px] pl-3 border-l border-line space-y-0.5 py-1">
                                    @foreach($item['children'] as $child)
                                        @php
                                            $childRoute = $child['route'] ?? null;
                                            $childActive = $childRoute !== null && $currentRoute === $childRoute;
                                        @endphp
                                        @if($childRoute)
                                            <a href="{{ route($childRoute) }}" aria-current="{{ $childActive ? 'page' : 'false' }}"
                                               class="block px-3 py-1.5 rounded-lg text-[12px] font-medium transition-all
                                                      {{ $childActive ? 'bg-brand/10 text-brand font-bold' : 'text-muted hover:bg-panel-2 hover:text-ink' }}">
                                                {{ $child['label'] }}
                                            </a>
                                        @else
                                            <span class="block px-3 py-1.5 rounded-lg text-[12px] font-medium text-faint/70 cursor-not-allowed opacity-70" title="Ships in Phase 9">
                                                {{ $child['label'] }} <span class="text-[8px] font-black uppercase tracking-wider text-faint">· P9</span>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @elseif($route)
                        <a href="{{ route($route) }}" aria-current="{{ $isActive ? 'page' : 'false' }}"
                           title="{{ $item['label'] }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-semibold transition-all group
                                  {{ $isActive ? 'bg-brand/10 text-brand shadow-[inset_0_0_0_1px_rgba(21,137,135,0.25)]' : 'text-muted hover:bg-panel-2 hover:text-ink' }}">
                            <span class="shrink-0 w-[18px] h-[18px] flex items-center justify-center">
                                <x-kp.icon :name="$item['icon'] ?? 'squares-2x2'" class="w-[18px] h-[18px]" stroke="1.8" />
                            </span>
                            <span x-show="{{ $showLabel }} || !sidebarCollapsed" x-cloak class="flex-1 truncate">{{ $item['label'] }}</span>
                            @if(! empty($item['badge']))
                                <span class="shrink-0 min-w-[18px] h-[18px] px-1.5 rounded-full text-[9px] font-black inline-flex items-center justify-center
                                              {{ ($item['badge']['tone'] ?? 'crit') === 'crit' ? 'bg-crit/15 text-crit' : 'bg-brand/15 text-brand' }}">
                                    {{ $item['badge']['text'] }}
                                </span>
                            @endif
                            @if(! empty($item['critical']))
                                <span class="shrink-0 w-2 h-2 rounded-full bg-crit animate-pulse" title="Critical alert indicator"></span>
                            @endif
                        </a>
                    @elseif($soon)
                        <span class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-semibold text-faint/70 cursor-not-allowed opacity-70 select-none"
                              title="Ships with the Phase 9 data layer">
                            <span class="shrink-0 w-[18px] h-[18px] flex items-center justify-center">
                                <x-kp.icon :name="$item['icon'] ?? 'squares-2x2'" class="w-[18px] h-[18px]" stroke="1.8" />
                            </span>
                            <span x-show="{{ $showLabel }} || !sidebarCollapsed" x-cloak class="flex-1 truncate">{{ $item['label'] }}</span>
                            <span x-show="{{ $showLabel }} || !sidebarCollapsed" x-cloak class="shrink-0 text-[8px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-panel-2 border border-line text-faint">P9</span>
                        </span>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</nav>
