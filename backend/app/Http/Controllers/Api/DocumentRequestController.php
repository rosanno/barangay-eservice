<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequestRequest;
use App\Http\Resources\DocumentRequestResource;
use App\Models\DocumentRequest;
use App\Services\DocumentRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentRequestController extends Controller
{
    public function __construct(private readonly DocumentRequestService $service)
    {
    }

    /**
     * List the authenticated resident's own document requests.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = DocumentRequest::query()
            ->with(['documentType', 'attachments'])
            ->where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return DocumentRequestResource::collection($requests);
    }

    public function store(StoreDocumentRequestRequest $request): JsonResponse
    {
        $documentRequest = $this->service->create($request->user(), $request->validated());

        return (new DocumentRequestResource($documentRequest))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, DocumentRequest $documentRequest): DocumentRequestResource
    {
        $this->authorize('view', $documentRequest);

        return new DocumentRequestResource($documentRequest->load(['documentType', 'attachments']));
    }

    /**
     * Track a request by its public tracking number (no auth requirement upstream is optional;
     * kept behind auth here so residents can only track their own, per RBAC conventions).
     */
    public function trackByNumber(Request $request, string $trackingNumber): DocumentRequestResource
    {
        $documentRequest = DocumentRequest::query()
            ->with(['documentType', 'attachments'])
            ->where('tracking_number', $trackingNumber)
            ->firstOrFail();

        $this->authorize('view', $documentRequest);

        return new DocumentRequestResource($documentRequest);
    }

    public function cancel(Request $request, DocumentRequest $documentRequest): DocumentRequestResource
    {
        $this->authorize('cancel', $documentRequest);

        $updated = $this->service->cancel($documentRequest, $request->user());

        return new DocumentRequestResource($updated->load(['documentType', 'attachments']));
    }
}
