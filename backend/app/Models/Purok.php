<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Purok extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description', 'leader_name', 'leader_contact', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(fn(self $p) => $p->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function households(): HasMany
    {
        return $this->hasMany(Household::class);
    }

    public function members(): HasManyThrough
    {
        return $this->hasManyThrough(HouseholdMember::class, Household::class);
    }
}