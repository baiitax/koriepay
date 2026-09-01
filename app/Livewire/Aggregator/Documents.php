<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorDocumentsService;
use App\Domain\Aggregator\AggregatorTenantService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * AGGREGATOR CONSOLE — Stage H (document center, §63).
 *
 * Authorized docs only: the aggregator's own uploads plus KoriePay-published
 * notices. Uploads are stored as real bytes and audited.
 */
#[Layout('layouts.aggregator')]
class Documents extends Component
{
    use WithFileUploads;

    #[Url(as: 'category', history: true)]
    public string $category = '';

    public string $search = '';
    public int $perPage = 8;
    public int $page = 1;

    public bool $showUpload = false;
    public string $docCategory = 'other';
    public string $docTitle = '';
    public string $docVisibility = 'network';
    public $docFile = null;

    public array $categories = [];

    public function mount(AggregatorDocumentsService $service): void
    {
        $this->categories = $service->categories();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedCategory(): void
    {
        $this->page = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function resetFilters(): void
    {
        $this->reset(['category', 'search']);
        $this->page = 1;
    }

    public function openUpload(): void
    {
        $this->showUpload = true;
    }

    public function cancelUpload(): void
    {
        $this->showUpload = false;
        $this->reset(['docCategory', 'docTitle', 'docVisibility', 'docFile']);
    }

    public function upload(AggregatorDocumentsService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('document.manage'), 403, 'Unauthorized: missing permission [document.manage].');
        $this->validate([
            'docFile' => ['required', 'file', 'max:10240'],
            'docCategory' => ['required', 'string'],
            'docTitle' => ['required', 'string', 'max:200'],
            'docVisibility' => ['required', 'in:network,internal'],
        ]);

        $aggregator = app(AggregatorTenantService::class)->requireCurrent();
        $document = $service->upload($aggregator, auth()->user(), $this->docFile, $this->docCategory, $this->docTitle, $this->docVisibility);

        $this->reset(['showUpload', 'docCategory', 'docTitle', 'docVisibility', 'docFile']);
        $this->dispatch('toast', message: "Document \"{$document->title}\" uploaded.", type: 'success');
    }

    public function render(AggregatorDocumentsService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.documents', ['notProvisioned' => true, 'payload' => null]);
        }

        $payload = $service->center($aggregator, [
            'category' => $this->category,
            'search' => $this->search,
        ], $this->perPage, $this->page);

        return view('livewire.aggregator.documents', [
            'notProvisioned' => false,
            'payload' => $payload,
            'canManage' => Gate::forUser(auth()->user())->allows('document.manage'),
        ]);
    }
}
