<?php

namespace App\Domain\Aggregator;

use App\Models\Aggregator;
use App\Models\AggregatorDocument;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AGGREGATOR CONSOLE — Stage H (document center, §63).
 *
 * "Authorized docs only": an aggregator can ONLY list and download (a) their
 * own uploaded documents and (b) system-published documents issued by
 * KoriePay to every aggregator. Foreign tenants' documents are invisible and
 * any direct access returns 404 (IDOR §133). Every upload/download is
 * audited.
 */
class AggregatorDocumentsService
{
    public function __construct(private readonly AggregatorTenantService $tenant)
    {
    }

    public function categories(): array
    {
        return AggregatorDocument::CATEGORIES;
    }

    public function center(Aggregator $aggregator, array $filters = [], int $perPage = 10, int $page = 1): array
    {
        $query = AggregatorDocument::query()
            ->where(fn ($q) => $q->where('aggregator_id', $aggregator->id)->orWhere('is_system', true))
            ->latest('created_at');

        $category = $filters['category'] ?? '';
        if ($category !== '' && in_array($category, AggregatorDocument::CATEGORIES, true)) {
            $query->where('category', $category);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where('title', 'like', '%'.$search.'%');
        }

        $total = (clone $query)->count();

        return [
            'documents' => $query->forPage($page, $perPage)->get()->map(fn (AggregatorDocument $d) => $this->present($d))->all(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'summary' => [
                'own' => AggregatorDocument::where('aggregator_id', $aggregator->id)->count(),
                'system' => AggregatorDocument::where('is_system', true)->count(),
            ],
            'basis' => 'Authorized documents only: your uploads plus KoriePay-published notices.',
        ];
    }

    /**
     * Upload a document to the tenant's center. Real bytes are stored under
     * storage/app/documents/{aggregator_code}/ — metadata always reflects the
     * stored file (name, mime, size) so the list is never fabricated.
     */
    public function upload(Aggregator $aggregator, User $actor, UploadedFile $file, string $category, string $title, string $visibility = 'network'): AggregatorDocument
    {
        if (! in_array($category, AggregatorDocument::CATEGORIES, true)) {
            throw new \InvalidArgumentException("Unsupported document category [{$category}].");
        }
        if (! in_array($visibility, [AggregatorDocument::VISIBILITY_NETWORK, AggregatorDocument::VISIBILITY_INTERNAL], true)) {
            throw new \InvalidArgumentException("Unsupported visibility [{$visibility}].");
        }
        if (trim($title) === '') {
            throw new \InvalidArgumentException('A document title is required.');
        }

        $safe = Str::slug($title) ?: 'document';
        $storedName = $safe.'-'.Str::lower(Str::random(8)).'.'.$file->getClientOriginalExtension();

        $path = $file->storeAs(
            'documents/'.$aggregator->code,
            $storedName,
            ['disk' => 'local']
        );

        $document = AggregatorDocument::create([
            'aggregator_id' => $aggregator->id,
            'category' => $category,
            'title' => trim($title),
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'visibility' => $visibility,
            'is_system' => false,
            'uploaded_by' => $actor->id,
        ]);

        AuditLog::record('document.uploaded', $actor->id, $actor->id, [
            'description' => "Document [{$document->title}] uploaded to the document center.", 'event_type' => 'operations',
            'metadata' => ['document_id' => $document->id, 'category' => $category, 'size_bytes' => $file->getSize()],
        ]);

        return $document;
    }

    /**
     * Prepare a tenant-authorized download. Returns path/name/mime or throws
     * 404 for foreign/unknown documents. Download is audited.
     *
     * @return array{path: string, name: string, mime: string}
     */
    public function download(AggregatorDocument $document, Aggregator $aggregator, User $actor): array
    {
        abort_unless($this->owned($document, $aggregator), 404, 'Document not found in this network.');

        $path = $document->file_path;
        if ($path === null || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Document file is not available.');
        }

        AuditLog::record('document.downloaded', $actor->id, $actor->id, [
            'description' => "Document [{$document->title}] downloaded.", 'event_type' => 'operations',
            'metadata' => ['document_id' => $document->id, 'category' => $document->category],
        ]);

        return [
            'path' => $path,
            'name' => $document->file_name ?: basename($path),
            'mime' => $document->mime ?: 'application/octet-stream',
        ];
    }

    /** Tenant guard for direct access (IDOR §133). */
    public function owned(AggregatorDocument $document, Aggregator $aggregator): bool
    {
        return $document->is_system || (int) $document->aggregator_id === (int) $aggregator->id;
    }

    protected function present(AggregatorDocument $document): array
    {
        return [
            'id' => $document->id,
            'category' => $document->category,
            'title' => $document->title,
            'file_name' => $document->file_name,
            'mime' => $document->mime,
            'size_bytes' => $document->size_bytes,
            'visibility' => $document->visibility,
            'is_system' => $document->is_system,
            'source' => $document->is_system ? 'KoriePay' : 'Your network',
            'uploaded_by' => $document->uploader?->name,
            'created_at' => $document->created_at?->toIso8601String(),
            'downloadable' => $document->file_path !== null,
        ];
    }
}
