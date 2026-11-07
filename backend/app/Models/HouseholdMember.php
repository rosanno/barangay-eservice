<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseholdMember extends Model
{
    protected $fillable = [
        'household_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'relationship_to_head',
        'is_head',
        'birthdate',
        'sex',
        'civil_status',
        'education',
        'occupation',
        'is_pwd',
        'is_solo_parent',
        'is_registered_voter',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'is_head' => 'boolean',
        'is_pwd' => 'boolean',
        'is_solo_parent' => 'boolean',
        'is_registered_voter' => 'boolean',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])));
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birthdate?->age;
    }
}
