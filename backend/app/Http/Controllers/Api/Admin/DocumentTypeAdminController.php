<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentTypeRequest;
use App\Models\DocumentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentTypeAdminController extends Controller
{
    /**
     * Unlike the public GET /document-types (which only returns active
     * types, for the resident-facing request form), this returns every
     * type including inactive ones — an admin managing the list needs to
     * see what's been turned off, not just what residents currently see.
     */
    public function index(Request $request): JsonResponse
    {
        $types = DocumentType::query()
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $types]);
    }

    public function store(StoreDocumentTypeRequest $request): JsonResponse
    {
        $type = DocumentType::create($request->validated());

        return response()->json(['data' => $type], 201);
    }

    public function update(StoreDocumentTypeRequest $request, DocumentType $documentType): JsonResponse
    {
        $documentType->update($request->validated());

        return response()->json(['data' => $documentType->fresh()]);
    }

    /**
     * No destroy() — document_requests.document_type_id has a
     * restrictOnDelete() foreign key from the original migration, so
     * hard-deleting a type that any request references would fail at the
     * database level anyway. Deactivating (is_active=false via update())
     * is the only supported way to retire a type — it stops appearing on
     * the resident request form while existing requests keep working.
     */
}