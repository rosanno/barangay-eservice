<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CensusCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'start_date', 'end_date', 'status', 'created_by'];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'activated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const TRANSITIONS = [
        'draft' => ['active'],
        'active' => ['paused', 'completed'],
        'paused' => ['active', 'completed'],
        'completed' => [],
    ];

    protected static function booted(): void
    {
        static::creating(fn($c) => $c->uuid ??= (string) Str::uuid());
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function puroks()
    {
        return $this->hasMany(CensusCampaignPurok::class);
    }
    public function enumerators()
    {
        return $this->belongsToMany(User::class, 'census_campaign_enumerators')
            ->withPivot('purok')->withTimestamps();
    }
    public function targets()
    {
        return $this->hasMany(CensusCampaignHousehold::class);
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /** Create target rows for every household in the selected puroks (idempotent). */
    public function syncTargets(): void
    {
        $ids = Household::whereIn('purok', $this->puroks()->pluck('purok'))->pluck('id');

        foreach ($ids as $id) {
            $this->targets()->firstOrCreate(['household_id' => $id]);
        }
        // Drop untouched targets that are no longer in the selected puroks
        $this->targets()->where('status', 'pending')->whereNotIn('household_id', $ids)->delete();
    }
}
