<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Household extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['pending', 'verified', 'needs_update', 'rejected', 'inactive'];

    protected $fillable = [
        'user_id',
        'purok_id',
        'purok', // legacy text column, kept in sync from purok_id
        'street_address',
        'housing_tenure',
        'house_material',
        'water_source',
        'toilet_type',
        'has_electricity',
        'income_bracket',
        'is_4ps',
        'status',
        'remarks',
        'verified_by',
        'verified_at',
        'consent_at',
    ];

    protected $casts = [
        'has_electricity' => 'boolean',
        'is_4ps' => 'boolean',
        'verified_at' => 'datetime',
        'consent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn(self $h) => $h->uuid ??= (string) Str::uuid());

        // Keep the legacy `purok` text in sync so old reports/exports don't break
        static::saving(function (self $h) {
            if ($h->purok_id && ($h->isDirty('purok_id') || blank($h->purok))) {
                $h->purok = Purok::whereKey($h->purok_id)->value('name');
            }
        });

        static::created(function (self $h) {
            $h->updateQuietly([
                'household_number' => sprintf('HH-%s-%05d', now()->year, $h->id),
            ]);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
    // Named purokRecord (not purok) while the legacy `purok` column exists
    public function purokRecord(): BelongsTo
    {
        return $this->belongsTo(Purok::class, 'purok_id');
    }
    public function members(): HasMany
    {
        return $this->hasMany(HouseholdMember::class)->orderByDesc('is_head')->orderBy('birthdate');
    }
    public function head(): HasOne
    {
        return $this->hasOne(HouseholdMember::class)->where('is_head', true);
    }

    public function scopeInPurok(Builder $q, int|string|null $purokId): Builder
    {
        return $purokId ? $q->where('purok_id', $purokId) : $q;
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}