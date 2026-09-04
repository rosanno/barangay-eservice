<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDocumentRequestStatusRequest;
use App\Http\Resources\DocumentRequestResource;
use App\Models\DocumentRequest;
use App\Services\DocumentRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentRequestAdminController extends Controller
{
    public function __construct(private readonly DocumentRequestService $service)
    {
    }

    /**
     * List all document requests (staff/admin), filterable by status and document type.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DocumentRequest::class);

        $requests = DocumentRequest::query()
            ->with(['documentType', 'user', 'attachments'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('document_type_id'), fn ($q) => $q->where('document_type_id', $request->integer('document_type_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('tracking_number', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term));
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return DocumentRequestResource::collection($requests);
    }

    public function show(DocumentRequest $documentRequest): DocumentRequestResource
    {
        $this->authorize('view', $documentRequest);

        return new DocumentRequestResource($documentRequest->load(['documentType', 'user', 'attachments']));
    }

    public function updateStatus(UpdateDocumentRequestStatusRequest $request, DocumentRequest $documentRequest): DocumentRequestResource
    {
        $updated = $this->service->updateStatus($documentRequest, $request->user(), $request->validated());

        return new DocumentRequestResource($updated);
    }
}
