<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Appointmentresource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'resident_name' => $this->user?->name ?? 'Unknown resident',
            'purpose' => $this->purpose,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'status' => $this->status?->value ?? 'scheduled',
            'status_label' => $this->status?->label() ?? 'Scheduled',
        ];
    }
}
