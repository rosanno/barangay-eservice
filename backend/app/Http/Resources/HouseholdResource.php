<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseholdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'household_number' => $this->household_number,
            'purok' => $this->purok,
            'street_address' => $this->street_address,
            'housing_tenure' => $this->housing_tenure,
            'house_material' => $this->house_material,
            'water_source' => $this->water_source,
            'toilet_type' => $this->toilet_type,
            'has_electricity' => $this->has_electricity,
            'income_bracket' => $this->income_bracket,
            'is_4ps' => $this->is_4ps,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'verified_at' => $this->verified_at,
            'consented_at' => $this->consent_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'head_name' => $this->whenLoaded('head', fn() => $this->head?->full_name),
            'member_count' => $this->whenCounted('members'),
            'verified_by' => $this->whenLoaded('verifier', fn() => $this->verifier?->name),
            'submitted_by' => $this->whenLoaded('user', fn() => $this->user ? ['id' => $this->user->id, 'name' => $this->user->name] : null),

            'members' => $this->whenLoaded('members', fn() => $this->members->map(fn($m) => [
                'id' => $m->id,
                'first_name' => $m->first_name,
                'middle_name' => $m->middle_name,
                'last_name' => $m->last_name,
                'suffix' => $m->suffix,
                'full_name' => $m->full_name,
                'relationship_to_head' => $m->relationship_to_head,
                'is_head' => $m->is_head,
                'birthdate' => $m->birthdate?->toDateString(),
                'age' => $m->age,
                'sex' => $m->sex,
                'civil_status' => $m->civil_status,
                'education' => $m->education,
                'occupation' => $m->occupation,
                'is_pwd' => $m->is_pwd,
                'is_solo_parent' => $m->is_solo_parent,
                'is_registered_voter' => $m->is_registered_voter,
            ])->values()),
        ];
    }
}
