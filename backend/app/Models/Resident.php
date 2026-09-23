<?php

namespace App\Models;

use App\Enums\Sex;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Resident extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'purok',
        'first_name',
        'middle_name',
        'last_name',
        'sex',
        'date_of_birth',
        'place_of_birth',
        'citizenship',
        'religion',
        'blood_type',
        'mother_first_name',
        'mother_middle_name',
        'mother_last_name',
        'mother_occupation',
        'father_first_name',
        'father_middle_name',
        'father_last_name',
        'father_suffix',
        'father_occupation',
        'spouse_first_name',
        'spouse_middle_name',
        'spouse_last_name',
        'spouse_suffix',
        'number_of_children',
        'emergency_contact_first_name',
        'emergency_contact_middle_name',
        'emergency_contact_last_name',
        'emergency_contact_suffix',
        'emergency_contact_number',
    ];

    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'date_of_birth' => 'date',
            'number_of_children' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Resident $resident) {
            $resident->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Derived, not stored — see the migration's comment on why there's no
     * 'age' column. Recomputed on every access, so it's never stale.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? Carbon::parse($this->date_of_birth)->age : null;
    }

    public function getFullNameAttribute(): string
    {
        return trim(str_replace('  ', ' ', "{$this->first_name} {$this->middle_name} {$this->last_name}"));
    }

    public function getFatherFullNameAttribute(): ?string
    {
        if (!$this->father_first_name) {
            return null;
        }

        return trim(str_replace(
            '  ',
            ' ',
            "{$this->father_first_name} {$this->father_middle_name} {$this->father_last_name} {$this->father_suffix}"
        ));
    }

    public function getMotherFullNameAttribute(): ?string
    {
        if (!$this->mother_first_name) {
            return null;
        }

        return trim(str_replace(
            '  ',
            ' ',
            "{$this->mother_first_name} {$this->mother_middle_name} {$this->mother_last_name}"
        ));
    }

    public function getSpouseFullNameAttribute(): ?string
    {
        if (!$this->spouse_first_name) {
            return null;
        }

        return trim(str_replace(
            '  ',
            ' ',
            "{$this->spouse_first_name} {$this->spouse_middle_name} {$this->spouse_last_name} {$this->spouse_suffix}"
        ));
    }

    public function getEmergencyContactFullNameAttribute(): string
    {
        return trim(str_replace(
            '  ',
            ' ',
            "{$this->emergency_contact_first_name} {$this->emergency_contact_middle_name} " .
            "{$this->emergency_contact_last_name} {$this->emergency_contact_suffix}"
        ));
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
