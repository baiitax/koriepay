<?php

namespace App\Domain\Aggregator;

use App\Models\Aggregator;
use App\Models\AggregatorDocument;
use App\Models\Agent;
use App\Models\ReportJob;
use App\Models\SupportTicket;

/**
 * AGGREGATOR CONSOLE — Stage I (command search, Ctrl/Cmd+K, §135–144).
 *
 * Global search across real, tenant-scoped records: agents (code/name),
 * support cases (id/subject), documents (title) and reports (reference/type),
 * plus static navigation commands. Every result points at an existing record
 * the aggregator is authorized to see — no fabricated hits.
 */
class AggregatorCommandSearchService
{
    public function __construct(private readonly AggregatorTenantService $tenant)
    {
    }

    public function navCommands(): array
    {
        return [
            ['type' => 'nav', 'label' => 'Go to Overview', 'route' => 'aggregator.dashboard'],
            ['type' => 'nav', 'label' => 'Go to Agents', 'route' => 'aggregator.agents'],
            ['type' => 'nav', 'label' => 'Go to Liquidity', 'route' => 'aggregator.liquidity'],
            ['type' => 'nav', 'label' => 'Go to Commissions', 'route' => 'aggregator.commissions'],
            ['type' => 'nav', 'label' => 'Go to Settlements', 'route' => 'aggregator.settlements'],
            ['type' => 'nav', 'label' => 'Go to Network intelligence', 'route' => 'aggregator.network'],
            ['type' => 'nav', 'label' => 'Go to Risk & alerts', 'route' => 'aggregator.risk'],
            ['type' => 'nav', 'label' => 'Go to Support', 'route' => 'aggregator.support'],
            ['type' => 'nav', 'label' => 'Go to Documents', 'route' => 'aggregator.documents'],
            ['type' => 'nav', 'label' => 'Go to Reports', 'route' => 'aggregator.reports'],
            ['type' => 'nav', 'label' => 'Go to Data quality', 'route' => 'aggregator.data-quality'],
            ['type' => 'nav', 'label' => 'Go to Profile & limits', 'route' => 'aggregator.profile'],
            ['type' => 'nav', 'label' => 'Go to Insights & EOD', 'route' => 'aggregator.insights'],
        ];
    }

    public function search(Aggregator $aggregator, string $term, int $limit = 6): array
    {
        $term = trim($term);

        if ($term === '') {
            return ['term' => '', 'groups' => [], 'total' => 0];
        }

        $agentIds = $this->tenant->agentIds($aggregator);

        $agents = Agent::whereIn('id', $agentIds)
            ->where(function ($q) use ($term) {
                $q->where('agent_code', 'like', '%'.$term.'%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$term.'%'));
            })
            ->limit($limit)->get()
            ->map(fn (Agent $a) => [
                'type' => 'agent',
                'label' => $a->agent_code.' — '.($a->user?->name ?? 'Agent'),
                'sub' => $a->status.' · '.($a->city ?? $a->country_iso2),
                'route' => route('aggregator.agents.show', ['agent' => $a->agent_code]),
            ]);

        $tickets = SupportTicket::where(function ($q) use ($aggregator) {
            $q->where('aggregator_id', $aggregator->id)->orWhere('user_id', $aggregator->user_id);
        })
            ->where(function ($q) use ($term) {
                $q->where('ticket_id', 'like', '%'.$term.'%')
                    ->orWhere('subject', 'like', '%'.$term.'%');
            })
            ->limit($limit)->get()
            ->map(fn (SupportTicket $t) => [
                'type' => 'support',
                'label' => $t->ticket_id.' — '.$t->subject,
                'sub' => $t->category.' · '.$t->status,
                'route' => route('aggregator.support').'#ticket-'.$t->id,
            ]);

        $documents = AggregatorDocument::where(fn ($q) => $q->where('aggregator_id', $aggregator->id)->orWhere('is_system', true))
            ->where('title', 'like', '%'.$term.'%')
            ->limit($limit)->get()
            ->map(fn (AggregatorDocument $d) => [
                'type' => 'document',
                'label' => $d->title,
                'sub' => $d->category.' · '.($d->is_system ? 'KoriePay' : 'Your network'),
                'route' => route('aggregator.documents'),
            ]);

        $reports = ReportJob::where('aggregator_id', $aggregator->id)
            ->where(function ($q) use ($term) {
                $q->where('reference', 'like', '%'.$term.'%')
                    ->orWhere('type', 'like', '%'.$term.'%');
            })
            ->limit($limit)->get()
            ->map(fn (ReportJob $j) => [
                'type' => 'report',
                'label' => $j->reference.' — '.$j->type.' ('.$j->format.')',
                'sub' => $j->status,
                'route' => route('aggregator.reports'),
            ]);

        // filter() keeps original keys, so values() re-sequences before use —
        // otherwise a nav item could land at a non-zero index and break the UI.
        $nav = collect($this->navCommands())->filter(fn ($n) => str_contains(strtolower($n['label']), strtolower($term)))
            ->take($limit)->map(fn ($n) => [
                'type' => 'nav',
                'label' => $n['label'],
                'sub' => 'Navigate',
                'route' => route($n['route']),
            ])->values();

        $groups = collect()
            ->when($agents->isNotEmpty(), fn ($c) => $c->push(['key' => 'agents', 'label' => 'Agents', 'items' => $agents->all()]))
            ->when($tickets->isNotEmpty(), fn ($c) => $c->push(['key' => 'support', 'label' => 'Support cases', 'items' => $tickets->all()]))
            ->when($documents->isNotEmpty(), fn ($c) => $c->push(['key' => 'documents', 'label' => 'Documents', 'items' => $documents->all()]))
            ->when($reports->isNotEmpty(), fn ($c) => $c->push(['key' => 'reports', 'label' => 'Reports', 'items' => $reports->all()]))
            ->when($nav->isNotEmpty(), fn ($c) => $c->push(['key' => 'nav', 'label' => 'Navigate', 'items' => $nav->all()]))
            ->values()->all();

        $total = collect($groups)->sum(fn ($g) => count($g['items']));

        return [
            'term' => $term,
            'groups' => $groups,
            'total' => $total,
            'basis' => 'Live search across tenant-scoped records — no cached or fabricated results.',
        ];
    }
}
