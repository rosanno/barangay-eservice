<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensusCampaignPurok extends Model
{
    public $timestamps = false;
    protected $fillable = ['census_campaign_id', 'purok'];
}
