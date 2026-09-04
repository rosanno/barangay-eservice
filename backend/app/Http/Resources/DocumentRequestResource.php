<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'tracking_number' => $this->tracking_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'purpose' => $this->purpose,
            'details' => $this->details ?? [],

            'document_type' => new DocumentTypeResource($this->whenLoaded('documentType')),

            'requested_by' => $this->when(
                $request->user()?->can('viewAny', \App\Models\DocumentRequest::class),
                fn () => [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                ]
            ),

            'fee' => (float) $this->fee,
            'payment_status' => $this->payment_status->value,

            'remarks' => $this->when(
                $request->user()?->can('viewAny', \App\Models\DocumentRequest::class),
                $this->remarks
            ),
            'rejection_reason' => $this->rejection_reason,

            'attachments' => $this->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'label' => $attachment->label,
                'original_name' => $attachment->original_name,
                'url' => $attachment->url,
            ]),

            'timeline' => [
                'requested_at' => $this->created_at?->toIso8601String(),
                'processed_at' => $this->processed_at?->toIso8601String(),
                'ready_at' => $this->ready_at?->toIso8601String(),
                'released_at' => $this->released_at?->toIso8601String(),
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            ],
        ];
    }
}
