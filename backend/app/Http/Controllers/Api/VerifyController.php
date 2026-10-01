<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use Illuminate\Http\JsonResponse;

class VerifyController extends Controller
{
    /**
     * GET /api/verify/{trackingNumber} — deliberately public, no auth
     * middleware. This is what a QR code scan lands on, so anyone (an
     * employer, a bank, a school) can confirm a document is genuine
     * without needing an account.
     *
     * Only ever reveals anything for requests that are actually
     * "released" — a pending/processing/rejected request returns 404
     * regardless of whether the tracking number is real, so this can't be
     * used as a general-purpose lookup tool for requests that haven't
     * been handed out yet.
     */
    public function show(string $trackingNumber): JsonResponse
    {
        $request = DocumentRequest::query()
            ->where('tracking_number', $trackingNumber)
            ->where('status', 'released')
            ->with(['documentType', 'user'])
            ->first();

        if (!$request) {
            return response()->json([
                'verified' => false,
                'message' => 'No released document matches this tracking number.',
            ], 404);
        }

        return response()->json([
            'verified' => true,
            'data' => [
                'tracking_number' => $request->tracking_number,
                'document_type' => $request->documentType->name,
                'resident_name' => $request->user?->name,
                'released_at' => $request->released_at?->toIso8601String(),
                // Deliberately excluded: purpose, fee, attachments, remarks,
                // rejection_reason — none of a third party's business, and
                // not needed to confirm authenticity.
            ],
        ]);
    }
}