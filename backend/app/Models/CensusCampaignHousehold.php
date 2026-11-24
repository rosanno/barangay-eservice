<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensusCampaignHousehold extends Model
{
    protected $fillable = ['census_campaign_id', 'household_id', 'enumerator_id', 'status', 'surveyed_at'];
    protected $casts = ['surveyed_at' => 'datetime'];

    public function household()
    {
        return $this->belongsTo(Household::class);
    }
    public function enumerator()
    {
        return $this->belongsTo(User::class, 'enumerator_id');
    }
}
