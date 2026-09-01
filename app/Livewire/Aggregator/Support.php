<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorSupportService;
use App\Domain\Aggregator\AggregatorTenantService;
use App\Models\SupportTicket;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage H (support center, §59–62).
 *
 * Network support cases with priority-driven SLA countdown. Raising a case,
 * replying and changing status/priority are permission-gated
 * (`support.manage`) and every action is audited.
 */
#[Layout('layouts.aggregator')]
class Support extends Component
{
    #[Url(as: 'status', history: true)]
    public string $status = '';

    #[Url(as: 'priority', history: true)]
    public string $priority = '';

    public string $search = '';
    public int $perPage = 8;
    public int $page = 1;

    // Raise form
    public bool $showRaise = false;
    public string $category = 'technical';
    public string $subject = '';
    public string $message = '';
    public string $raisePriority = 'medium';

    // Reply / action state
    public ?int $activeTicket = null;
    public string $reply = '';
    public bool $replyInternal = false;

    public array $categories = [];
    public array $priorities = [];

    public function mount(AggregatorSupportService $service): void
    {
        $this->categories = $service->categories();
        $this->priorities = $service->priorities();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedStatus(): void
    {
        $this->page = 1;
    }

    public function updatedPriority(): void
    {
        $this->page = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function resetFilters(): void
    {
        $this->reset(['status', 'priority', 'search']);
        $this->page = 1;
    }

    public function openRaise(): void
    {
        $this->showRaise = true;
    }

    public function cancelRaise(): void
    {
        $this->showRaise = false;
        $this->reset(['category', 'subject', 'message', 'raisePriority']);
    }

    public function raise(AggregatorSupportService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('support.manage'), 403, 'Unauthorized: missing permission [support.manage].');
        $this->validate([
            'category' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:4000'],
            'raisePriority' => ['required', 'string'],
        ]);

        $aggregator = app(AggregatorTenantService::class)->requireCurrent();
        $ticket = $service->raise($aggregator, auth()->user(), $this->category, $this->subject, $this->message, $this->raisePriority);

        $this->reset(['showRaise', 'category', 'subject', 'message', 'raisePriority']);
        $this->dispatch('toast', message: "Case {$ticket->ticket_id} raised — SLA set.", type: 'success');
    }

    public function openTicket(int $id): void
    {
        $this->activeTicket = $id === $this->activeTicket ? null : $id;
        $this->reply = '';
    }

    public function addReply(int $id, AggregatorSupportService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('support.manage'), 403, 'Unauthorized: missing permission [support.manage].');
        $this->validate(['reply' => ['required', 'string', 'max:4000']]);

        $ticket = $this->owned($id, $service);
        $service->reply($ticket, auth()->user(), $this->reply, $this->replyInternal);
        $this->reply = '';
        $this->replyInternal = false;

        $this->dispatch('toast', message: 'Reply added to the case.', type: 'success');
    }

    public function setStatus(int $id, string $status, AggregatorSupportService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('support.manage'), 403, 'Unauthorized: missing permission [support.manage].');
        $service->setStatus($this->owned($id, $service), auth()->user(), $status);
        $this->dispatch('toast', message: "Case moved to [{$status}].", type: 'info');
    }

    public function setPriority(int $id, string $priority, AggregatorSupportService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('support.manage'), 403, 'Unauthorized: missing permission [support.manage].');
        $service->setPriority($this->owned($id, $service), auth()->user(), $priority);
        $this->dispatch('toast', message: 'Priority changed — SLA rebased.', type: 'info');
    }

    protected function owned(int $id, AggregatorSupportService $service): SupportTicket
    {
        $aggregator = app(AggregatorTenantService::class)->requireCurrent();
        $ticket = SupportTicket::query()->find($id);
        abort_unless($ticket !== null && $service->owned($ticket, $aggregator), 404, 'Support case not found in this network.');

        return $ticket;
    }

    public function render(AggregatorSupportService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.support', ['notProvisioned' => true, 'payload' => null]);
        }

        $payload = $service->center($aggregator, [
            'status' => $this->status,
            'priority' => $this->priority,
            'search' => $this->search,
        ], $this->perPage, $this->page);

        return view('livewire.aggregator.support', [
            'notProvisioned' => false,
            'payload' => $payload,
            'canManage' => Gate::forUser(auth()->user())->allows('support.manage'),
        ]);
    }
}
