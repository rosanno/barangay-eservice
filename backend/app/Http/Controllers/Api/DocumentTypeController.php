<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentTypeResource;
use App\Models\DocumentType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentTypeController extends Controller
{
    /**
     * List the document types residents may request.
     */
    public function index(): AnonymousResourceCollection
    {
        $types = DocumentType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return DocumentTypeResource::collection($types);
    }
}
