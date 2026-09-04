<?php

namespace App\Services;

use App\Enums\DocumentRequestStatus;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\User;
use App\Notifications\DocumentRequestStatusUpdated;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentRequestService
{
    /**
     * @param  array{document_type_id:int, purpose:string, details?:array, attachments?:array}  $data
     */
    public function create(User $user, array $data): DocumentRequest
    {
        $documentType = DocumentType::query()
            ->where('id', $data['document_type_id'])
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($user, $documentType, $data) {
            $request = DocumentRequest::create([
                'user_id' => $user->id,
                'document_type_id' => $documentType->id,
                'purpose' => $data['purpose'],
                'details' => $data['details'] ?? [],
                'status' => DocumentRequestStatus::Pending,
                'fee' => $documentType->fee,
            ]);

            foreach ($data['attachments'] ?? [] as $attachment) {
                /** @var UploadedFile $file */
                $file = $attachment['file'];
                $path = $file->store("document-requests/{$request->uuid}", 'private');

                $request->attachments()->create([
                    'label' => $attachment['label'] ?? null,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            return $request->load(['documentType', 'attachments']);
        });
    }

    public function cancel(DocumentRequest $request, User $user): DocumentRequest
    {
        if ($request->user_id !== $user->id) {
            throw ValidationException::withMessages(['request' => 'You may only cancel your own requests.']);
        }

        if (! $request->status->canTransitionTo(DocumentRequestStatus::Cancelled)) {
            throw ValidationException::withMessages(['status' => 'This request can no longer be cancelled.']);
        }

        $request->update([
            'status' => DocumentRequestStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $request;
    }

    /**
     * @param  array{status:string, remarks?:string, rejection_reason?:string}  $data
     */
    public function updateStatus(DocumentRequest $request, User $staff, array $data): DocumentRequest
    {
        $target = DocumentRequestStatus::from($data['status']);

        if (! $request->status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move a request from \"{$request->status->label()}\" to \"{$target->label()}\".",
            ]);
        }

        $timestampField = match ($target) {
            DocumentRequestStatus::Processing => 'processed_at',
            DocumentRequestStatus::ReadyForPickup => 'ready_at',
            DocumentRequestStatus::Released => 'released_at',
            DocumentRequestStatus::Cancelled => 'cancelled_at',
            default => null,
        };

        $request->update(array_filter([
            'status' => $target,
            'processed_by' => $staff->id,
            'remarks' => $data['remarks'] ?? $request->remarks,
            'rejection_reason' => $target === DocumentRequestStatus::Rejected
                ? ($data['rejection_reason'] ?? null)
                : $request->rejection_reason,
            $timestampField => $timestampField ? now() : null,
        ], fn ($value) => $value !== null));

        $request->user->notify(new DocumentRequestStatusUpdated($request->fresh()));

        return $request->fresh(['documentType', 'attachments']);
    }
}
