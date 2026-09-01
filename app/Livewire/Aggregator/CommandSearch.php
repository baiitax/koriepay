<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorCommandSearchService;
use App\Domain\Aggregator\AggregatorTenantService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage I (command search, Ctrl/Cmd+K).
 *
 * Global overlay search across tenant-scoped records + navigation. Opened via
 * the keyboard shortcut (or the header button), it renders inside the shared
 * layout. All results come from live, authorized data.
 */
class CommandSearch extends Component
{
    public bool $open = false;
    public string $term = '';
    public int $highlight = 0;

    #[On('toggle-command-search')]
    public function toggle(): void
    {
        $this->open = ! $this->open;
        $this->term = '';
        $this->highlight = 0;
    }

    public function close(): void
    {
        $this->open = false;
        $this->term = '';
        $this->highlight = 0;
    }

    public function updatedTerm(): void
    {
        $this->highlight = 0;
    }

    public function goNext(): void
    {
        $this->highlight++;
    }

    public function goPrev(): void
    {
        $this->highlight = max(0, $this->highlight - 1);
    }

    public function render(AggregatorCommandSearchService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();

        if (! $this->open || $aggregator === null) {
            return view('livewire.aggregator.command-search', [
                'open' => $this->open,
                'term' => $this->term,
                'groups' => [],
                'total' => 0,
                'highlight' => $this->highlight,
            ]);
        }

        $result = $service->search($aggregator, $this->term);

        return view('livewire.aggregator.command-search', [
            'open' => $this->open,
            'term' => $this->term,
            'groups' => $result['groups'],
            'total' => $result['total'],
            'highlight' => $this->highlight,
        ]);
    }
}
